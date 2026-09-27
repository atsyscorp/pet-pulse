<?php

declare(strict_types=1);

namespace Tests\Unit\Pharmacy;

use App\Support\Pharmacy\DoseCalculator;
use App\Support\Pharmacy\Quantity;
use Brick\Math\Exception\RoundingNecessaryException;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    public function test_arithmetic_is_exact_at_four_decimals(): void
    {
        $this->assertSame('0.3000', Quantity::of('0.1')->plus('0.2')->toString());
        $this->assertSame('9.9875', Quantity::of(10)->minus('0.0125')->toString());
        $this->assertSame('0.0125', Quantity::of('0.012500')->format());
    }

    public function test_it_refuses_to_silently_truncate_extra_precision(): void
    {
        $this->expectException(RoundingNecessaryException::class);

        Quantity::of('0.00001');
    }

    public function test_packages_needed_rounds_up(): void
    {
        $this->assertSame(2, Quantity::of('15')->packagesNeeded(Quantity::of('10')));
        $this->assertSame(1, Quantity::of('10')->packagesNeeded(Quantity::of('10')));
        $this->assertSame(1, Quantity::of('0.0001')->packagesNeeded(Quantity::of('10')));
    }

    public function test_dose_calculator_rounds_once_at_the_end(): void
    {
        $calculator = new DoseCalculator;

        // Buprenorphine 0.02 mg/kg × 3.85 kg ÷ 0.3 mg/mL = 0.25666… mL → 0.2567
        $this->assertSame('0.2567', $calculator->amountFor('0.02', '3.85', '0.3')->toString());
        // Ketamine 0.5 mg/kg × 32.4 kg ÷ 50 mg/mL = 0.324 mL
        $this->assertSame('0.3240', $calculator->amountFor('0.5', '32.4', '50')->toString());
    }
}
