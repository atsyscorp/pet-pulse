<?php

declare(strict_types=1);

namespace App\Services\Hospitalization;

use App\Enums\DrugMovementType;
use App\Enums\KardexStatus;
use App\Events\KardexUpdated;
use App\Exceptions\Kardex\InsufficientStockException;
use App\Exceptions\Kardex\ScheduleNotAdministrableException;
use App\Models\Drug;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Models\User;
use App\Services\Pharmacy\DrugInventoryLedger;
use App\Support\Pharmacy\Quantity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;

/**
 * Registers a bedside administration and depletes fractional stock atomically.
 *
 * ─── Concurrency model ──────────────────────────────────────────────────────
 * Two nurses can tap "administered" for different patients that share the same
 * multi-dose vial at the same second, or the same nurse can double-tap on a
 * flaky ICU Wi-Fi. Every step below runs in ONE transaction and takes row
 * locks in a fixed global order to make deadlocks structurally impossible
 * between the flows that touch these tables:
 *
 *     hospitalizations (shared)  →  kardex_schedules (exclusive)  →  drugs (exclusive)
 *
 * The discharge flow (HospitalizationObserver) takes hospitalization → schedules,
 * i.e. a prefix of the same order. Transient deadlock/serialization failures
 * that still occur under load (gap locks, index contention) are retried by
 * DB::transaction($cb, attempts: 3), which only retries concurrency errors.
 *
 * ─── Why re-read under lock ─────────────────────────────────────────────────
 * The $schedule passed in was loaded by the UI *before* the lock; it may be
 * stale. We only trust its primary key and immutable FKs, then re-read state
 * with lockForUpdate(). The idempotency check (status must be pending) is
 * therefore evaluated on the locked row, so a double-tap yields exactly one
 * administration and one stock deduction.
 *
 * ─── Why broadcast after commit ─────────────────────────────────────────────
 * KardexUpdated implements ShouldDispatchAfterCommit: if this service is ever
 * wrapped by an outer transaction that rolls back, wall displays never see a
 * phantom administration.
 */
