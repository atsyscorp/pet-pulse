<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\KardexStatus;
use App\Enums\OwnerVisibleStatus;
use App\Enums\TriageLevel;
use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\Drug;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\User;
use App\Services\OwnerApp\OwnerAccessService;
use App\Services\OwnerApp\OwnerCommunicationService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class OwnerApiTest extends TestCase
{
    use RefreshDatabase;

    private Clinic $clinic;

    private User $vet;

    private Patient $rocky;

    private Hospitalization $stay;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00', 'America/Bogota'));
        $this->clinic = Clinic::factory()->create(['name' => 'Clínica Los Andes']);
        $tenant = app(TenantContext::class);
        $tenant->set($this->clinic);

        $this->vet = User::factory()->for($this->clinic)->role(UserRole::Veterinarian)->create(['name' => 'Dra. Restrepo']);
        $this->rocky = Patient::factory()->for($this->clinic)->create(['name' => 'Rocky', 'species' => 'Canino', 'microchip' => '9001']);
        $this->stay = Hospitalization::factory()->for($this->clinic)->for($this->rocky)->create([
            'triage_level' => TriageLevel::Critical, 'bed_label' => 'UCI-1', 'current_weight_kg' => '32.400',
        ]);

        // API requests carry no tenant; reproduce that for every call below.
        $tenant->forget();
    }

    private function codeFor(Patient $patient): string
    {
        return app(TenantContext::class)->run($patient->clinic_id, fn () => app(OwnerAccessService::class)->issueCode($patient, $this->vet->id)['code']);
    }

    private function linkedOwner(): Owner
    {
        $owner = Owner::factory()->create(['name' => 'María López']);
        app(OwnerAccessService::class)->redeem($owner, $this->codeFor($this->rocky));

        return $owner;
    }

    public function test_an_owner_registers_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/owner/register', [
            'name' => 'María López', 'email' => 'Maria@Example.com', 'phone' => '300 123 4567',
            'password' => 'secreta123', 'accept_terms' => true, 'device_name' => 'Pixel 8',
        ])->assertCreated()->assertJsonPath('owner.email', 'maria@example.com');

        $this->assertNotEmpty($response->json('token'));
        $this->assertNotNull(Owner::query()->sole()->terms_accepted_at);

        $this->withToken($response->json('token'))->getJson('/api/v1/owner/me')->assertOk()->assertJsonPath('data.name', 'María López');
    }

    public function test_registration_requires_consent_and_a_reasonable_password_with_spanish_messages(): void
    {
        $this->postJson('/api/v1/owner/register', [
            'name' => 'X', 'email' => 'x@example.com', 'password' => 'corta', 'device_name' => 'iPhone',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password', 'accept_terms'])
            ->assertJsonPath('errors.accept_terms.0', 'Debes aceptar la política de tratamiento de datos personales.');
    }

    public function test_login_and_logout(): void
    {
        Owner::factory()->create(['email' => 'ana@example.com', 'password' => 'secreta123']);

        $this->postJson('/api/v1/owner/login', ['email' => 'ana@example.com', 'password' => 'mala', 'device_name' => 'x'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Correo o contraseña incorrectos.');

        $token = $this->postJson('/api/v1/owner/login', ['email' => 'ANA@example.com', 'password' => 'secreta123', 'device_name' => 'x'])
            ->assertOk()->json('token');

        $this->withToken($token)->postJson('/api/v1/owner/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/owner/me')->assertUnauthorized();
    }

    public function test_redeeming_a_clinic_code_links_the_pet_once(): void
    {
        $owner = Owner::factory()->create();
        $code = $this->codeFor($this->rocky);
        Sanctum::actingAs($owner, ['owner']);

        // Accepts the QR payload and lowercase/hyphenated input.
        $this->postJson('/api/v1/owner/pets/link', ['code' => 'PETPULSE:'.$code])
            ->assertCreated()->assertJsonPath('data.name', 'Rocky')->assertJsonPath('data.clinic.name', 'Clínica Los Andes');

        // Single use.
        $other = Owner::factory()->create();
        Sanctum::actingAs($other, ['owner']);
        $this->postJson('/api/v1/owner/pets/link', ['code' => strtolower(substr($code, 0, 4).'-'.substr($code, 4))])
            ->assertUnprocessable()->assertJsonPath('message', 'El código no es válido o ya venció. Pide uno nuevo en tu clínica.');
    }

    public function test_expired_and_superseded_codes_do_not_work(): void
    {
        $first = $this->codeFor($this->rocky);
        $second = $this->codeFor($this->rocky);   // voids the first
        Sanctum::actingAs(Owner::factory()->create(), ['owner']);

        $this->postJson('/api/v1/owner/pets/link', ['code' => $first])->assertUnprocessable();

        Carbon::setTestNow(now()->addDays(8));
        $this->postJson('/api/v1/owner/pets/link', ['code' => $second])->assertUnprocessable();
    }

    public function test_the_pet_list_and_detail_hide_internal_clinical_data(): void
    {
        $owner = $this->linkedOwner();
        app(TenantContext::class)->run($this->clinic, fn () => app(OwnerCommunicationService::class)->setStatus($this->stay, OwnerVisibleStatus::Delicate));
        Sanctum::actingAs($owner, ['owner']);

        $this->getJson('/api/v1/owner/pets')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Rocky');

        $json = $this->getJson('/api/v1/owner/pets/'.$this->rocky->id)
            ->assertOk()
            ->assertJsonPath('data.hospitalization.active', true)
            ->assertJsonPath('data.hospitalization.status_label', 'Hospitalizado')
            ->assertJsonPath('data.hospitalization.owner_status', 'delicate')
            ->assertJsonPath('data.hospitalization.owner_status_label', 'Delicado')
            ->json();

        $raw = json_encode($json);
        foreach (['critical', 'Crítico', 'UCI-1', 'triage', 'owner_name', 'owner_document'] as $internal) {
            $this->assertStringNotContainsString($internal, $raw, "Owner payload leaked: {$internal}");
        }
    }

    public function test_an_owner_never_sees_a_pet_they_are_not_linked_to(): void
    {
        $owner = $this->linkedOwner();
        $stranger = app(TenantContext::class)->run($this->clinic, fn () => Patient::factory()->for($this->clinic)->create(['name' => 'Ajeno']));
        Sanctum::actingAs($owner, ['owner']);

        $this->getJson('/api/v1/owner/pets')->assertJsonMissing(['name' => 'Ajeno']);
        $this->getJson('/api/v1/owner/pets/'.$stranger->id)->assertNotFound();
        $this->getJson('/api/v1/owner/pets/'.$stranger->id.'/updates')->assertNotFound();
        $this->getJson('/api/v1/owner/pets/'.$stranger->id.'/medications')->assertNotFound();
    }

    public function test_updates_and_administered_medications_are_listed_newest_first(): void
    {
        $owner = $this->linkedOwner();
        $drug = app(TenantContext::class)->run($this->clinic, fn () => Drug::factory()->for($this->clinic)->create(['name' => 'Ceftriaxona 1 g', 'active_ingredient' => 'Ceftriaxona']));
        app(TenantContext::class)->run($this->clinic, function () use ($drug): void {
            $comms = app(OwnerCommunicationService::class);
            $comms->publishUpdate($this->stay, $this->vet->id, 'Ingresó estable.');
            $comms->publishUpdate($this->stay, $this->vet->id, 'Hoy comió bien.');
            KardexSchedule::factory()->create(['clinic_id' => $this->clinic->id, 'hospitalization_id' => $this->stay->id, 'drug_id' => $drug->id, 'status' => KardexStatus::Administered, 'administered_at' => now()->subHour(), 'administered_by' => $this->vet->id]);
            KardexSchedule::factory()->create(['clinic_id' => $this->clinic->id, 'hospitalization_id' => $this->stay->id, 'drug_id' => $drug->id, 'status' => KardexStatus::Pending]);
        });
        Sanctum::actingAs($owner, ['owner']);

        $this->getJson("/api/v1/owner/pets/{$this->rocky->id}/updates")
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.body', 'Hoy comió bien.')
            ->assertJsonPath('data.0.author', 'Dra. Restrepo')
            ->assertJsonPath('data.0.author_role', 'Veterinario');

        $meds = $this->getJson("/api/v1/owner/pets/{$this->rocky->id}/medications")
            ->assertOk()->assertJsonCount(1, 'data')           // pending dose not shown
            ->assertJsonPath('data.0.drug', 'Ceftriaxona 1 g')
            ->assertJsonPath('data.0.route_label', 'Intravenosa')
            ->json('data.0');
        $this->assertArrayNotHasKey('dose_amount', $meds);
    }

    public function test_revoked_access_disappears_immediately(): void
    {
        $owner = $this->linkedOwner();
        $link = $owner->links()->withoutGlobalScopes()->sole();
        app(TenantContext::class)->run($this->clinic, fn () => app(OwnerAccessService::class)->revoke($link, $this->vet->id));
        Sanctum::actingAs($owner, ['owner']);

        $this->getJson('/api/v1/owner/pets')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/owner/pets/'.$this->rocky->id)->assertNotFound();
    }

    public function test_tokens_without_the_owner_ability_and_anonymous_calls_are_rejected(): void
    {
        $this->getJson('/api/v1/owner/pets')->assertUnauthorized();

        Sanctum::actingAs(Owner::factory()->create(), ['something-else']);
        $this->getJson('/api/v1/owner/pets')->assertForbidden();
    }

    public function test_a_staff_session_cannot_use_the_owner_api(): void
    {
        $this->actingAs($this->vet)->getJson('/api/v1/owner/pets')->assertUnauthorized();
    }
}
