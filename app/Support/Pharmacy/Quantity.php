<?php

declare(strict_types=1);

namespace App\Support\Pharmacy;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Stringable;

/**
 * Immutable fixed-point pharmacological quantity (scale 4).
 *
 * Why not float: 0.1 + 0.2 !== 0.3 in IEEE-754. Across hundreds of 0.0125 mL
 * micro-doses the drift becomes a real stock discrepancy (and, for controlled
 * substances, a regulatory one). brick/math uses ext-bcmath / ext-gmp when
 * available and falls back to a pure-PHP implementation otherwise, so results
 * are exact on every host. Values are exchanged with the DB as strings, which
 * is also what Eloquent's `decimal:4` cast produces.
 */
final readonly class Quantity implements Stringable
{
    public const int SCALE = 4;

    private function __construct(private BigDecimal $value) {}

    public static function of(BigNumber|Quantity|int|string $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        // UNNECESSARY: refuse to silently round input that exceeds 4 decimals.
        return new self(BigDecimal::of($value)->toScale(self::SCALE, RoundingMode::Unnecessary));
    }

    /** Explicitly round a computed value (e.g. mg/kg × kg ÷ mg/mL) to 4 decimals. */
    public static function rounded(BigNumber|int|string $value): self
    {
        return new self(BigDecimal::of($value)->toScale(self::SCALE, RoundingMode::HalfUp));
    }

    public static function zero(): self
    {
        return self::of(0);
    }

    public function plus(self|int|string $other): self
    {
        return new self($this->value->plus(self::of($other)->value));
    }

    public function minus(self|int|string $other): self
    {
        return new self($this->value->minus(self::of($other)->value));
    }

    public function multipliedBy(int $factor): self
    {
        return new self($this->value->multipliedBy($factor));
    }

    public function negated(): self
    {
        return new self($this->value->negated());
    }

    public function isLessThan(self|int|string $other): bool
    {
        return $this->value->isLessThan(self::of($other)->value);
    }

    public function isGreaterThan(self|int|string $other): bool
    {
        return $this->value->isGreaterThan(self::of($other)->value);
    }

    public function isEqualTo(self|int|string $other): bool
    {
        return $this->value->isEqualTo(self::of($other)->value);
    }

    public function isPositive(): bool
    {
        return $this->value->isPositive();
    }

    public function isNegative(): bool
    {
        return $this->value->isNegative();
    }

    public function isZero(): bool
    {
        return $this->value->isZero();
    }

    /** Smallest number of whole packages of size $perPackage needed to cover this quantity. */
    public function packagesNeeded(self $perPackage): int
    {
        return $this->value
            ->dividedBy($perPackage->value, 0, RoundingMode::Ceiling)
            ->toInt();
    }

    public function toBigDecimal(): BigDecimal
    {
        return $this->value;
    }

    /** Canonical DB representation, e.g. "0.0125". */
    public function toString(): string
    {
        return (string) $this->value;
    }

    /** Human display without trailing zeros, e.g. "0.0125", "2.5", "10". */
    public function format(): string
    {
        return (string) $this->value->strippedOfTrailingZeros();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
