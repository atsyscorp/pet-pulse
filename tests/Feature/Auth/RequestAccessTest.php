<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\AccessRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RequestAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'clinic_name' => 'Clínica Veterinaria Norte',
            'city' => 'Bogotá',
            'tax_id' => '900.123.456-7',
            'beds' => 6,
            'contact_name' => 'Marcela Rojas',
            'email' => 'marcela@clinicanorte.co',
            'phone' => '+57 300 123 4567',
            'message' => 'Tenemos dos turnos de enfermería.',
            'consent' => '1',
        ], $overrides);
    }

    public function test_the_page_renders_with_the_form(): void
    {
        $this->get(route('access.create'))
            ->assertOk()
            ->assertSee('Solicitar acceso')
            ->assertSee('Nombre de la clínica')
            ->assertSee('Ley 1581 de 2012');
    }

    public function test_a_valid_request_is_stored_as_pending_and_confirmed(): void
    {
        $this->post(route('access.store'), $this->payload())
            ->assertRedirect(route('access.create'))
            ->assertSessionHas('access_requested', 'marcela@clinicanorte.co');

        $this->assertDatabaseHas('access_requests', [
            'clinic_name' => 'Clínica Veterinaria Norte',
            'email' => 'marcela@clinicanorte.co',
            'status' => AccessRequest::STATUS_PENDING,
        ]);
        $this->assertNotNull(AccessRequest::query()->first()->consented_at);

        $this->followingRedirects()
            ->post(route('access.store'), $this->payload())
            ->assertSee('Recibimos su solicitud');
    }

    public function test_required_fields_and_consent_are_validated_in_spanish(): void
    {
        $this->from(route('access.create'))
            ->post(route('access.store'), ['consent' => '0'])
            ->assertRedirect(route('access.create'))
            ->assertSessionHasErrors([
                'clinic_name' => 'Complete el campo nombre de la clínica.',
                'email' => 'Complete el campo correo electrónico.',
                'consent' => 'Debe autorizar el tratamiento de datos para enviar la solicitud.',
            ]);

        $this->assertDatabaseCount('access_requests', 0);
    }

    public function test_an_invalid_phone_and_bed_count_are_rejected(): void
    {
        $this->post(route('access.store'), $this->payload(['phone' => 'abc', 'beds' => 900]))
            ->assertSessionHasErrors(['phone', 'beds']);
    }

    public function test_a_filled_honeypot_looks_successful_but_stores_nothing(): void
    {
        $this->post(route('access.store'), $this->payload(['website' => 'https://spam.example']))
            ->assertRedirect(route('access.create'));

        $this->assertDatabaseCount('access_requests', 0);
    }

    public function test_public_pages_offer_the_theme_toggle_and_light_default(): void
    {
        foreach ([route('home'), route('login'), route('access.create')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('data-theme-set="dark"', false)
                ->assertSee('data-theme="light"', false);
        }
    }

    public function test_the_form_redisplays_with_errors_and_keeps_input(): void
    {
        $this->followingRedirects()
            ->from(route('access.create'))
            ->post(route('access.store'), ['clinic_name' => 'Mi clínica', 'consent' => '0'])
            ->assertOk()
            ->assertSee('Revise los campos marcados')
            ->assertSee('Mi clínica')
            ->assertSee('Complete el campo ciudad.');
    }
}