final readonly class KardexAdministrationService
{
    private const int TRANSACTION_ATTEMPTS = 3;

    public function __construct(
        private ConnectionInterface $db,
        private DrugInventoryLedger $ledger,
        private Dispatcher $events,
    ) {}

    /**
     * @throws ScheduleNotAdministrableException when the dose is no longer pending, the stay is closed,
     *                                           the drug is inactive, or controlled-drug notes are missing
     * @throws InsufficientStockException when sealed + open stock cannot cover the dose
     * @throws AuthorizationException when $userId is not an active clinical user of this clinic
     */
    public function administerDose(KardexSchedule $schedule, int $userId, ?string $notes): void
    {
        $notes = $this->normalizeNotes($notes);
        $administeredAt = Carbon::now();

        $this->db->transaction(function () use ($schedule, $userId, $notes, $administeredAt): void {
            // 1) Lock order step 1 — hospitalization (shared lock). Blocks a concurrent
            //    discharge from committing mid-administration, while still allowing
            //    other nurses to administer to the same patient in parallel.
            $hospitalization = Hospitalization::query()
                ->whereKey($schedule->hospitalization_id)
                ->sharedLock()
                ->firstOrFail();

            // 2) Lock order step 2 — the schedule row itself (exclusive). Serialises
            //    double-taps; the second one sees status=administered and aborts.
            $locked = KardexSchedule::query()
                ->whereKey($schedule->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status->isFinal()) {
                throw ScheduleNotAdministrableException::alreadyFinal($locked);
            }

            if (! $hospitalization->isActive()) {
                throw ScheduleNotAdministrableException::hospitalizationClosed($locked);
            }

            $this->assertClinicalUser($userId, (int) $locked->clinic_id);

            // 3) Lock order step 3 — the drug row (exclusive). Every writer of
            //    stock_packages/open_fraction_balance goes through this lock.
            $drug = Drug::query()
                ->whereKey($locked->drug_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $drug->is_active) {
                throw ScheduleNotAdministrableException::drugInactive($locked);
            }

            if ($drug->is_controlled && $notes === null) {
                throw ScheduleNotAdministrableException::controlledSubstanceRequiresNotes();
            }

            $dose = $locked->dose();

            // 4) Discard an open unit that exceeded its in-use stability window
            //    (e.g. reconstituted ceftriaxone: 24 h). It must not be administered
            //    and its remainder is booked as waste, not silently zeroed.
            $this->discardExpiredOpenUnit($drug, $userId, $administeredAt);

            // 5) Feasibility check BEFORE mutating anything, so the error message
            //    reports the real shortage rather than failing half-way.
            $available = $drug->totalAvailable();

            if ($available->isLessThan($dose)) {
                throw InsufficientStockException::for($drug, $dose->format(), $available->format());
            }

            // 6) Automatic unsealing. A dose larger than one presentation
            //    (e.g. 15 mL from 10 mL vials) opens as many packages as required;
            //    each opening is its own ledger row for traceability.
            while ($drug->openBalance()->isLessThan($dose)) {
                $this->openPackage($drug, $locked, $userId, $administeredAt);
            }

            // 7) Micro-dose depletion from the open balance.
            $remaining = $drug->openBalance()->minus($dose);
            $drug->open_fraction_balance = $remaining->toString();

            if ($remaining->isZero()) {
                // Unit fully consumed: stability clock no longer applies.
                $drug->opened_at = null;
            }

            $drug->save();

            $this->ledger->record(
                drug: $drug,
                type: DrugMovementType::DoseAdministered,
                packagesDelta: 0,
                fractionDelta: $dose->negated(),
                source: $locked,
                userId: $userId,
                notes: $notes,
            );

            // 8) Close the kárdex entry.
            $locked->forceFill([
                'status' => KardexStatus::Administered,
                'administered_at' => $administeredAt,
                'administered_by' => $userId,
                'notes' => $notes,
            ])->save();

            // Keep the caller's instance in sync with what was persisted.
            $schedule->setRawAttributes($locked->getAttributes(), sync: true);

            // 9) Real-time fan-out; deferred until the outermost commit.
            $this->events->dispatch(KardexUpdated::administered($locked, $drug, $userId));
        }, self::TRANSACTION_ATTEMPTS);
    }

    /**
     * Converts one sealed package into open volume. Caller holds the drug lock.
     */
    private function openPackage(Drug $drug, KardexSchedule $source, int $userId, Carbon $at): void
    {
        $volume = $drug->packageVolume();

        // Guarded by the feasibility check; asserting keeps the invariant local.
        if ($drug->stock_packages < 1) {
            throw InsufficientStockException::for($drug, $source->dose()->format(), $drug->totalAvailable()->format());
        }

        $drug->stock_packages -= 1;
        $drug->open_fraction_balance = $drug->openBalance()->plus($volume)->toString();
        $drug->opened_at = $at;

        $this->ledger->record(
            drug: $drug,
            type: DrugMovementType::PackageOpened,
            packagesDelta: -1,
            fractionDelta: $volume,
            source: $source,
            userId: $userId,
            notes: sprintf('Apertura automática de %s (%s %s) para dosis #%d.',
                $drug->presentation_unit, $volume->format(), $drug->fraction_unit, $source->getKey()),
        );
    }

    private function discardExpiredOpenUnit(Drug $drug, int $userId, Carbon $at): void
    {
        $balance = $drug->openBalance();

        if (! $balance->isPositive() || ! $drug->openUnitExpired($at)) {
            return;
        }

        $drug->open_fraction_balance = Quantity::zero()->toString();
        $drug->opened_at = null;

        $this->ledger->record(
            drug: $drug,
            type: DrugMovementType::ExpiredWaste,
            packagesDelta: 0,
            fractionDelta: $balance->negated(),
            source: null,
            userId: $userId,
            notes: sprintf('Descarte por vencimiento de estabilidad (%d h desde apertura).', $drug->stability_hours_after_opening),
        );
    }

    /**
     * The acting user comes in as a bare id (the service is also called from
     * jobs and the HTTP API), so re-validate tenant membership and role here.
     * The UI/HTTP layers additionally enforce KardexSchedulePolicy.
     */
    private function assertClinicalUser(int $userId, int $clinicId): void
    {
        $user = User::query()->whereKey($userId)->first();

        if ($user === null || ! $user->belongsToClinic($clinicId) || ! $user->role->canAdministerMedication()) {
            throw new AuthorizationException('El usuario no está autorizado para administrar medicamentos en esta clínica.');
        }
    }

    private function normalizeNotes(?string $notes): ?string
    {
        $notes = $notes !== null ? trim($notes) : null;

        return $notes === '' ? null : $notes;
    }
}
