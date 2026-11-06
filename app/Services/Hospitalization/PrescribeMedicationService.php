<?php

declare(strict_types=1);

namespace App\Services\Hospitalization;

use App\Enums\KardexStatus;
use App\Enums\PrescriptionStatus;
use App\Events\KardexUpdated;
use App\Exceptions\Prescription\PrescriptionNotAllowedException;
use App\Models\Drug;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Models\Prescription;
use Illuminate\Database\ConnectionInterface;

/**
 * Persists a PrescriptionPlan: one prescriptions row plus one pending
 * kardex_schedules row per planned dose, in a single transaction.
 *
 * The stay is read under a shared lock (the first step of the kárdex lock
 * order, see KardexAdministrationService) so a concurrent discharge cannot
 * commit between the "is it still admitted?" check and the inserts.
 */
final readonly class PrescribeMedicationService
{
    public function __construct(
        private ConnectionInterface $db,
        private KardexNotifier $notifier,
    ) {}

    /** @throws PrescriptionNotAllowedException */
    public function prescribe(Hospitalization $hospitalization, PrescriptionPlan $plan, int $prescriberId, ?string $instructions): Prescription
    {
        return $this->db->transaction(function () use ($hospitalization, $plan, $prescriberId, $instructions): Prescription {
            $stay = Hospitalization::query()->whereKey($hospitalization->getKey())->sharedLock()->firstOrFail();

            if (! $stay->isActive()) {
                throw PrescriptionNotAllowedException::stayClosed();
            }

            // Re-read: the plan's Drug may be stale (loaded before the form was
            // submitted, or freshly created without DB defaults hydrated).
            $isActive = Drug::query()->whereKey($plan->drug->getKey())->value('is_active');

            if (! $isActive) {
                throw PrescriptionNotAllowedException::drugInactive();
            }

            $prescription = $stay->prescriptions()->create([
                'clinic_id' => $stay->clinic_id,
                'drug_id' => $plan->drug->getKey(),
                'prescribed_by' => $prescriberId,
                'dose_mode' => $plan->mode,
                'dose_per_kg' => $plan->dosePerKg,
                'weight_kg' => $plan->weightKg,
                'dose_amount' => $plan->dose->toString(),
                'route' => $plan->route,
                'frequency_hours' => $plan->frequencyHours,
                'starts_at' => $plan->startsAt,
                'ends_at' => $plan->endsAt,
                'doses_planned' => $plan->doseCount(),
                'instructions' => $instructions,
                'status' => PrescriptionStatus::Active,
            ]);

            $now = now();
            $rows = array_map(fn ($time) => [
                'clinic_id' => $stay->clinic_id,
                'hospitalization_id' => $stay->getKey(),
                'prescription_id' => $prescription->getKey(),
                'drug_id' => $plan->drug->getKey(),
                'prescribed_by' => $prescriberId,
                'dose_amount' => $plan->dose->toString(),
                'dose_per_kg' => $plan->dosePerKg,
                'weight_kg_at_prescription' => $plan->weightKg,
                'route' => $plan->route->value,
                'scheduled_at' => $time,
                'status' => KardexStatus::Pending->value,
                'created_at' => $now,
                'updated_at' => $now,
            ], $plan->times);

            // Bulk insert: a 14-day hourly order is 336 rows; one statement per chunk.
            foreach (array_chunk($rows, 200) as $chunk) {
                KardexSchedule::query()->insert($chunk);
            }

            // One signal per order is enough: boards re-query on any KardexUpdated.
            $first = $prescription->schedules()->orderBy('scheduled_at')->firstOrFail();
            $this->notifier->notify(KardexUpdated::scheduleChanged($first, 'prescribed'));

            return $prescription;
        });
    }
}
