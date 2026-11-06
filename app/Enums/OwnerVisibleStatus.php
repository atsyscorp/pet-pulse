<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The status an owner sees in the app. Deliberately coarser than the internal
 * triage level, and always set explicitly by the clinic.
 */
enum OwnerVisibleStatus: string
{
    case Stable = 'stable';
    case Observation = 'observation';
    case Delicate = 'delicate';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Stable => 'Estable',
            self::Observation => 'En observación',
            self::Delicate => 'Delicado',
            self::Critical => 'Crítico',
        };
    }
}
