<?php

declare(strict_types=1);

namespace App\Services\Pharmacy;

use App\Enums\DrugMovementType;
use App\Models\Drug;
use App\Support\Pharmacy\Quantity;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;

/**
 * Adds sealed packages to stock (a purchase or a transfer in). Goes through
 * the same row lock and ledger as administrations, so the ledger stays the
 * single source of truth for every unit that enters or leaves.
 */
final readonly class ReceiveDrugStockService
{
    public function __construct(
        private ConnectionInterface $db,
        private DrugInventoryLedger $ledger,
    ) {}

    public function receive(Drug $drug, int $packages, ?int $userId, ?string $notes): void
    {
        if ($packages < 1) {
            throw new InvalidArgumentException('At least one package must be received.');
        }

        $this->db->transaction(function () use ($drug, $packages, $userId, $notes): void {
            $locked = Drug::query()->whereKey($drug->getKey())->lockForUpdate()->firstOrFail();

            $locked->stock_packages += $packages;
            $locked->save();

            $this->ledger->record(
                drug: $locked,
                type: DrugMovementType::PackageReceived,
                packagesDelta: $packages,
                fractionDelta: Quantity::zero(),
                source: null,
                userId: $userId,
                notes: $notes,
            );

            $drug->setRawAttributes($locked->getAttributes(), sync: true);
        });
    }
}
