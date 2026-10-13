<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A PetPulse superadministrator. Signs in through the `admin` guard at
 * /admin and sees every clinic, so it is never tenant-scoped. Accounts are
 * created from the server console only (`php artisan petpulse:superadmin`).
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password'])]
class PlatformAdmin extends Authenticatable
{
    /** Session-only accounts: no "remember me" cookie for platform access. */
    protected $rememberTokenName = '';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }
}
