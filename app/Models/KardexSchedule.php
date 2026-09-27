<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdministrationRoute;
use App\Enums\KardexStatus;
use App\Models\Concerns\BelongsToClinic;
use App\Support\Pharmacy\Quantity;
use Database\Factories\KardexScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property KardexStatus $status
 * @property Carbon $scheduled_at
 * @property ?Carbon $administered_at
 * @property string $dose_amount decimal:4 string
 */
#[Fillable([
    'clinic_id', 'hospitalization_id', 'drug_id', 'prescribed_by', 'dose_amount',
    'dose_per_kg', 'weight_kg_at_prescription', 'route', 'scheduled_at',
    'administered_at', 'administered_by', 'status', 'notes',
])]
class KardexSchedule extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<KardexScheduleFactory> */
    use HasFactory;

    /** Window (minutes) after scheduled_at before a pending dose is flagged overdue. */
    public const int OVERDUE_GRACE_MINUTES = 30;

    /** Window (minutes) before scheduled_at in which a dose is considered "due now". */
    public const int DUE_SOON_MINUTES = 30;

    protected function casts(): array
    {
        return [
            'status' => KardexStatus::class,
            'route' => AdministrationRoute::class,
            'dose_amount' => 'decimal:4',
            'dose_per_kg' => 'decimal:4',
            'weight_kg_at_prescription' => 'decimal:3',
            'scheduled_at' => 'datetime',
            'administered_at' => 'datetime',
        ];
    }

    public function dose(): Quantity
    {
        return Quantity::of($this->dose_amount);
    }

    public function isOverdue(?Carbon $now = null): bool
    {
        return $this->status === KardexStatus::Pending
            && $this->scheduled_at->copy()->addMinutes(self::OVERDUE_GRACE_MINUTES)->isBefore($now ?? now());
    }

    public function isDueSoon(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->status === KardexStatus::Pending
            && ! $this->isOverdue($now)
            && $this->scheduled_at->copy()->subMinutes(self::DUE_SOON_MINUTES)->lessThanOrEqualTo($now);
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
    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    /** @return MorphMany<DrugFractionLog, $this> */
    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(DrugFractionLog::class, 'source');
    }
}
