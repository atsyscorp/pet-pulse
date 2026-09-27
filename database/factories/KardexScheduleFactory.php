<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdministrationRoute;
use App\Enums\KardexStatus;
use App\Models\Drug;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KardexSchedule> */
class KardexScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hospitalization_id' => Hospitalization::factory(),
            'clinic_id' => fn (array $a) => Hospitalization::query()->withoutGlobalScopes()->whereKey($a['hospitalization_id'])->value('clinic_id'),
            'drug_id' => fn (array $a) => Drug::factory()->create(['clinic_id' => $a['clinic_id']]),
            'dose_amount' => '1.2000',
            'route' => AdministrationRoute::Intravenous,
            'scheduled_at' => now(),
            'status' => KardexStatus::Pending,
        ];
    }

    public function dose(string $amount): static
    {
        return $this->state(fn () => ['dose_amount' => $amount]);
    }
}
