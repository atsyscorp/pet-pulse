<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Patient> */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'name' => fake()->randomElement(['Luna', 'Max', 'Rocky', 'Kira', 'Simón', 'Mía', 'Toby', 'Lola', 'Zeus', 'Nala']),
            'species' => fake()->randomElement(['Canino', 'Felino']),
            'breed' => fake()->randomElement(['Criollo', 'Labrador', 'Pastor Alemán', 'Siamés', 'Persa', null]),
            'sex' => fake()->randomElement(['M', 'H']),
            'birth_date' => fake()->dateTimeBetween('-14 years', '-3 months'),
            'owner_name' => fake()->name(),
            'owner_phone' => fake()->numerify('3#########'),
        ];
    }
}
