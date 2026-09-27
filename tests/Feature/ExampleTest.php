<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_the_kardex_board(): void
    {
        $this->get('/')->assertRedirect(route('hospital.kardex'));
    }

    public function test_guests_cannot_open_the_kardex_board(): void
    {
        $this->get(route('hospital.kardex'))->assertRedirect(route('login'));
    }
}
