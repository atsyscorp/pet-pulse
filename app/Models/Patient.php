<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HospitalizationStatus;
use App\Enums\PatientSex;
use App\Models\Concerns\BelongsToClinic;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'clinic_id', 'name', 'species', 'breed', 'sex', 'birth_date', 'microchip',
    'owner_name', 'owner_document', 'owner_phone',
])]
class Patient extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    use SoftDeletes;

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'sex' => PatientSex::class];
    }

    /** Human age from birth_date, e.g. "3 años", "7 meses", "3 semanas". */
    public function age(): ?string
    {
        if ($this->birth_date === null) {
            return null;
        }

        $diff = $this->birth_date->diff(now());

        return match (true) {
            $diff->y >= 1 => $diff->y.' '.($diff->y === 1 ? 'año' : 'años'),
            $diff->m >= 1 => $diff->m.' '.($diff->m === 1 ? 'mes' : 'meses'),
            default => max(1, intdiv($diff->d, 7)).' '.($diff->d < 14 ? 'semana' : 'semanas'),
        };
    }

    /**
     * The open stay, if any. AdmitPatientService guarantees at most one, so a
     * plain constrained hasOne is enough (no one-of-many join needed).
     *
     * @return HasOne<Hospitalization, $this>
     */
    public function activeHospitalization(): HasOne
    {
        return $this->hasOne(Hospitalization::class)->where('status', HospitalizationStatus::Admitted);
    }

    /**
     * Most recent stay (open or closed), for the owner app.
     *
     * @return HasOne<Hospitalization, $this>
     */
    public function latestHospitalization(): HasOne
    {
        return $this->hasOne(Hospitalization::class)->latestOfMany('admitted_at');
    }

    /** @return HasMany<OwnerPatientLink, $this> */
    public function ownerLinks(): HasMany
    {
        return $this->hasMany(OwnerPatientLink::class);
    }

    /** @return HasMany<Hospitalization, $this> */
    public function hospitalizations(): HasMany
    {
        return $this->hasMany(Hospitalization::class);
    }
}
