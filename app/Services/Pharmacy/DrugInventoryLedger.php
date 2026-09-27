<?php

declare(strict_types=1);

namespace App\Services\Pharmacy;

use App\Enums\DrugMovementType;
use App\Models\Drug;
use App\Models\DrugFractionLog;
use App\Support\Pharmacy\Quantity;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Writes one ledger row per stock mutation.
 *
 * Contract: the caller has already applied the delta to the in-memory $drug
 * *and holds a row lock on it* (lockForUpdate) inside an open transaction.
 * The ledger snapshots the post-movement balances from $drug, so the log and
 * the drug row can never disagree once the transaction commits.
 */
final class DrugInventoryLedger
{
    public function record(
        Drug $drug,
        DrugMovementType $type,
        int $packagesDelta,
        Quantity $fractionDelta,
        ?Model $source,
        ?int $userId,
        ?string $notes = null,
    ): DrugFractionLog {
        if ($drug->getConnection()->transactionLevel() === 0) {
            throw new LogicException('Inventory movements must be recorded inside a database transaction.');
        }

        return DrugFractionLog::query()->create([
            'clinic_id' => $drug->clinic_id,
            'drug_id' => $drug->getKey(),
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'user_id' => $userId,
            'movement_type' => $type,
            'packages_delta' => $packagesDelta,
            'fraction_delta' => $fractionDelta->toString(),
            'stock_packages_after' => $drug->stock_packages,
            'open_fraction_balance_after' => $drug->openBalance()->toString(),
            'notes' => $notes,
        ]);
    }
}
