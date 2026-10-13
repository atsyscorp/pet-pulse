<?php

declare(strict_types=1);

namespace App\Enums;

enum DrugMovementType: string
{
    case PackageReceived = 'package_received';
    case PackageOpened = 'package_opened';
    case DoseAdministered = 'dose_administered';
    case Waste = 'waste';
    case ExpiredWaste = 'expired_waste';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::PackageReceived => 'Entrada',
            self::PackageOpened => 'Apertura',
            self::DoseAdministered => 'Administración',
            self::Waste => 'Descarte',
            self::ExpiredWaste => 'Descarte por vencimiento',
            self::Adjustment => 'Ajuste',
        };
    }
}
