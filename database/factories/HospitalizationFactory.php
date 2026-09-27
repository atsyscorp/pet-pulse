<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HospitalizationStatus;
use App\Enums\TriageLevel;
use App\Models\Clinic;
use App\Models\Hospitalization;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Hospitalization> */
class HospitalizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'patient_id' => fn (array $attributes) => Patient::factory()->create(['clinic_id' => $attributes['clinic_id']]),
            'bed_label' => 'UCI-'.fake()->numberBetween(1, 12),
            'triage_level' => fake()->randomElement(TriageLevel::cases()),
            'current_weight_kg' => (string) fake()->randomFloat(3, 2, 45),
            'weight_recorded_at' => now(),
            'admission_reason' => fake()->randomElement(['Gastroenteritis hemorrágica', 'Politraumatismo', 'Insuficiencia renal aguda', 'Postquirúrgico OVH']),
            'admitted_at' => now()->subDay(),
            'status' => HospitalizationStatus::Admitted,
        ];
    }

    public function triage(TriageLevel $level): static
    {
        return $this->state(fn () => ['triage_level' => $level]);
    }
}
