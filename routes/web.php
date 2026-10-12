<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Hospital\AdministerDoseController;
use App\Http\Controllers\LandingController;
use App\Livewire\Hospital\KardexBoard;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public site
Route::get('/', LandingController::class)->name('home');

// Authentication
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

// Clinical application (tenant-scoped)
Route::middleware(['auth', 'clinic'])
    ->prefix('hospital')
    ->name('hospital.')
    ->group(function (): void {
        Route::get('/kardex', KardexBoard::class)->name('kardex');

        Route::post('/kardex/{schedule}/administer', AdministerDoseController::class)
            ->middleware('throttle:60,1')
            ->name('kardex.administer');
    });

// Local-only impersonation helper for the seeded demo.
if (app()->environment('local')) {
    Route::get('/dev/login/{user}', function (User $user) {
        Auth::login($user);

        return redirect()->route('hospital.kardex');
    })->name('dev.login');
}
