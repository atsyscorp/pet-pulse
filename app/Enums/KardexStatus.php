<?php

declare(strict_types=1);

namespace App\Enums;

enum KardexStatus: string
{
    case Pending = 'pending';
    case Administered = 'administered';
    case Omitted = 'omitted';
    case Cancelled = 'cancelled';

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
