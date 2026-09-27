<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use App\Support\Pharmacy\Quantity;
use Database\Factories\DrugFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $volume_per_presentation decimal:4 string
 * @property string $open_fraction_balance decimal:4 string
 * @property int $stock_packages
 * @property ?Carbon $opened_at
 */
#[Fillable([
    'clinic_id', 'name', 'active_ingredient', 'sanitary_registry',
    'concentration_amount', 'concentration_unit', 'presentation_unit',
    'volume_per_presentation', 'fraction_unit', 'stock_packages',
    'open_fraction_balance', 'reorder_level_packages',
    'stability_hours_after_opening', 'opened_at', 'is_controlled', 'is_active',
])]
class Drug extends Model
{
    use BelongsToClinic;

    /** @use HasFactory<DrugFactory> */
    use HasFactory;

    use SoftDeletes;

    protected function casts(): array
    {
        return [
            // `decimal:N` casts round-trip as strings (never float).
            'concentration_amount' => 'decimal:4',
            'volume_per_presentation' => 'decimal:4',
            'open_fraction_balance' => 'decimal:4',
            'stock_packages' => 'integer',
            'reorder_level_packages' => 'integer',
            'stability_hours_after_opening' => 'integer',
            'opened_at' => 'datetime',
            'is_controlled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function openBalance(): Quantity
    {
        return Quantity::of($this->open_fraction_balance);
    }

    public function packageVolume(): Quantity
    {
        return Quantity::of($this->volume_per_presentation);
    }

    /** Sealed + open stock expressed in fraction_unit. */
    public function totalAvailable(): Quantity
    {
        return $this->packageVolume()
            ->multipliedBy($this->stock_packages)
            ->plus($this->openBalance());
    }

    /** True when the currently open unit has exceeded its in-use stability window. */
    public function openUnitExpired(?Carbon $at = null): bool
    {
        if ($this->stability_hours_after_opening === null || $this->opened_at === null) {
            return false;
        }

        return $this->opened_at->copy()
            ->addHours($this->stability_hours_after_opening)
            ->lessThanOrEqualTo($at ?? now());
    }

    public function isBelowReorderLevel(): bool
    {
        return $this->stock_packages <= $this->reorder_level_packages;
    }

    /** @return HasMany<DrugFractionLog, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(DrugFractionLog::class);
    }
}
