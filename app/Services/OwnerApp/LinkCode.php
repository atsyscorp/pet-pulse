<?php

declare(strict_types=1);

namespace App\Services\OwnerApp;

/**
 * Format and normalisation of the codes clinics hand to owners.
 *
 * 8 characters from a 31-symbol alphabet without look-alikes (no 0/O, 1/I/L),
 * shown as "ABCD-2345": about 8.5 × 10^11 combinations. Combined with a short
 * lifetime, single use and per-owner rate limiting, guessing is not practical.
 */
final class LinkCode
{
    public const string ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const int LENGTH = 8;

    /** Prefix inside the QR, so the app can tell a PetPulse code from any other QR. */
    public const string QR_PREFIX = 'PETPULSE:';

    public static function generate(): string
    {
        $chars = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < self::LENGTH; $i++) {
            $chars .= self::ALPHABET[random_int(0, $max)];
        }

        return $chars;
    }

    /** "abcd 2345", "ABCD-2345" and "PETPULSE:ABCD2345" all become "ABCD2345". */
    public static function normalize(string $input): string
    {
        $input = strtoupper(trim($input));

        if (str_starts_with($input, self::QR_PREFIX)) {
            $input = substr($input, strlen(self::QR_PREFIX));
        }

        return preg_replace('/[^A-Z0-9]/', '', $input) ?? '';
    }

    public static function display(string $code): string
    {
        return substr($code, 0, 4).'-'.substr($code, 4);
    }

    public static function hash(string $normalized): string
    {
        return hash('sha256', $normalized);
    }
}
