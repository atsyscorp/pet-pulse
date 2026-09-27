<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Drug;
use App\Models\KardexSchedule;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Pushed to every nursing board / ICU wall display of the clinic.
 *
 * ShouldBroadcastNow (not ShouldBroadcast): the bedside confirmation must be
 * visible on the other tablets within the same second; routing it through a
 * busy queue would add latency exactly when the ICU is busiest. Reverb's
 * publish is a single local HTTP call, so the cost to the request is small.
 *
 * The payload is intentionally minimal and contains no owner PII (Ley 1581):
 * listeners use it as an invalidation signal and re-query through the tenant
 * scope, which also guarantees they render authorised data only.
 *
 * Broadcast name defaults to the FQCN, which Laravel Echo resolves from the
 * short name `KardexUpdated` (Echo prefixes the `App.Events` namespace).
 */
final class KardexUpdated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $clinicId,
        public readonly string $action,
        public readonly int $scheduleId,
        public readonly int $hospitalizationId,
        public readonly string $status,
        public readonly ?string $administeredAt,
        public readonly ?int $userId,
        public readonly ?int $drugId = null,
        public readonly ?string $drugName = null,
        public readonly ?int $stockPackages = null,
        public readonly ?string $openFractionBalance = null,
        public readonly bool $lowStock = false,
    ) {}

    public static function administered(KardexSchedule $schedule, Drug $drug, int $userId): self
    {
        return new self(
            clinicId: (int) $schedule->clinic_id,
            action: 'administered',
            scheduleId: $schedule->getKey(),
            hospitalizationId: (int) $schedule->hospitalization_id,
            status: $schedule->status->value,
            administeredAt: $schedule->administered_at?->toIso8601String(),
            userId: $userId,
            drugId: $drug->getKey(),
            drugName: $drug->name,
            stockPackages: $drug->stock_packages,
            openFractionBalance: $drug->openBalance()->toString(),
            lowStock: $drug->isBelowReorderLevel(),
        );
    }

    public static function scheduleChanged(KardexSchedule $schedule, string $action): self
    {
        return new self(
            clinicId: (int) $schedule->clinic_id,
            action: $action,
            scheduleId: $schedule->getKey(),
            hospitalizationId: (int) $schedule->hospitalization_id,
            status: $schedule->status->value,
            administeredAt: $schedule->administered_at?->toIso8601String(),
            userId: $schedule->administered_by !== null ? (int) $schedule->administered_by : null,
        );
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("clinic.{$this->clinicId}.icu")];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'schedule_id' => $this->scheduleId,
            'hospitalization_id' => $this->hospitalizationId,
            'status' => $this->status,
            'administered_at' => $this->administeredAt,
            'user_id' => $this->userId,
            'drug' => $this->drugId === null ? null : [
                'id' => $this->drugId,
                'name' => $this->drugName,
                'stock_packages' => $this->stockPackages,
                'open_fraction_balance' => $this->openFractionBalance,
                'low_stock' => $this->lowStock,
            ],
        ];
    }
}
