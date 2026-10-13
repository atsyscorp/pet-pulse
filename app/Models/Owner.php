<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OwnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A pet owner using the mobile app. Global (not tenant-scoped): the same
 * person may have pets in several clinics.
 */
#[Fillable(['name', 'email', 'phone', 'password', 'terms_accepted_at'])]
#[Hidden(['password'])]
class Owner extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<OwnerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'terms_accepted_at' => 'datetime',
        ];
    }

    /** @return HasMany<OwnerPatientLink, $this> */
    public function links(): HasMany
    {
        return $this->hasMany(OwnerPatientLink::class);
    }

    /**
     * Pets this owner may see right now: linked by a clinic and not revoked.
     * Clinic tenancy does not apply here (no tenant is bound on API requests);
     * the link itself is the authorisation.
     *
     * @return BelongsToMany<Patient, $this>
     */
    public function pets(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'owner_patient_links')
            ->wherePivotNull('revoked_at')
            ->withPivot(['linked_at', 'clinic_id'])
            ->withTimestamps();
    }
}
