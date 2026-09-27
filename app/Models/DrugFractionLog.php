<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DrugMovementType;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Append-only ledger row. See create_drug_fractions_log_table migration.
 */
#[Table('drug_fractions_log')]
#[Fillable([
    'clinic_id', 'drug_id', 'source_type', 'source_id', 'user_id', 'movement_type',
    'packages_delta', 'fraction_delta', 'stock_packages_after',
    'open_fraction_balance_after', 'notes',
])]
class DrugFractionLog extends Model
{
    use BelongsToClinic;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        $immutable = static function (): never {
            throw new LogicException('drug_fractions_log is append-only; post a compensating movement instead.');
        };

        static::updating($immutable);
        static::deleting($immutable);
    }

    protected function casts(): array
    {
        return [
            'movement_type' => DrugMovementType::class,
            'packages_delta' => 'integer',
            'fraction_delta' => 'decimal:4',
            'stock_packages_after' => 'integer',
            'open_fraction_balance_after' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Drug, $this> */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class)->withTrashed();
    }

    /** @return MorphTo<Model, $this> */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
