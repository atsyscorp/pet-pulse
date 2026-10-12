<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_shows_the_public_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('PetPulse')
            ->assertSee('Solicitar demostración')
            ->assertSee(route('login'));
    }

    public function test_guests_cannot_open_the_kardex_board(): void
    {
        $this->get(route('hospital.kardex'))->assertRedirect(route('login'));
    }
}
