<?php

declare(strict_types=1);

namespace Tests\Feature\Hospital;

use App\Enums\KardexStatus;
use App\Enums\TriageLevel;
use App\Enums\UserRole;
use App\Events\KardexUpdated;
use App\Livewire\Hospital\KardexBoard;
use App\Models\Clinic;
use App\Models\Drug;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

final class KardexBoardTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $nurse;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-27 15:00:00', 'America/Bogota'));
        $this->clinic = Clinic::factory()->create();
        $this->nurse = User::factory()->for($this->clinic)->role(UserRole::Nurse)->create();
        app(TenantContext::class)->set($this->clinic);
    }

    private function scheduleFor(string $patientName, TriageLevel $triage, string $localTime, array $drugState = []): KardexSchedule
    {
        $patient = Patient::factory()->for($this->clinic)->create(['name' => $patientName]);
        $stay = Hospitalization::factory()->for($this->clinic)->for($patient)->triage($triage)->create();
        $drug = Drug::factory()->for($this->clinic)->create(['name' => "Drug for {$patientName}", ...$drugState]);

        return KardexSchedule::factory()->create([
            'clinic_id' => $this->clinic->id,
            'hospitalization_id' => $stay->id,
            'drug_id' => $drug->id,
            'scheduled_at' => Carbon::parse("2026-09-27 {$localTime}", 'America/Bogota')->utc(),
        ]);
    }

    public function test_the_page_renders_for_authenticated_clinical_staff(): void
    {
        $this->scheduleFor('Rocky', TriageLevel::Critical, '14:00');

        $this->actingAs($this->nurse)
            ->get(route('hospital.kardex'))
            ->assertOk()
            ->assertSeeLivewire(KardexBoard::class)
            ->assertSee('Rocky')
            ->assertSee('Crítico / UCI');
    }

    public function test_it_orders_patients_by_triage_and_hides_other_clinics(): void
    {
        $this->scheduleFor('Luna', TriageLevel::Observation, '10:00');
        $this->scheduleFor('Rocky', TriageLevel::Critical, '10:00');
        $other = Clinic::factory()->create();
        app(TenantContext::class)->run($other, fn () => KardexSchedule::factory()->create([
            'hospitalization_id' => Hospitalization::factory()->for($other)->for(Patient::factory()->for($other)->state(['name' => 'Zeus'])),
        ]));

        Livewire::actingAs($this->nurse)
            ->test(KardexBoard::class)
            ->assertSeeInOrder(['Rocky', 'Luna'])
            ->assertDontSee('Zeus')
            ->assertViewHas('triageLevels')
            ->tap(fn ($c) => $this->assertCount(2, $c->instance()->board));
    }

    public function test_summary_flags_overdue_and_due_soon_doses(): void
    {
        $this->scheduleFor('Rocky', TriageLevel::Critical, '14:00');   // 60 min late → overdue
        $this->scheduleFor('Mía', TriageLevel::Critical, '15:20');     // due within 30 min
        $this->scheduleFor('Toby', TriageLevel::Intermediate, '20:00'); // pending

        $summary = Livewire::actingAs($this->nurse)->test(KardexBoard::class)->instance()->summary;

        $this->assertSame(['total' => 3, 'administered' => 0, 'pending' => 3, 'due' => 1, 'overdue' => 1], $summary);
    }

    public function test_mark_as_administered_invokes_the_domain_service(): void
    {
        Event::fake([KardexUpdated::class]);
        $schedule = $this->scheduleFor('Rocky', TriageLevel::Critical, '15:00', ['stock_packages' => 1, 'open_fraction_balance' => '0.0000']);

        Livewire::actingAs($this->nurse)
            ->test(KardexBoard::class)
            ->call('markAsAdministered', $schedule->id, 'Vía IV periférica OK')
            ->assertDispatched('kardex-toast', type: 'success');

        $this->assertSame(KardexStatus::Administered, $schedule->fresh()->status);
        $this->assertSame('8.8000', Drug::query()->findOrFail($schedule->drug_id)->open_fraction_balance);
        Event::assertDispatched(KardexUpdated::class);
    }

    public function test_domain_errors_are_reported_as_toasts_not_exceptions(): void
    {
        $schedule = $this->scheduleFor('Rocky', TriageLevel::Critical, '15:00', ['stock_packages' => 0]);

        Livewire::actingAs($this->nurse)
            ->test(KardexBoard::class)
            ->call('markAsAdministered', $schedule->id)
            ->assertDispatched('kardex-toast', type: 'error');

        $this->assertSame(KardexStatus::Pending, $schedule->fresh()->status);
    }

    public function test_receptionists_are_forbidden_from_administering(): void
    {
        $schedule = $this->scheduleFor('Rocky', TriageLevel::Critical, '15:00');
        $receptionist = User::factory()->for($this->clinic)->role(UserRole::Receptionist)->create();

        Livewire::actingAs($receptionist)
            ->test(KardexBoard::class)
            ->call('markAsAdministered', $schedule->id)
            ->assertForbidden();
    }

    public function test_echo_event_refreshes_the_board(): void
    {
        $component = Livewire::actingAs($this->nurse)->test(KardexBoard::class)->assertDontSee('Nala');

        $this->scheduleFor('Nala', TriageLevel::Intermediate, '16:00');

        $component
            ->dispatch("echo-private:clinic.{$this->clinic->id}.icu,KardexUpdated", ['drug' => ['name' => 'X', 'low_stock' => true, 'stock_packages' => 0]])
            ->assertSee('Nala')
            ->assertDispatched('kardex-toast', type: 'warning')
            ->assertSet('lastSyncAt', fn ($v) => $v !== null);
    }
}
