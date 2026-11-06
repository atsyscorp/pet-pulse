<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\HospitalizationStatus;
use App\Enums\KardexStatus;
use App\Enums\PrescriptionStatus;
use App\Events\KardexUpdated;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Services\Hospitalization\KardexNotifier;
use Illuminate\Support\Carbon;

/**
 * Keeps the kárdex consistent with the stay lifecycle.
 *
 * When a patient leaves the ward (discharge, transfer, death), every dose that
 * is still pending is cancelled so it disappears from the "due" lists on all
 * boards. Runs inside the caller's transaction when there is one; the
 * hospitalization row is already exclusively locked by the UPDATE that fired
 * this event, which is the first step of the kárdex lock order.
 */
final class HospitalizationObserver
{
    public function updating(Hospitalization $hospitalization): void
    {
        if ($hospitalization->isDirty('status')
            && ! $hospitalization->status->isActive()
            && $hospitalization->discharged_at === null) {
            $hospitalization->discharged_at = Carbon::now();
        }
    }

    public function updated(Hospitalization $hospitalization): void
    {
        if (! $hospitalization->wasChanged('status') || $hospitalization->status === HospitalizationStatus::Admitted) {
            return;
        }

        // The orders end with the stay; their remaining doses are cancelled below.
        $hospitalization->prescriptions()
            ->where('status', PrescriptionStatus::Active)
            ->update(['status' => PrescriptionStatus::Completed, 'updated_at' => Carbon::now()]);

        $cancelled = $hospitalization->kardexSchedules()
            ->where('status', KardexStatus::Pending)
            ->lockForUpdate()
            ->get()
            ->each(function (KardexSchedule $schedule) use ($hospitalization): void {
                $schedule->forceFill([
                    'status' => KardexStatus::Cancelled,
                    'notes' => trim(($schedule->notes ?? '')."\nCancelada automáticamente: egreso ({$hospitalization->status->value})."),
                ])->save();
            });

        // One signal per discharge: boards re-query on any KardexUpdated.
        if ($cancelled->isNotEmpty()) {
            app(KardexNotifier::class)->notify(KardexUpdated::scheduleChanged($cancelled->first(), 'cancelled'));
        }
    }
}
