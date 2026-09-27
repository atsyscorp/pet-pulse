<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Clinic> */
class ClinicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Clínica Veterinaria '.fake()->unique()->lastName(),
            'tax_id' => fake()->unique()->numerify('9########-#'),
            'country_code' => 'CO',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ];
    }
}
