<?php

declare(strict_types=1);

namespace App\Support\Pharmacy;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * Converts a weight-based prescription into an administrable amount.
 *
 *   volume (fraction_unit) = dose_per_kg (mg/kg) × weight (kg) ÷ concentration (mg / fraction_unit)
 *
 * Intermediate math runs at scale 10 and the result is rounded HALF_UP once,
 * at the end, to 4 decimals — rounding intermediate steps compounds error.
 */
final class DoseCalculator
{
    private const int INTERMEDIATE_SCALE = 10;

    public function amountFor(string $dosePerKg, string $weightKg, string $concentrationPerUnit): Quantity
    {
        $concentration = BigDecimal::of($concentrationPerUnit);

        if (! $concentration->isPositive()) {
            throw new InvalidArgumentException('Drug concentration must be greater than zero.');
        }

        $raw = BigDecimal::of($dosePerKg)
            ->multipliedBy($weightKg)
            ->dividedBy($concentration, self::INTERMEDIATE_SCALE, RoundingMode::HalfUp);

        return Quantity::rounded($raw);
    }
}
