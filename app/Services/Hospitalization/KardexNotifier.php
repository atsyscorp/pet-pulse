<?php

declare(strict_types=1);

namespace App\Services\Hospitalization;

use App\Events\KardexUpdated;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;

/**
 * The only way the domain announces a kárdex change to the screens.
 *
 * Two guarantees:
 *  1. After commit: the signal is sent once the outermost transaction commits,
 *     so no screen ever shows a change that was rolled back.
 *  2. Best effort: KardexUpdated broadcasts synchronously (ShouldBroadcastNow).
 *     If Reverb is down or unreachable, the broadcaster throws *after the data
 *     is already committed*. Letting that escape would show the nurse an error
 *     for a dose that was in fact recorded, inviting a dangerous retry. The
 *     failure is reported to the log instead; boards still catch up through
 *     their 60 s poll.
 */
final readonly class KardexNotifier
{
    public function __construct(
        private ConnectionInterface $db,
        private Dispatcher $events,
    ) {}

    public function notify(KardexUpdated $event): void
    {
        $this->db->afterCommit(function () use ($event): void {
            rescue(fn () => $this->events->dispatch($event), report: true);
        });
    }
}
