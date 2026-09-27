<?php

declare(strict_types=1);

namespace Tests\Feature\Hospital;

use App\Enums\DrugMovementType;
use App\Enums\HospitalizationStatus;
use App\Enums\KardexStatus;
use App\Enums\UserRole;
use App\Events\KardexUpdated;
use App\Exceptions\Kardex\InsufficientStockException;
use App\Exceptions\Kardex\ScheduleNotAdministrableException;
use App\Models\Clinic;
use App\Models\Drug;
use App\Models\DrugFractionLog;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Models\User;
use App\Services\Hospitalization\KardexAdministrationService;
use App\Support\Pharmacy\Quantity;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use LogicException;
use Tests\TestCase;

final class KardexAdministrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $nurse;

    private Hospitalization $stay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();
        app(TenantContext::class)->set($this->clinic);

        $this->nurse = User::factory()->for($this->clinic)->role(UserRole::Nurse)->create();
        $this->stay = Hospitalization::factory()->for($this->clinic)->create();
    }

    /** Resolved lazily so Event::fake() is already in place when the dispatcher is injected. */
    private function service(): KardexAdministrationService
    {
        return app(KardexAdministrationService::class);
    }

    private function drug(int $packages, string $open = '0.0000', array $overrides = []): Drug
    {
        return Drug::factory()->for($this->clinic)->stock($packages, $open)->create($overrides);
    }

    private function schedule(Drug $drug, string $dose): KardexSchedule
    {
        return KardexSchedule::factory()->create([
            'clinic_id' => $this->clinic->id,
            'hospitalization_id' => $this->stay->id,
            'drug_id' => $drug->id,
            'dose_amount' => $dose,
        ]);
    }

    public function test_it_depletes_the_open_balance_without_unsealing_when_sufficient(): void
    {
        $drug = $this->drug(packages: 3, open: '5.0000');
        $schedule = $this->schedule($drug, '1.2500');

        $this->service()->administerDose($schedule, $this->nurse->id, 'Sin reacciones');

        $drug->refresh();
        $this->assertSame(3, $drug->stock_packages);
        $this->assertSame('3.7500', $drug->open_fraction_balance);

        $this->assertSame(KardexStatus::Administered, $schedule->status);
        $this->assertSame($this->nurse->id, (int) $schedule->administered_by);
        $this->assertNotNull($schedule->administered_at);

        $log = DrugFractionLog::query()->sole();
        $this->assertSame(DrugMovementType::DoseAdministered, $log->movement_type);
        $this->assertSame('-1.2500', $log->fraction_delta);
        $this->assertSame('3.7500', $log->open_fraction_balance_after);
        $this->assertTrue($log->source->is($schedule));
    }

    public function test_it_automatically_unseals_a_package_when_the_open_balance_is_insufficient(): void
    {
        $drug = $this->drug(packages: 2, open: '0.3000');
        $schedule = $this->schedule($drug, '1.0000');

        $this->service()->administerDose($schedule, $this->nurse->id, null);

        $drug->refresh();
        $this->assertSame(1, $drug->stock_packages);
        // 0.3 + 10 (new vial) - 1.0
        $this->assertSame('9.3000', $drug->open_fraction_balance);
        $this->assertNotNull($drug->opened_at);

        $movements = DrugFractionLog::query()->orderBy('id')->get();
        $this->assertSame(
            [DrugMovementType::PackageOpened, DrugMovementType::DoseAdministered],
            $movements->pluck('movement_type')->all(),
        );
        $this->assertSame(-1, $movements[0]->packages_delta);
        $this->assertSame('10.0000', $movements[0]->fraction_delta);
        $this->assertSame('10.3000', $movements[0]->open_fraction_balance_after);
    }

    public function test_a_dose_larger_than_one_presentation_opens_several_packages(): void
    {
        $drug = $this->drug(packages: 3);
        $schedule = $this->schedule($drug, '15.0000');

        $this->service()->administerDose($schedule, $this->nurse->id, null);

        $drug->refresh();
        $this->assertSame(1, $drug->stock_packages);
        $this->assertSame('5.0000', $drug->open_fraction_balance);
        $this->assertSame(2, DrugFractionLog::query()->where('movement_type', DrugMovementType::PackageOpened)->count());
    }

    public function test_insufficient_stock_rolls_back_everything(): void
    {
        Event::fake([KardexUpdated::class]);

        $drug = $this->drug(packages: 1, open: '2.0000');
        $schedule = $this->schedule($drug, '12.5000');

        try {
            $this->service()->administerDose($schedule, $this->nurse->id, null);
            $this->fail('Expected InsufficientStockException.');
        } catch (InsufficientStockException $e) {
            $this->assertSame('12.5', $e->requested);
            $this->assertSame('12', $e->available);
        }

        $drug->refresh();
        $this->assertSame(1, $drug->stock_packages);
        $this->assertSame('2.0000', $drug->open_fraction_balance);
        $this->assertSame(KardexStatus::Pending, $schedule->fresh()->status);
        $this->assertSame(0, DrugFractionLog::query()->count());
        Event::assertNotDispatched(KardexUpdated::class);
    }

    public function test_a_double_tap_administers_and_deducts_only_once(): void
    {
        $drug = $this->drug(packages: 1, open: '5.0000');
        $schedule = $this->schedule($drug, '1.0000');
        $staleCopy = KardexSchedule::query()->findOrFail($schedule->id);

        $this->service()->administerDose($schedule, $this->nurse->id, null);

        $this->expectException(ScheduleNotAdministrableException::class);

        try {
            // Second tap arrives with a stale model still saying "pending".
            $this->service()->administerDose($staleCopy, $this->nurse->id, null);
        } finally {
            $this->assertSame('4.0000', $drug->fresh()->open_fraction_balance);
            $this->assertSame(1, DrugFractionLog::query()->count());
        }
    }

    public function test_micro_doses_accumulate_without_floating_point_drift(): void
    {
        $drug = $this->drug(packages: 1, open: '10.0000');

        // 800 × 0.0125 mL == exactly 10 mL. With floats this lands on 9.999999… or 10.000001.
        for ($i = 0; $i < 800; $i++) {
            $this->service()->administerDose($this->schedule($drug, '0.0125'), $this->nurse->id, null);
        }

        $drug->refresh();
        $this->assertSame(1, $drug->stock_packages);
        $this->assertSame('0.0000', $drug->open_fraction_balance);
        $this->assertNull($drug->opened_at, 'A fully consumed unit resets its stability clock.');

        $sum = DrugFractionLog::query()->pluck('fraction_delta')
            ->reduce(fn (Quantity $carry, string $d) => $carry->plus($d), Quantity::zero());
        $this->assertTrue($sum->isEqualTo('-10'));
    }

    public function test_an_expired_open_unit_is_discarded_as_waste_before_unsealing(): void
    {
        $drug = $this->drug(packages: 2, open: '6.0000', overrides: ['stability_hours_after_opening' => 24]);
        $drug->forceFill(['opened_at' => now()->subHours(25)])->save();

        $this->service()->administerDose($this->schedule($drug, '1.0000'), $this->nurse->id, null);

        $drug->refresh();
        $this->assertSame(1, $drug->stock_packages);
        $this->assertSame('9.0000', $drug->open_fraction_balance);
        $this->assertSame(
            [DrugMovementType::ExpiredWaste, DrugMovementType::PackageOpened, DrugMovementType::DoseAdministered],
            DrugFractionLog::query()->orderBy('id')->pluck('movement_type')->all(),
        );
        $this->assertSame('-6.0000', DrugFractionLog::query()->where('movement_type', DrugMovementType::ExpiredWaste)->value('fraction_delta'));
    }

    public function test_controlled_substances_require_notes(): void
    {
        $drug = Drug::factory()->for($this->clinic)->controlled()->stock(1)->create();

        $this->expectException(ScheduleNotAdministrableException::class);

        $this->service()->administerDose($this->schedule($drug, '0.3240'), $this->nurse->id, '   ');
    }

    public function test_it_refuses_to_administer_to_a_discharged_patient(): void
    {
        $drug = $this->drug(packages: 1);
        $schedule = $this->schedule($drug, '1.0000');
        $this->stay->update(['status' => HospitalizationStatus::Discharged]);

        $this->expectException(ScheduleNotAdministrableException::class);

        $this->service()->administerDose($schedule, $this->nurse->id, null);
    }

    public function test_discharge_cancels_pending_doses_via_observer(): void
    {
        Event::fake([KardexUpdated::class]);
        $schedule = $this->schedule($this->drug(packages: 1), '1.0000');

        $this->stay->update(['status' => HospitalizationStatus::Discharged]);

        $this->assertSame(KardexStatus::Cancelled, $schedule->fresh()->status);
        $this->assertNotNull($this->stay->fresh()->discharged_at);
        Event::assertDispatched(KardexUpdated::class, fn (KardexUpdated $e) => $e->action === 'cancelled');
    }

    public function test_receptionists_and_foreign_users_cannot_administer(): void
    {
        $drug = $this->drug(packages: 1);
        $receptionist = User::factory()->for($this->clinic)->role(UserRole::Receptionist)->create();
        $foreignNurse = User::factory()->role(UserRole::Nurse)->create(); // other clinic

        foreach ([$receptionist, $foreignNurse] as $user) {
            try {
                $this->service()->administerDose($this->schedule($drug, '1.0000'), $user->id, null);
                $this->fail('Expected AuthorizationException.');
            } catch (AuthorizationException) {
                // expected
            }
        }

        $this->assertSame(0, DrugFractionLog::query()->count());
    }

    public function test_it_broadcasts_kardex_updated_on_the_clinic_icu_channel(): void
    {
        Event::fake([KardexUpdated::class]);
        $drug = $this->drug(packages: 1, overrides: ['reorder_level_packages' => 1]);
        $schedule = $this->schedule($drug, '1.0000');

        $this->service()->administerDose($schedule, $this->nurse->id, null);

        Event::assertDispatched(KardexUpdated::class, function (KardexUpdated $event) use ($schedule): bool {
            $payload = $event->broadcastWith();

            return $event->broadcastOn()[0]->name === "private-clinic.{$this->clinic->id}.icu"
                && $payload['schedule_id'] === $schedule->id
                && $payload['status'] === 'administered'
                && $payload['drug']['open_fraction_balance'] === '9.0000'
                && $payload['drug']['low_stock'] === true;
        });
    }

    public function test_the_ledger_is_append_only(): void
    {
        $drug = $this->drug(packages: 1, open: '5.0000');
        $this->service()->administerDose($this->schedule($drug, '1.0000'), $this->nurse->id, null);

        $this->expectException(LogicException::class);

        DrugFractionLog::query()->firstOrFail()->update(['notes' => 'tampered']);
    }
}
