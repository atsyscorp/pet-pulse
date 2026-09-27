<?php

declare(strict_types=1);

use App\Http\Controllers\Hospital\AdministerDoseController;
use App\Livewire\Hospital\KardexBoard;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('hospital.kardex'));

Route::middleware(['auth', 'clinic'])
    ->prefix('hospital')
    ->name('hospital.')
    ->group(function (): void {
        Route::get('/kardex', KardexBoard::class)->name('kardex');

        Route::post('/kardex/{schedule}/administer', AdministerDoseController::class)
            ->middleware('throttle:60,1')
            ->name('kardex.administer');
    });

// Local-only impersonation helper for the seeded demo (no auth UI is shipped).
if (app()->environment('local')) {
    Route::get('/dev/login/{user}', function (User $user) {
        Auth::login($user);

        return redirect()->route('hospital.kardex');
    })->name('dev.login');
}

Route::get('/login', fn () => app()->environment('local')
    ? response('Demo: visite /dev/login/{userId} (ver DatabaseSeeder).', 401)
    : abort(401))->name('login');
