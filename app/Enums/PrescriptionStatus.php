<?php

declare(strict_types=1);

namespace App\Enums;

enum PrescriptionStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Suspended => 'Suspendida',
            self::Completed => 'Finalizada',
        };
    }
}
