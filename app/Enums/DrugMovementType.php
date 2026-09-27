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
}
