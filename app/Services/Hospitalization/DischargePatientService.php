<?php

declare(strict_types=1);

namespace App\Services\Hospitalization;

use App\Enums\HospitalizationStatus;
use App\Models\Hospitalization;
use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;
use LogicException;

/**
 * Closes a stay. HospitalizationObserver cancels the pending doses and
 * broadcasts the change to every board, inside this same transaction.
 */
final readonly class DischargePatientService
{
    public function __construct(private ConnectionInterface $db) {}

    public function discharge(Hospitalization $hospitalization, HospitalizationStatus $outcome, ?string $notes): void
    {
        if (! in_array($outcome, HospitalizationStatus::dischargeOptions(), true)) {
            throw new InvalidArgumentException("{$outcome->value} is not a discharge outcome.");
        }

        $this->db->transaction(function () use ($hospitalization, $outcome, $notes): void {
            $locked = Hospitalization::query()->whereKey($hospitalization->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isActive()) {
                throw new LogicException('La hospitalización ya estaba cerrada.');
            }

            $locked->forceFill([
                'status' => $outcome,
                'discharge_notes' => $notes,
            ])->save();

            $hospitalization->setRawAttributes($locked->getAttributes(), sync: true);
        });
    }
}
