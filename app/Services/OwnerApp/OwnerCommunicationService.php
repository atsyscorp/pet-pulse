<?php

declare(strict_types=1);

namespace App\Services\OwnerApp;

use App\Enums\OwnerVisibleStatus;
use App\Models\Hospitalization;
use App\Models\OwnerUpdate;

/** What the clinic tells the owner about a stay: a status and written updates. */
final class OwnerCommunicationService
{
    public function setStatus(Hospitalization $stay, ?OwnerVisibleStatus $status): void
    {
        $stay->forceFill([
            'owner_status' => $status,
            'owner_status_updated_at' => $status !== null ? now() : null,
        ])->save();
    }

    public function publishUpdate(Hospitalization $stay, int $authorId, string $body): OwnerUpdate
    {
        return $stay->ownerUpdates()->create([
            'clinic_id' => $stay->clinic_id,
            'author_id' => $authorId,
            'body' => trim($body),
        ]);
    }
}
