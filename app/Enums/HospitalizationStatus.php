<?php

declare(strict_types=1);

namespace App\Enums;

enum HospitalizationStatus: string
{
    case Admitted = 'admitted';
    case Discharged = 'discharged';
    case VoluntaryDischarge = 'voluntary_discharge';
    case Transferred = 'transferred';
    case Deceased = 'deceased';

    public function isActive(): bool
    {
        return $this === self::Admitted;
    }
}
