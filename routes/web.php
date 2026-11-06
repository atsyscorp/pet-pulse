<?php

declare(strict_types=1);

use App\Http\Controllers\AccessRequestController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Hospital\AdministerDoseController;
use App\Http\Controllers\LandingController;
use App\Livewire\Hospital\AdmissionEditor;
use App\Livewire\Hospital\KardexBoard;
use App\Livewire\Hospital\PatientEditor;
use App\Livewire\Hospital\PatientIndex;
use App\Livewire\Hospital\PatientShow;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public site
Route::get('/', LandingController::class)->name('home');
Route::get('/solicitar-acceso', [AccessRequestController::class, 'create'])->name('access.create');
Route::post('/solicitar-acceso', [AccessRequestController::class, 'store'])->middleware('throttle:6,1')->name('access.store');

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

        // Patients and admissions
        Route::get('/pacientes', PatientIndex::class)->name('patients.index');
        Route::get('/pacientes/nuevo', PatientEditor::class)->name('patients.create');
        Route::get('/pacientes/{patient}', PatientShow::class)->name('patients.show');
        Route::get('/pacientes/{patient}/editar', PatientEditor::class)->name('patients.edit');
        Route::get('/pacientes/{patient}/ingreso', AdmissionEditor::class)->name('patients.admit');

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
