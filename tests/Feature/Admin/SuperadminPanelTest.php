<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AccessRequest;
use App\Models\Clinic;
use App\Models\Owner;
use App\Models\OwnerPatientLink;
use App\Models\Patient;
use App\Models\PlatformAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SuperadminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): PlatformAdmin
    {
        return PlatformAdmin::create(['name' => 'Equipo PetPulse', 'email' => 'admin@petpulse.test', 'password' => 'Superclave-2026']);
    }

    private function link(Owner $owner, Patient $patient, bool $revoked = false): void
    {
        OwnerPatientLink::create([
            'clinic_id' => $patient->clinic_id,
            'owner_id' => $owner->id,
            'patient_id' => $patient->id,
            'linked_at' => now(),
            'revoked_at' => $revoked ? now() : null,
        ]);
    }

    public function test_the_admin_login_page_renders_and_is_not_indexed(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSeeText('Superadministración')
            ->assertSee('noindex', false);
    }

    public function test_a_superadmin_logs_in_and_reaches_the_dashboard(): void
    {
        $admin = $this->admin();

        $this->post(route('admin.login'), ['email' => 'admin@petpulse.test', 'password' => 'Superclave-2026'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');
        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->get(route('admin.dashboard'))->assertOk()->assertSeeText('Resumen de la plataforma');
    }

    public function test_a_wrong_password_is_rejected_and_attempts_are_throttled(): void
    {
        $this->admin();

        foreach (range(1, 5) as $attempt) {
            $this->from(route('admin.login'))
                ->post(route('admin.login'), ['email' => 'admin@petpulse.test', 'password' => 'incorrecta'])
                ->assertSessionHasErrors(['email' => 'Correo o contraseña incorrectos.']);
        }

        $this->post(route('admin.login'), ['email' => 'admin@petpulse.test', 'password' => 'Superclave-2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_guests_are_sent_to_the_admin_login(): void
    {
        foreach (['admin.dashboard', 'admin.owners', 'admin.clinics', 'admin.staff', 'admin.access-requests'] as $route) {
            $this->get(route($route))->assertRedirect(route('admin.login'));
        }
    }

    public function test_clinic_staff_cannot_open_the_panel_even_as_clinic_admins(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_a_superadmin_session_does_not_open_the_clinic_app(): void
    {
        // Sign in for real: actingAs() would also make `admin` the default guard.
        $this->admin();
        $this->post(route('admin.login'), ['email' => 'admin@petpulse.test', 'password' => 'Superclave-2026']);
        $this->assertAuthenticated('admin');

        $this->get(route('hospital.kardex'))->assertRedirect(route('login'));
    }

    public function test_the_dashboard_counts_the_whole_platform(): void
    {
        [$a, $b] = Clinic::factory()->count(2)->create();
        User::factory()->for($a)->count(2)->create();
        User::factory()->for($b)->create();
        $owners = Owner::factory()->count(3)->create();
        $this->link($owners[0], Patient::factory()->for($a)->create());
        $this->link($owners[1], Patient::factory()->for($b)->create());
        $this->link($owners[2], Patient::factory()->for($b)->create(), revoked: true);
        AccessRequest::create([
            'clinic_name' => 'Clínica Huellitas', 'city' => 'Medellín', 'contact_name' => 'Ana Gómez',
            'email' => 'ana@huellitas.test', 'phone' => '3001234567', 'status' => AccessRequest::STATUS_PENDING, 'consented_at' => now(),
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats) => $stats['clinics'] === 2
                && $stats['staff'] === 3
                && $stats['owners'] === 3
                && $stats['owners_week'] === 3
                && $stats['linked_pets'] === 2
                && $stats['pending_requests'] === 1)
            ->assertSeeText('Clínica Huellitas')
            ->assertSeeText($owners[2]->name);
    }

    public function test_the_owner_list_shows_and_searches_registered_owners(): void
    {
        $clinic = Clinic::factory()->create();
        $laura = Owner::factory()->create(['name' => 'Laura Mejía', 'email' => 'laura@example.com']);
        Owner::factory()->create(['name' => 'Carlos Ruiz', 'email' => 'carlos@example.com']);
        $this->link($laura, Patient::factory()->for($clinic)->create());
        $this->link($laura, Patient::factory()->for($clinic)->create());

        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.owners'))
            ->assertOk()
            ->assertSeeText('Laura Mejía')
            ->assertSeeText('2 mascotas · 1 clínica')
            ->assertSeeText('Carlos Ruiz');

        $this->get(route('admin.owners', ['q' => 'laura@']))
            ->assertOk()
            ->assertSeeText('Laura Mejía')
            ->assertDontSeeText('Carlos Ruiz');
    }

    public function test_the_clinic_list_counts_each_tenant_separately(): void
    {
        $andes = Clinic::factory()->create(['name' => 'Clínica Los Andes']);
        User::factory()->for($andes)->count(2)->create();
        Patient::factory()->for($andes)->count(3)->create();
        Clinic::factory()->create(['name' => 'Vet Caribe', 'is_active' => false]);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.clinics'))
            ->assertOk()
            ->assertSeeText('Clínica Los Andes')
            ->assertSeeText('2 usuarios · 3 pacientes · 0 mascotas en la app')
            ->assertSeeText('Vet Caribe')
            ->assertSeeText('Inactiva');
    }

    public function test_the_staff_and_access_request_lists_render(): void
    {
        $clinic = Clinic::factory()->create(['name' => 'Clínica Los Andes']);
        User::factory()->for($clinic)->create(['name' => 'Camila Restrepo', 'role' => 'veterinarian']);
        AccessRequest::create([
            'clinic_name' => 'Clínica Huellitas', 'city' => 'Medellín', 'beds' => 8, 'contact_name' => 'Ana Gómez',
            'email' => 'ana@huellitas.test', 'phone' => '300 123 4567', 'message' => 'Queremos probarlo.',
            'status' => AccessRequest::STATUS_PENDING, 'consented_at' => now(),
        ]);

        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.staff'))
            ->assertOk()
            ->assertSeeText('Camila Restrepo')
            ->assertSeeText('Veterinario · Clínica Los Andes');

        $this->get(route('admin.access-requests'))
            ->assertOk()
            ->assertSeeText('Clínica Huellitas')
            ->assertSeeText('8 jaulas o camas')
            ->assertSeeText('Pendiente')
            ->assertSee('tel:3001234567', false)
            ->assertSeeText('Queremos probarlo.');
    }

    public function test_a_superadmin_can_log_out(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
    }

    public function test_the_console_command_creates_a_superadmin(): void
    {
        $this->artisan('petpulse:superadmin', ['--email' => 'Dueno@PetPulse.test', '--name' => 'Orlando Ruiz'])
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, con mayúsculas, minúsculas y números)', 'Superclave-2026')
            ->expectsQuestion('Repita la contraseña', 'Superclave-2026')
            ->assertSuccessful();

        $admin = PlatformAdmin::query()->sole();
        $this->assertSame('dueno@petpulse.test', $admin->email);
        $this->assertSame('Orlando Ruiz', $admin->name);
        $this->assertNotSame('Superclave-2026', $admin->getAttributes()['password']);
    }

    public function test_the_console_command_rejects_weak_or_mismatched_passwords(): void
    {
        $this->artisan('petpulse:superadmin', ['--email' => 'a@petpulse.test', '--name' => 'A'])
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, con mayúsculas, minúsculas y números)', 'corta')
            ->assertFailed();

        $this->artisan('petpulse:superadmin', ['--email' => 'a@petpulse.test', '--name' => 'A'])
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, con mayúsculas, minúsculas y números)', 'Superclave-2026')
            ->expectsQuestion('Repita la contraseña', 'Otraclave-2026')
            ->expectsOutputToContain('no coinciden')
            ->assertFailed();

        $this->assertSame(0, PlatformAdmin::query()->count());
    }

    public function test_the_console_command_resets_an_existing_password(): void
    {
        $this->admin();

        $this->artisan('petpulse:superadmin', ['--email' => 'admin@petpulse.test'])
            ->expectsConfirmation('Ya existe un superadministrador con admin@petpulse.test. ¿Cambiar su contraseña?', 'yes')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres, con mayúsculas, minúsculas y números)', 'Nuevaclave-2027')
            ->expectsQuestion('Repita la contraseña', 'Nuevaclave-2027')
            ->assertSuccessful();

        $this->post(route('admin.login'), ['email' => 'admin@petpulse.test', 'password' => 'Nuevaclave-2027'])
            ->assertRedirect(route('admin.dashboard'));
    }
}
