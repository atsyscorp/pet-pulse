<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HospitalizationStatus;
use App\Enums\OwnerVisibleStatus;
use App\Enums\TriageLevel;
use App\Models\Concerns\BelongsToClinic;
use App\Observers\HospitalizationObserver;
use Database\Factories\HospitalizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(HospitalizationObserver::class)]
#[Fillable([
    'clinic_id', 'patient_id', 'attending_vet_id', 'bed_label', 'triage_level',
    'owner_status', 'owner_status_updated_at',
    'current_weight_kg', 'weight_recorded_at', 'admission_reason', 'diagnosis',
    'admitted_at', 'status', 'discharged_at', 'discharge_notes',
])]
class Hospitalization extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<HospitalizationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'triage_level' => TriageLevel::class,
            'owner_status' => OwnerVisibleStatus::class,
            'owner_status_updated_at' => 'datetime',
            'status' => HospitalizationStatus::class,
            'current_weight_kg' => 'decimal:3',
            'weight_recorded_at' => 'datetime',
            'admitted_at' => 'datetime',
            'discharged_at' => 'datetime',
        ];
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', HospitalizationStatus::Admitted);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function attendingVet(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attending_vet_id');
    }

    /** @return HasMany<OwnerUpdate, $this> */
    public function ownerUpdates(): HasMany
    {
        return $this->hasMany(OwnerUpdate::class);
    }

    /** @return HasMany<Prescription, $this> */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /** @return HasMany<KardexSchedule, $this> */
    public function kardexSchedules(): HasMany
    {
        return $this->hasMany(KardexSchedule::class);
    }
}
