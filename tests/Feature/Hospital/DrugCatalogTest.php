<?php

declare(strict_types=1);

namespace Tests\Feature\Hospital;

use App\Enums\DrugMovementType;
use App\Enums\UserRole;
use App\Livewire\Hospital\DrugEditor;
use App\Livewire\Hospital\DrugIndex;
use App\Livewire\Hospital\DrugShow;
use App\Models\Clinic;
use App\Models\Drug;
use App\Models\DrugFractionLog;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Services\Hospitalization\KardexAdministrationService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class DrugCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $vet;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();
        app(TenantContext::class)->set($this->clinic);
        $this->vet = User::factory()->for($this->clinic)->role(UserRole::Veterinarian)->create();
        $this->receptionist = User::factory()->for($this->clinic)->role(UserRole::Receptionist)->create();
    }

    public function test_a_vet_creates_a_drug_and_the_opening_stock_goes_through_the_ledger(): void
    {
        Livewire::actingAs($this->vet)->test(DrugEditor::class)
            ->set('form.name', 'Ketamina 50 mg/mL')
            ->set('form.active_ingredient', 'Ketamina')
            ->set('form.presentation_unit', 'vial')
            ->set('form.volume_per_presentation', '10')
            ->set('form.fraction_unit', 'mL')
            ->set('form.concentration_amount', '50')
            ->set('form.concentration_unit', 'mg')
            ->set('form.stability_hours_after_opening', '672')
            ->set('form.is_controlled', true)
            ->set('form.initial_packages', '5')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('hospital.drugs.show', Drug::query()->firstOrFail()));

        $drug = Drug::query()->sole();
        $this->assertSame(5, $drug->stock_packages);
        $this->assertSame('10.0000', $drug->volume_per_presentation);
        $this->assertSame('50.0000', $drug->concentration_amount);
        $this->assertTrue($drug->is_controlled);
        $this->assertTrue($drug->is_active);

        $entry = DrugFractionLog::query()->sole();
        $this->assertSame(DrugMovementType::PackageReceived, $entry->movement_type);
        $this->assertSame(5, $entry->packages_delta);
        $this->assertSame(5, $entry->stock_packages_after);
    }

    public function test_decimal_commas_are_accepted_and_bad_numbers_rejected(): void
    {
        Livewire::actingAs($this->vet)->test(DrugEditor::class)
            ->set('form.name', 'Buprenorfina')
            ->set('form.presentation_unit', 'ampolla')
            ->set('form.volume_per_presentation', '1,5')
            ->set('form.concentration_amount', '0,3')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('1.5000', Drug::query()->value('volume_per_presentation'));
        $this->assertSame('0.3000', Drug::query()->value('concentration_amount'));

        Livewire::actingAs($this->vet)->test(DrugEditor::class)
            ->set('form.name', 'X')
            ->set('form.presentation_unit', 'vial')
            ->set('form.volume_per_presentation', '1.250,5')
            ->set('form.concentration_amount', '0')
            ->call('save')
            ->assertHasErrors(['form.volume_per_presentation', 'form.concentration_amount']);
    }

    public function test_units_are_frozen_once_the_drug_has_movements(): void
    {
        $drug = Drug::factory()->for($this->clinic)->create(['volume_per_presentation' => '10.0000', 'fraction_unit' => 'mL']);

        // No movements yet: units can change.
        Livewire::actingAs($this->vet)->test(DrugEditor::class, ['drug' => $drug])
            ->set('form.volume_per_presentation', '20')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('20.0000', $drug->fresh()->volume_per_presentation);

        Livewire::actingAs($this->vet)->test(DrugShow::class, ['drug' => $drug])
            ->call('openReceive')->set('packages', '2')->call('receive');

        Livewire::actingAs($this->vet)->test(DrugEditor::class, ['drug' => $drug->fresh()])
            ->assertSee('no se pueden cambiar')
            ->set('form.volume_per_presentation', '5')
            ->set('form.fraction_unit', 'tab')
            ->set('form.name', 'Renombrado')
            ->call('save')->assertHasNoErrors();

        $fresh = $drug->fresh();
        $this->assertSame('20.0000', $fresh->volume_per_presentation);
        $this->assertSame('mL', $fresh->fraction_unit);
        $this->assertSame('Renombrado', $fresh->name);
    }

    public function test_receiving_stock_adds_sealed_packages_and_a_ledger_entry(): void
    {
        $drug = Drug::factory()->for($this->clinic)->stock(1)->create(['presentation_unit' => 'vial']);

        Livewire::actingAs($this->vet)->test(DrugShow::class, ['drug' => $drug])
            ->call('openReceive')
            ->set('packages', '0')
            ->call('receive')
            ->assertHasErrors(['packages'])
            ->set('packages', '12')
            ->set('receiveNotes', 'Lote 23A14, factura 4512')
            ->call('receive')
            ->assertHasNoErrors()
            ->assertSet('showReceive', false)
            ->assertSee('Entrada registrada: 12 vial')
            ->assertSee('Lote 23A14, factura 4512');

        $this->assertSame(13, $drug->fresh()->stock_packages);
        $this->assertSame(12, DrugFractionLog::query()->sole()->packages_delta);
    }

    public function test_the_ledger_shows_whose_dose_each_administration_was(): void
    {
        $drug = Drug::factory()->for($this->clinic)->stock(1)->create();
        $patient = Patient::factory()->for($this->clinic)->create(['name' => 'Rocky']);
        $stay = Hospitalization::factory()->for($this->clinic)->for($patient)->create(['bed_label' => 'UCI-1']);
        $nurse = User::factory()->for($this->clinic)->role(UserRole::Nurse)->create(['name' => 'Aux. Gómez']);
        $schedule = KardexSchedule::factory()->create(['clinic_id' => $this->clinic->id, 'hospitalization_id' => $stay->id, 'drug_id' => $drug->id]);
        app(KardexAdministrationService::class)->administerDose($schedule, $nurse->id, null);

        $this->actingAs($this->receptionist)->get(route('hospital.drugs.show', $drug))
            ->assertOk()
            ->assertSee('Administración')
            ->assertSee('Apertura')
            ->assertSeeText('Rocky · UCI-1')
            ->assertSee('Aux. Gómez');
    }

    public function test_receptionists_can_consult_but_not_change_the_catalog(): void
    {
        $drug = Drug::factory()->for($this->clinic)->create();

        $this->actingAs($this->receptionist)->get(route('hospital.drugs.index'))->assertOk()->assertDontSee('Nuevo medicamento');
        $this->actingAs($this->receptionist)->get(route('hospital.drugs.show', $drug))->assertOk()->assertDontSee('Registrar entrada');
        $this->actingAs($this->receptionist)->get(route('hospital.drugs.create'))->assertForbidden();
        $this->actingAs($this->receptionist)->get(route('hospital.drugs.edit', $drug))->assertForbidden();

        Livewire::actingAs($this->receptionist)->test(DrugShow::class, ['drug' => $drug])->call('openReceive')->assertForbidden();
    }

    public function test_the_list_filters_low_stock_and_hides_other_clinics(): void
    {
        Drug::factory()->for($this->clinic)->create(['name' => 'Meloxicam', 'stock_packages' => 1, 'reorder_level_packages' => 2]);
        Drug::factory()->for($this->clinic)->create(['name' => 'Ceftriaxona', 'stock_packages' => 9, 'reorder_level_packages' => 2]);
        $other = Clinic::factory()->create();
        $foreign = app(TenantContext::class)->run($other, fn () => Drug::factory()->for($other)->create(['name' => 'Zeusmicina']));

        Livewire::actingAs($this->vet)->test(DrugIndex::class)
            ->assertSee('Meloxicam')->assertSee('Ceftriaxona')->assertDontSee('Zeusmicina')
            ->set('filter', 'stock-bajo')
            ->assertSee('Meloxicam')->assertDontSee('Ceftriaxona');

        $this->actingAs($this->vet)->get(route('hospital.drugs.show', $foreign))->assertNotFound();
    }
}
