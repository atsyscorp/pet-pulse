<?php

declare(strict_types=1);

namespace Tests\Feature\Hospital;

use App\Enums\AdministrationRoute;
use App\Enums\DoseMode;
use App\Enums\HospitalizationStatus;
use App\Enums\KardexStatus;
use App\Enums\PrescriptionStatus;
use App\Enums\UserRole;
use App\Events\KardexUpdated;
use App\Exceptions\Prescription\PrescriptionNotAllowedException;
use App\Livewire\Hospital\PatientShow;
use App\Livewire\Hospital\PrescriptionEditor;
use App\Models\Clinic;
use App\Models\Drug;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Hospitalization\PrescribeMedicationService;
use App\Services\Hospitalization\PrescriptionPlan;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

final class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $vet;

    private User $nurse;

    private Patient $patient;

    private Hospitalization $stay;

    private Drug $buprenorphine;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00', 'America/Bogota'));
        $this->clinic = Clinic::factory()->create();
        app(TenantContext::class)->set($this->clinic);

        $this->vet = User::factory()->for($this->clinic)->role(UserRole::Veterinarian)->create();
        $this->nurse = User::factory()->for($this->clinic)->role(UserRole::Nurse)->create();
        $this->patient = Patient::factory()->for($this->clinic)->create(['name' => 'Mía', 'species' => 'Felino']);
        $this->stay = Hospitalization::factory()->for($this->clinic)->for($this->patient)->create(['current_weight_kg' => '3.850', 'bed_label' => 'UCI-2']);
        $this->buprenorphine = Drug::factory()->for($this->clinic)->stock(10)->create([
            'name' => 'Buprenorfina 0.3 mg/mL', 'concentration_amount' => '0.3000', 'concentration_unit' => 'mg',
            'presentation_unit' => 'ampolla', 'volume_per_presentation' => '1.0000', 'fraction_unit' => 'mL',
        ]);
    }

    private function plan(array $overrides = []): PrescriptionPlan
    {
        $args = array_merge([
            'drug' => $this->buprenorphine,
            'mode' => DoseMode::PerKg,
            'dosePerKg' => '0.02',
            'fixedAmount' => null,
            'weightKg' => '3.850',
            'route' => AdministrationRoute::Intravenous,
            'frequencyHours' => 6,
            'startsAt' => Carbon::parse('2026-10-13 12:00', 'America/Bogota'),
            'durationHours' => 24,
        ], $overrides);

        return PrescriptionPlan::make(...$args);
    }

    public function test_a_per_kg_prescription_expands_into_kardex_rows_with_the_exact_dose(): void
    {
        Event::fake([KardexUpdated::class]);

        $prescription = app(PrescribeMedicationService::class)->prescribe($this->stay, $this->plan(), $this->vet->id, 'Diluir en 1 mL SSN');

        // 0.02 mg/kg × 3.85 kg ÷ 0.3 mg/mL = 0.25666… → 0.2567 mL
        $this->assertSame('0.2567', $prescription->dose_amount);
        $this->assertSame(4, $prescription->doses_planned);
        $this->assertSame(PrescriptionStatus::Active, $prescription->status);

        $rows = KardexSchedule::query()->orderBy('scheduled_at')->get();
        $this->assertCount(4, $rows);
        $this->assertSame(
            ['12:00', '18:00', '00:00', '06:00'],
            $rows->map(fn ($r) => $r->scheduled_at->setTimezone('America/Bogota')->format('H:i'))->all(),
        );
        $rows->each(function (KardexSchedule $r) use ($prescription): void {
            $this->assertSame($prescription->id, (int) $r->prescription_id);
            $this->assertSame('0.2567', $r->dose_amount);
            $this->assertSame('0.0200', $r->dose_per_kg);
            $this->assertSame(KardexStatus::Pending, $r->status);
            $this->assertSame(AdministrationRoute::Intravenous, $r->route);
            $this->assertSame($this->vet->id, (int) $r->prescribed_by);
            $this->assertSame($this->clinic->id, (int) $r->clinic_id);
        });

        // One realtime signal per order, not one per dose.
        Event::assertDispatchedTimes(KardexUpdated::class, 1);
    }

    public function test_a_single_fixed_dose_creates_one_row(): void
    {
        $plan = $this->plan(['mode' => DoseMode::Fixed, 'dosePerKg' => null, 'fixedAmount' => '0.5', 'weightKg' => null, 'frequencyHours' => null, 'durationHours' => null]);

        app(PrescribeMedicationService::class)->prescribe($this->stay, $plan, $this->vet->id, null);

        $this->assertSame(1, KardexSchedule::query()->count());
        $this->assertSame('0.5000', KardexSchedule::query()->value('dose_amount'));
        $this->assertNull(Prescription::query()->value('frequency_hours'));
    }

    public function test_prescribing_to_a_closed_stay_or_an_inactive_drug_is_refused(): void
    {
        $this->buprenorphine->update(['is_active' => false]);

        try {
            app(PrescribeMedicationService::class)->prescribe($this->stay, $this->plan(), $this->vet->id, null);
            $this->fail('Expected inactive drug to be refused.');
        } catch (PrescriptionNotAllowedException) {
        }

        $this->buprenorphine->update(['is_active' => true]);
        $this->stay->update(['status' => HospitalizationStatus::Discharged]);

        $this->expectException(PrescriptionNotAllowedException::class);
        app(PrescribeMedicationService::class)->prescribe($this->stay->fresh(), $this->plan(), $this->vet->id, null);
    }

    public function test_a_vet_prescribes_from_the_form_and_sees_the_live_preview(): void
    {
        Livewire::actingAs($this->vet)->test(PrescriptionEditor::class, ['hospitalization' => $this->stay])
            ->set('drug_id', (string) $this->buprenorphine->id)
            ->set('dose_mode', 'per_kg')
            ->set('dose_per_kg', '0,02')
            ->set('route', 'IV')
            ->set('frequency_hours', '8')
            ->set('duration_days', '2')
            ->set('starts_at', '2026-10-13T12:00')
            ->assertSee('0,2567')                 // dose per administration
            ->assertSee('Dosis programadas')
            ->assertSeeInOrder(['Dosis programadas', '6'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('hospital.patients.show', $this->patient));

        $this->assertSame(6, KardexSchedule::query()->count());
        $this->assertSame(1, Prescription::query()->count());
    }

    public function test_the_preview_warns_when_stock_does_not_cover_the_treatment(): void
    {
        $this->buprenorphine->update(['stock_packages' => 0, 'open_fraction_balance' => '0.3000']);

        Livewire::actingAs($this->vet)->test(PrescriptionEditor::class, ['hospitalization' => $this->stay])
            ->set('drug_id', (string) $this->buprenorphine->id)
            ->set('dose_per_kg', '0,02')
            ->set('frequency_hours', '6')
            ->set('duration_days', '1')
            ->assertSee('no alcanza para todo el tratamiento');
    }

    public function test_a_drug_without_concentration_switches_to_fixed_dose(): void
    {
        $noConcentration = Drug::factory()->for($this->clinic)->create(['name' => 'Omeprazol 20 mg tab', 'concentration_amount' => null, 'fraction_unit' => 'tab']);

        Livewire::actingAs($this->vet)->test(PrescriptionEditor::class, ['hospitalization' => $this->stay])
            ->set('dose_mode', 'per_kg')
            ->set('drug_id', (string) $noConcentration->id)
            ->assertSet('dose_mode', 'fixed')
            ->set('dose_mode', 'per_kg')
            ->set('dose_per_kg', '1')
            ->set('route', 'PO')
            ->call('save')
            ->assertHasErrors(['dose_mode']);
    }

    public function test_validation_rejects_tiny_doses_and_stale_start_times(): void
    {
        Livewire::actingAs($this->vet)->test(PrescriptionEditor::class, ['hospitalization' => $this->stay])
            ->set('drug_id', (string) $this->buprenorphine->id)
            ->set('dose_per_kg', '0,0001')   // 0.0001 × 3.85 ÷ 0.3 = 0.00128 → 0.0013 OK
            ->set('route', 'IV')
            ->set('starts_at', '2026-10-12T20:00') // 14 h ago
            ->call('save')
            ->assertHasErrors(['starts_at']);

        $tiny = Drug::factory()->for($this->clinic)->create(['concentration_amount' => '500.0000', 'concentration_unit' => 'mg']);

        Livewire::actingAs($this->vet)->test(PrescriptionEditor::class, ['hospitalization' => $this->stay])
            ->set('drug_id', (string) $tiny->id)
            ->set('dose_per_kg', '0,0001')   // 0.0001 × 3.85 ÷ 500 → rounds to 0
            ->set('route', 'IV')
            ->call('save')
            ->assertHasErrors(['dose_per_kg']);

        $this->assertSame(0, Prescription::query()->count());
    }

    public function test_nurses_cannot_open_the_prescription_form(): void
    {
        $this->actingAs($this->nurse)->get(route('hospital.stays.prescribe', $this->stay))->assertForbidden();
        $this->actingAs($this->vet)->get(route('hospital.stays.prescribe', $this->stay))->assertOk();
    }

    public function test_suspending_cancels_pending_doses_and_keeps_administered_ones(): void
    {
        $prescription = app(PrescribeMedicationService::class)->prescribe(
            $this->stay,
            $this->plan(['startsAt' => Carbon::parse('2026-10-13 04:00', 'America/Bogota')]),   // 04, 10, 16, 22
            $this->vet->id,
            null,
        );
        $rows = KardexSchedule::query()->orderBy('scheduled_at')->get();
        $rows[0]->forceFill(['status' => KardexStatus::Administered, 'administered_at' => now(), 'administered_by' => $this->nurse->id])->save();

        Livewire::actingAs($this->vet)->test(PatientShow::class, ['patient' => $this->patient])
            ->assertSee('Plan de medicación')
            ->assertSee('Buprenorfina 0.3 mg/mL')
            ->assertSee('1/4 administradas')
            ->call('openSuspend', $prescription->id)
            ->call('suspend')
            ->assertHasErrors(['suspensionReason' => 'required'])
            ->set('suspensionReason', 'Dolor controlado, se cambia a meloxicam')
            ->call('suspend')
            ->assertHasNoErrors()
            ->assertSet('suspendingId', null)
            ->assertSee('3 dosis pendientes canceladas');

        $this->assertSame(PrescriptionStatus::Suspended, $prescription->fresh()->status);
        $this->assertSame('Dolor controlado, se cambia a meloxicam', $prescription->fresh()->suspension_reason);
        $this->assertSame(
            [KardexStatus::Administered, KardexStatus::Cancelled, KardexStatus::Cancelled, KardexStatus::Cancelled],
            KardexSchedule::query()->orderBy('scheduled_at')->pluck('status')->all(),
        );
    }

    public function test_nurses_cannot_suspend_a_prescription(): void
    {
        $prescription = app(PrescribeMedicationService::class)->prescribe($this->stay, $this->plan(), $this->vet->id, null);

        Livewire::actingAs($this->nurse)->test(PatientShow::class, ['patient' => $this->patient])
            ->assertDontSee('Suspender</button>', false)
            ->call('openSuspend', $prescription->id)
            ->assertForbidden();
    }

    public function test_discharge_completes_active_prescriptions(): void
    {
        $prescription = app(PrescribeMedicationService::class)->prescribe($this->stay, $this->plan(), $this->vet->id, null);

        $this->stay->update(['status' => HospitalizationStatus::Discharged]);

        $this->assertSame(PrescriptionStatus::Completed, $prescription->fresh()->status);
        $this->assertSame(0, KardexSchedule::query()->where('status', KardexStatus::Pending)->count());
    }

    public function test_the_kardex_board_links_each_patient_to_their_record(): void
    {
        app(PrescribeMedicationService::class)->prescribe($this->stay, $this->plan(), $this->vet->id, null);

        $this->actingAs($this->nurse)->get(route('hospital.kardex'))
            ->assertOk()
            ->assertSee(route('hospital.patients.show', $this->patient), false)
            ->assertSee('0,2567');
    }
}
