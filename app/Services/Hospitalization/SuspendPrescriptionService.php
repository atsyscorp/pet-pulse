<?php

declare(strict_types=1);

namespace App\Services\Hospitalization;

use App\Enums\KardexStatus;
use App\Enums\PrescriptionStatus;
use App\Events\KardexUpdated;
use App\Exceptions\Prescription\PrescriptionNotAllowedException;
use App\Models\KardexSchedule;
use App\Models\Prescription;
use Illuminate\Database\ConnectionInterface;

/**
 * Stops an order: every dose still pending (future or overdue) is cancelled;
 * doses already administered stay in the record untouched.
 *
 * Pending rows are locked FOR UPDATE, the same lock a nurse's administration
 * takes, so a dose being given at this exact moment either completes first
 * (and stays administered) or sees "cancelled" and is refused.
 */
final readonly class SuspendPrescriptionService
{
    public function __construct(
        private ConnectionInterface $db,
        private KardexNotifier $notifier,
    ) {}

    /** @throws PrescriptionNotAllowedException */
    public function suspend(Prescription $prescription, int $userId, string $reason): int
    {
        return $this->db->transaction(function () use ($prescription, $userId, $reason): int {
            $locked = Prescription::query()->whereKey($prescription->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isActive()) {
                throw PrescriptionNotAllowedException::notActive();
            }

            $pending = $locked->schedules()
                ->where('status', KardexStatus::Pending)
                ->orderBy('scheduled_at')
                ->lockForUpdate()
                ->get();

            $note = 'Cancelada: prescripción suspendida. '.$reason;
            $pending->each(fn (KardexSchedule $s) => $s->forceFill([
                'status' => KardexStatus::Cancelled,
                'notes' => trim(($s->notes ?? '')."\n".$note),
            ])->save());

            $locked->forceFill([
                'status' => PrescriptionStatus::Suspended,
                'suspended_at' => now(),
                'suspended_by' => $userId,
                'suspension_reason' => $reason,
            ])->save();

            $prescription->setRawAttributes($locked->getAttributes(), sync: true);

            if ($pending->isNotEmpty()) {
                $this->notifier->notify(KardexUpdated::scheduleChanged($pending->first(), 'prescription_suspended'));
            }

            return $pending->count();
        });
    }
}
