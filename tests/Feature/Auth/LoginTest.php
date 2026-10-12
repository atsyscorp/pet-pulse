<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Iniciar sesión')->assertSee('Correo electrónico');
    }

    public function test_users_can_log_in_and_reach_the_kardex(): void
    {
        $user = User::factory()->create(['password' => 'secreta-123']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'secreta-123'])
            ->assertRedirect(route('hospital.kardex'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('hospital.kardex'))->assertOk();
    }

    public function test_wrong_password_is_rejected_with_a_spanish_message(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))
            ->post(route('login'), ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Correo o contraseña incorrectos.']);

        $this->assertGuest();
    }

    public function test_authenticated_users_are_sent_to_the_kardex_instead_of_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('hospital.kardex'));
    }

    public function test_users_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
