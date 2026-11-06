<?php

declare(strict_types=1);

namespace Tests\Feature\Hospital;

use App\Enums\KardexStatus;
use App\Enums\UserRole;
use App\Livewire\Hospital\KardexBoard;
use App\Models\Clinic;
use App\Models\Drug;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Reverb down must never turn a recorded dose into an error at the bedside:
 * the nurse would retry, and the message would wrongly say nothing was saved.
 */
final class RealtimeResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_dose_is_recorded_and_confirmed_even_when_the_websocket_server_is_down(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'key',
            'broadcasting.connections.reverb.secret' => 'secret',
            'broadcasting.connections.reverb.app_id' => 'app',
            'broadcasting.connections.reverb.options.host' => '127.0.0.1',
            'broadcasting.connections.reverb.options.port' => 1,     // nothing listens here
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);
        Exceptions::fake();

        $clinic = Clinic::factory()->create();
        app(TenantContext::class)->set($clinic);
        $nurse = User::factory()->for($clinic)->role(UserRole::Nurse)->create();
        $stay = Hospitalization::factory()->for($clinic)->create();
        $drug = Drug::factory()->for($clinic)->stock(1)->create();
        $schedule = KardexSchedule::factory()->create(['clinic_id' => $clinic->id, 'hospitalization_id' => $stay->id, 'drug_id' => $drug->id]);

        Livewire::actingAs($nurse)->test(KardexBoard::class)
            ->call('markAsAdministered', $schedule->id)
            ->assertDispatched('kardex-toast', type: 'success');

        $this->assertSame(KardexStatus::Administered, $schedule->fresh()->status);
        Exceptions::assertReported(BroadcastException::class);
    }
}
