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

    public function label(): string
    {
        return match ($this) {
            self::Admitted => 'Hospitalizado',
            self::Discharged => 'Alta médica',
            self::VoluntaryDischarge => 'Alta voluntaria',
            self::Transferred => 'Remitido',
            self::Deceased => 'Fallecido',
        };
    }

    /** Outcomes a clinician can choose when closing a stay. */
    public static function dischargeOptions(): array
    {
        return [self::Discharged, self::VoluntaryDischarge, self::Transferred, self::Deceased];
    }
}
