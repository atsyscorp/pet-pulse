<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

final class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('landing', [
            'contactEmail' => config('petpulse.contact_email'),
            'whatsapp' => config('petpulse.whatsapp'),
        ]);
    }
}
