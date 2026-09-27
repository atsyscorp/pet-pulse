<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Drug;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Drug> */
class DrugFactory extends Factory
{
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'name' => 'Meloxicam 0.5%',
            'active_ingredient' => 'Meloxicam',
            'concentration_amount' => '5.0000',
            'concentration_unit' => 'mg',
            'presentation_unit' => 'vial',
            'volume_per_presentation' => '10.0000',
            'fraction_unit' => 'mL',
            'stock_packages' => 5,
            'open_fraction_balance' => '0.0000',
            'reorder_level_packages' => 1,
            'is_controlled' => false,
            'is_active' => true,
        ];
    }

    public function controlled(): static
    {
        return $this->state(fn () => [
            'name' => 'Ketamina 50 mg/mL',
            'active_ingredient' => 'Ketamina',
            'concentration_amount' => '50.0000',
            'is_controlled' => true,
        ]);
    }

    public function stock(int $packages, string $openBalance = '0.0000'): static
    {
        return $this->state(fn () => [
            'stock_packages' => $packages,
            'open_fraction_balance' => $openBalance,
            'opened_at' => BigDecimal::of($openBalance)->isPositive() ? now() : null,
        ]);
    }
}
