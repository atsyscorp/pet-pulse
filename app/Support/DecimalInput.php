<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalises numbers typed by staff. In Colombia the decimal separator is a
 * comma ("2,5"), but tablets often offer a dot; both are accepted. Thousands
 * separators are not: "1.250" is ambiguous at the bedside and is rejected by
 * the validation regex before it gets here.
 */
final class DecimalInput
{
    /** Up to 6 integer digits and 4 decimals, comma or dot. */
    public const string PATTERN = '/^\d{1,6}([.,]\d{1,4})?$/';

    public static function normalize(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === null || $value === '' ? null : str_replace(',', '.', $value);
    }
}
