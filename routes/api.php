<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Owner\AuthController;
use App\Http\Controllers\Api\V1\Owner\PetController;
use Illuminate\Support\Facades\Route;

/*
 * Owner mobile app, version 1. Bearer tokens (Sanctum) with the "owner"
 * ability; no tenant is bound on these requests: access is per linked pet.
 */
Route::prefix('v1/owner')->name('api.v1.owner.')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

    Route::middleware(['auth:sanctum', 'abilities:owner', 'owner'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/pets', [PetController::class, 'index'])->name('pets.index');
        // Codes are short; throttle hard per owner to make guessing pointless.
        Route::post('/pets/link', [PetController::class, 'link'])->middleware('throttle:10,60')->name('pets.link');
        Route::get('/pets/{pet}', [PetController::class, 'show'])->whereNumber('pet')->name('pets.show');
        Route::get('/pets/{pet}/updates', [PetController::class, 'updates'])->whereNumber('pet')->name('pets.updates');
        Route::get('/pets/{pet}/medications', [PetController::class, 'medications'])->whereNumber('pet')->name('pets.medications');
    });
});
