<?php

declare(strict_types=1);

namespace Tests\Feature\Hospital;

use App\Enums\UserRole;
use App\Livewire\Hospital\PatientEditor;
use App\Livewire\Hospital\PatientIndex;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();
        $this->receptionist = User::factory()->for($this->clinic)->role(UserRole::Receptionist)->create();
        app(TenantContext::class)->set($this->clinic);
    }

    public function test_the_patient_list_renders_and_searches_by_owner_and_microchip(): void
    {
        Patient::factory()->for($this->clinic)->create(['name' => 'Rocky', 'owner_name' => 'Carlos Pérez', 'microchip' => '9001']);
        Patient::factory()->for($this->clinic)->create(['name' => 'Mía', 'owner_name' => 'Ana Gómez', 'microchip' => '9002']);

        $this->actingAs($this->receptionist)->get(route('hospital.patients.index'))->assertOk()->assertSee('Rocky')->assertSee('Mía');

        Livewire::actingAs($this->receptionist)->test(PatientIndex::class)
            ->set('search', 'Gómez')->assertSee('Mía')->assertDontSee('Rocky')
            ->set('search', '9001')->assertSee('Rocky')->assertDontSee('Mía');
    }

    public function test_patients_from_other_clinics_are_never_listed_or_reachable(): void
    {
        $other = Clinic::factory()->create();
        $foreign = app(TenantContext::class)->run($other, fn () => Patient::factory()->for($other)->create(['name' => 'Zeus']));

        Livewire::actingAs($this->receptionist)->test(PatientIndex::class)->assertDontSee('Zeus');
        $this->actingAs($this->receptionist)->get(route('hospital.patients.show', $foreign))->assertNotFound();
    }

    public function test_a_receptionist_can_register_a_patient_and_is_sent_to_its_record(): void
    {
        Livewire::actingAs($this->receptionist)->test(PatientEditor::class)
            ->set('form.name', 'Luna')
            ->set('form.species', 'Canino')
            ->set('form.breed', 'Criollo')
            ->set('form.sex', 'H')
            ->set('form.birth_date', now()->subYears(3)->toDateString())
            ->set('form.microchip', '900182000123456')
            ->set('form.owner_name', 'María López')
            ->set('form.owner_document', '1020304050')
            ->set('form.owner_phone', '300 123 4567')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('hospital.patients.show', Patient::query()->firstOrFail()));

        $patient = Patient::query()->firstOrFail();
        $this->assertSame($this->clinic->id, (int) $patient->clinic_id);
        $this->assertSame('Luna', $patient->name);
        $this->assertSame('3 años', $patient->age());
    }

    public function test_saving_with_the_admission_shortcut_redirects_to_the_admission_form(): void
    {
        Livewire::actingAs($this->receptionist)->test(PatientEditor::class)
            ->set('form.name', 'Toby')->set('form.species', 'Canino')->set('form.owner_name', 'Juan Ruiz')
            ->set('next', 'ingreso')
            ->call('save')
            ->assertRedirect(route('hospital.patients.admit', Patient::query()->firstOrFail()));
    }

    public function test_validation_requires_name_species_and_owner_and_unique_microchip_per_clinic(): void
    {
        Patient::factory()->for($this->clinic)->create(['microchip' => 'ABC-1']);
        // Same microchip in another clinic must not collide.
        $other = Clinic::factory()->create();
        app(TenantContext::class)->run($other, fn () => Patient::factory()->for($other)->create(['microchip' => 'ABC-2']));

        Livewire::actingAs($this->receptionist)->test(PatientEditor::class)
            ->set('form.microchip', 'ABC-1')
            ->call('save')
            ->assertHasErrors(['form.name' => 'required', 'form.species' => 'required', 'form.owner_name' => 'required', 'form.microchip' => 'unique']);

        Livewire::actingAs($this->receptionist)->test(PatientEditor::class)
            ->set('form.name', 'Kira')->set('form.species', 'Felino')->set('form.owner_name', 'Pedro')
            ->set('form.microchip', 'ABC-2')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_editing_keeps_the_patients_own_microchip_valid(): void
    {
        $patient = Patient::factory()->for($this->clinic)->create(['name' => 'Simón', 'microchip' => 'X-1']);

        Livewire::actingAs($this->receptionist)->test(PatientEditor::class, ['patient' => $patient])
            ->assertSet('form.name', 'Simón')
            ->set('form.breed', 'Siamés')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('hospital.patients.show', $patient));

        $this->assertSame('Siamés', $patient->fresh()->breed);
    }

    public function test_validation_messages_are_in_spanish(): void
    {
        Livewire::actingAs($this->receptionist)->test(PatientEditor::class)
            ->call('save')
            ->assertSee('El campo nombre es obligatorio.')
            ->assertSee('El campo nombre del propietario es obligatorio.');
    }
}
