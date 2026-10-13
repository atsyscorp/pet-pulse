<?php

declare(strict_types=1);

namespace App\Enums;

enum DoseMode: string
{
    /** Dose per kilogram (e.g. mg/kg); the amount is computed from weight and concentration. */
    case PerKg = 'per_kg';

    /** A fixed amount in the drug's fraction unit (e.g. 1 tab, 0,5 mL). */
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::PerKg => 'Por peso',
            self::Fixed => 'Dosis fija',
        };
    }
}
