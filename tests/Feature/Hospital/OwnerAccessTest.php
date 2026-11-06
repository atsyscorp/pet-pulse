<?php

declare(strict_types=1);

namespace Tests\Feature\Hospital;

use App\Enums\OwnerVisibleStatus;
use App\Enums\UserRole;
use App\Livewire\Hospital\PatientShow;
use App\Models\Clinic;
use App\Models\Hospitalization;
use App\Models\Owner;
use App\Models\OwnerUpdate;
use App\Models\Patient;
use App\Models\PatientLinkCode;
use App\Models\User;
use App\Services\OwnerApp\OwnerAccessService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class OwnerAccessTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $nurse;

    private User $receptionist;

    private Patient $patient;

    private Hospitalization $stay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clinic = Clinic::factory()->create();
        app(TenantContext::class)->set($this->clinic);
        $this->nurse = User::factory()->for($this->clinic)->role(UserRole::Nurse)->create(['name' => 'Aux. Gómez']);
        $this->receptionist = User::factory()->for($this->clinic)->role(UserRole::Receptionist)->create();
        $this->patient = Patient::factory()->for($this->clinic)->create(['name' => 'Luna']);
        $this->stay = Hospitalization::factory()->for($this->clinic)->for($this->patient)->create();
    }

    public function test_reception_generates_a_code_with_qr_that_is_stored_only_as_a_hash(): void
    {
        $component = Livewire::actingAs($this->receptionist)->test(PatientShow::class, ['patient' => $this->patient])
            ->call('issueLinkCode')
            ->assertSee('Código de vinculación')
            ->assertSee('<svg', false);

        $code = $component->get('issuedCode');
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/', $code);

        $stored = PatientLinkCode::query()->sole();
        $this->assertSame(hash('sha256', $code), $stored->code_hash);
        $this->assertStringNotContainsString($code, json_encode($stored->getAttributes()));
    }

    public function test_linked_owners_are_listed_and_access_can_be_revoked(): void
    {
        $owner = Owner::factory()->create(['name' => 'Carlos Pérez']);
        $code = app(OwnerAccessService::class)->issueCode($this->patient, $this->nurse->id)['code'];
        app(OwnerAccessService::class)->redeem($owner, $code);

        $link = $this->patient->ownerLinks()->sole();

        Livewire::actingAs($this->receptionist)->test(PatientShow::class, ['patient' => $this->patient])
            ->assertSee('Carlos Pérez')
            ->call('revokeOwnerLink', $link->id)
            ->assertSee('Se retiró el acceso de Carlos Pérez')
            ->assertSee('Nadie todavía.');

        $this->assertNotNull($link->fresh()->revoked_at);
    }

    public function test_nurses_set_the_owner_status_and_publish_updates(): void
    {
        Livewire::actingAs($this->nurse)->test(PatientShow::class, ['patient' => $this->patient])
            ->set('ownerStatus', OwnerVisibleStatus::Stable->value)
            ->call('saveOwnerStatus')
            ->assertSee('El propietario ahora ve: Estable.')
            ->call('publishOwnerUpdate')
            ->assertHasErrors(['updateBody' => 'required'])
            ->set('updateBody', 'Durmió tranquila, sin vómito.')
            ->call('publishOwnerUpdate')
            ->assertHasNoErrors()
            ->assertSee('Durmió tranquila, sin vómito.')
            ->assertSet('updateBody', '');

        $this->assertSame(OwnerVisibleStatus::Stable, $this->stay->fresh()->owner_status);
        $this->assertSame($this->nurse->id, (int) OwnerUpdate::query()->sole()->author_id);
    }

    public function test_receptionists_cannot_publish_clinical_updates(): void
    {
        Livewire::actingAs($this->receptionist)->test(PatientShow::class, ['patient' => $this->patient])
            ->assertDontSee('Nuevo parte médico')
            ->set('updateBody', 'Texto')
            ->call('publishOwnerUpdate')
            ->assertForbidden();
    }
}
