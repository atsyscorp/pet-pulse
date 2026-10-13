<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdministrationRoute;
use App\Enums\DoseMode;
use App\Enums\PrescriptionStatus;
use App\Models\Concerns\BelongsToClinic;
use App\Support\Pharmacy\Quantity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property PrescriptionStatus $status
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 */
#[Fillable([
    'clinic_id', 'hospitalization_id', 'drug_id', 'prescribed_by', 'dose_mode',
    'dose_per_kg', 'weight_kg', 'dose_amount', 'route', 'frequency_hours',
    'starts_at', 'ends_at', 'doses_planned', 'instructions', 'status',
    'suspended_at', 'suspended_by', 'suspension_reason',
])]
class Prescription extends Model
{
    use BelongsToClinic;

    protected function casts(): array
    {
        return [
            'dose_mode' => DoseMode::class,
            'dose_per_kg' => 'decimal:4',
            'weight_kg' => 'decimal:3',
            'dose_amount' => 'decimal:4',
            'route' => AdministrationRoute::class,
            'frequency_hours' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'doses_planned' => 'integer',
            'status' => PrescriptionStatus::class,
            'suspended_at' => 'datetime',
        ];
    }

    public function dose(): Quantity
    {
        return Quantity::of($this->dose_amount);
    }

    public function isActive(): bool
    {
        return $this->status === PrescriptionStatus::Active;
    }

    public function frequencyLabel(): string
    {
        return match ($this->frequency_hours) {
            null => 'Dosis única',
            24 => 'Cada 24 h (una vez al día)',
            default => "Cada {$this->frequency_hours} h",
        };
    }

    /** @return BelongsTo<Hospitalization, $this> */
    public function hospitalization(): BelongsTo
    {
        return $this->belongsTo(Hospitalization::class);
    }

    /** @return BelongsTo<Drug, $this> */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function prescriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function suspender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    /** @return HasMany<KardexSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(KardexSchedule::class);
    }
}
