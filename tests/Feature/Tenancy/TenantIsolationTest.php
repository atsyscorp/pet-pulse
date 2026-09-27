<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Exceptions\Tenancy\TenantMismatchException;
use App\Models\Clinic;
use App\Models\Drug;
use App\Models\KardexSchedule;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queries_are_scoped_to_the_active_clinic(): void
    {
        [$a, $b] = Clinic::factory()->count(2)->create();
        Drug::factory()->for($a)->create(['name' => 'Drug A']);
        Drug::factory()->for($b)->create(['name' => 'Drug B']);

        $tenant = app(TenantContext::class);

        $this->assertSame(['Drug A'], $tenant->run($a, fn () => Drug::query()->pluck('name')->all()));
        $this->assertSame(['Drug B'], $tenant->run($b, fn () => Drug::query()->pluck('name')->all()));
    }

    public function test_new_records_inherit_the_tenant_and_cannot_target_another_clinic(): void
    {
        [$a, $b] = Clinic::factory()->count(2)->create();
        $tenant = app(TenantContext::class);

        $drug = Drug::factory()->make(['clinic_id' => null]);
        $tenant->run($a, fn () => $drug->save());
        $this->assertSame($a->id, (int) $drug->clinic_id);

        $this->expectException(TenantMismatchException::class);
        $tenant->run($a, fn () => Drug::factory()->create(['clinic_id' => $b->id]));
    }

    public function test_route_model_binding_does_not_leak_other_clinics_schedules(): void
    {
        $foreign = KardexSchedule::factory()->create();
        $nurse = User::factory()->role(UserRole::Nurse)->create(); // different clinic

        $this->actingAs($nurse)
            ->postJson(route('hospital.kardex.administer', $foreign->id))
            ->assertNotFound();
    }

    public function test_icu_channel_authorisation_is_limited_to_own_clinic(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'key',
            'broadcasting.connections.reverb.secret' => 'secret',
            'broadcasting.connections.reverb.app_id' => 'app',
        ]);
        // Channels were registered on the test "null" broadcaster at boot; register them on reverb.
        require base_path('routes/channels.php');

        $nurse = User::factory()->create();

        $this->actingAs($nurse)
            ->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => "private-clinic.{$nurse->clinic_id}.icu"])
            ->assertOk();

        $this->actingAs($nurse)
            ->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-clinic.999999.icu'])
            ->assertForbidden();
    }
}
