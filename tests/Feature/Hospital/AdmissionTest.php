<?php

declare(strict_types=1);

namespace Tests\Feature\Hospital;

use App\Enums\HospitalizationStatus;
use App\Enums\KardexStatus;
use App\Enums\TriageLevel;
use App\Enums\UserRole;
use App\Events\KardexUpdated;
use App\Livewire\Hospital\AdmissionEditor;
use App\Livewire\Hospital\PatientShow;
use App\Models\Clinic;
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

final class AdmissionTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $vet;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-13 15:00:00', 'America/Bogota'));
        $this->clinic = Clinic::factory()->create();
        $this->vet = User::factory()->for($this->clinic)->role(UserRole::Veterinarian)->create(['name' => 'Dra. Restrepo']);
        app(TenantContext::class)->set($this->clinic);
        $this->patient = Patient::factory()->for($this->clinic)->create(['name' => 'Rocky']);
    }

    public function test_a_vet_can_admit_a_patient_with_weight_triage_and_bed(): void
    {
        Livewire::actingAs($this->vet)->test(AdmissionEditor::class, ['patient' => $this->patient])
            ->assertSet('form.attending_vet_id', (string) $this->vet->id)
            ->set('form.current_weight_kg', '32,4')
            ->set('form.triage_level', TriageLevel::Critical->value)
            ->set('form.bed_label', 'UCI-1')
            ->set('form.admission_reason', 'Politraumatismo por atropellamiento')
            ->set('form.admitted_at', '2026-10-13T08:30')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('hospital.patients.show', $this->patient));

        $stay = Hospitalization::query()->sole();
        $this->assertSame('32.400', $stay->current_weight_kg);
        $this->assertSame(TriageLevel::Critical, $stay->triage_level);
        $this->assertSame('UCI-1', $stay->bed_label);
        $this->assertSame($this->vet->id, (int) $stay->attending_vet_id);
        $this->assertSame(HospitalizationStatus::Admitted, $stay->status);
        // 08:30 Bogotá == 13:30 UTC
        $this->assertSame('2026-10-13 13:30:00', $stay->admitted_at->toDateTimeString());
    }

    public function test_weight_and_reason_are_validated(): void
    {
        Livewire::actingAs($this->vet)->test(AdmissionEditor::class, ['patient' => $this->patient])
            ->set('form.current_weight_kg', 'abc')
            ->set('form.admission_reason', 'x')
            ->call('save')
            ->assertHasErrors(['form.current_weight_kg', 'form.admission_reason']);

        $this->assertSame(0, Hospitalization::query()->count());
    }

    public function test_a_patient_cannot_be_admitted_twice(): void
    {
        Hospitalization::factory()->for($this->clinic)->for($this->patient)->create();

        Livewire::actingAs($this->vet)->test(AdmissionEditor::class, ['patient' => $this->patient])
            ->assertSee('ya está hospitalizado')
            ->set('form.current_weight_kg', '10')
            ->set('form.admission_reason', 'Segundo ingreso simultáneo')
            ->call('save')
            ->assertHasErrors(['form.admission_reason']);

        $this->assertSame(1, Hospitalization::query()->count());
    }

    public function test_receptionists_cannot_admit(): void
    {
        $receptionist = User::factory()->for($this->clinic)->role(UserRole::Receptionist)->create();

        $this->actingAs($receptionist)->get(route('hospital.patients.admit', $this->patient))->assertForbidden();
    }

    public function test_discharging_from_the_record_cancels_pending_doses(): void
    {
        Event::fake([KardexUpdated::class]);
        $stay = Hospitalization::factory()->for($this->clinic)->for($this->patient)->create(['bed_label' => 'UCI-2']);
        $dose = KardexSchedule::factory()->create(['clinic_id' => $this->clinic->id, 'hospitalization_id' => $stay->id]);

        Livewire::actingAs($this->vet)->test(PatientShow::class, ['patient' => $this->patient])
            ->assertSee('Hospitalización en curso')
            ->assertSee('UCI-2')
            ->call('openDischarge')
            ->assertSet('showDischarge', true)
            ->set('outcome', HospitalizationStatus::Discharged->value)
            ->set('dischargeNotes', 'Control en 5 días.')
            ->call('discharge')
            ->assertHasNoErrors()
            ->assertSet('showDischarge', false)
            ->assertSee('Sin hospitalización activa')
            ->assertSee('Hospitalización cerrada: Alta médica');

        $this->assertSame(HospitalizationStatus::Discharged, $stay->fresh()->status);
        $this->assertNotNull($stay->fresh()->discharged_at);
        $this->assertSame(KardexStatus::Cancelled, $dose->fresh()->status);
        Event::assertDispatched(KardexUpdated::class);
    }

    public function test_the_record_offers_admission_only_when_there_is_no_active_stay(): void
    {
        $this->actingAs($this->vet)->get(route('hospital.patients.show', $this->patient))
            ->assertOk()->assertSee('Ingresar a hospitalización');

        Hospitalization::factory()->for($this->clinic)->for($this->patient)->create();

        $this->actingAs($this->vet)->get(route('hospital.patients.show', $this->patient))
            ->assertOk()->assertDontSee('Ingresar a hospitalización')->assertSee('Ver kárdex');
    }
}
