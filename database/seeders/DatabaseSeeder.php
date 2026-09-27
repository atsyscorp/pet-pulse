<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdministrationRoute;
use App\Enums\TriageLevel;
use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\Drug;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo ICU for local development: `php artisan migrate --seed`, then open
 * /dev/login/{id} (ids printed below) to land on the kárdex board.
 */
class DatabaseSeeder extends Seeder
{
    public function run(TenantContext $tenant): void
    {
        $clinic = Clinic::factory()->create(['name' => 'Clínica Veterinaria Los Andes', 'tax_id' => '900123456-7']);
        // A second tenant proves isolation: none of its data may appear on the board.
        $other = Clinic::factory()->create(['name' => 'Hospital Veterinario del Caribe', 'tax_id' => '900765432-1']);

        $tenant->run($clinic, fn () => $this->seedIcu($clinic));
        $tenant->run($other, fn () => $this->seedIcu($other));

        $this->command?->info('Demo users (clinic '.$clinic->id.'):');
        User::query()->where('clinic_id', $clinic->id)->get()->each(
            fn (User $u) => $this->command?->line("  /dev/login/{$u->id}  {$u->role->value}  {$u->email}"),
        );
    }

    private function seedIcu(Clinic $clinic): void
    {
        $slug = $clinic->id;

        User::factory()->for($clinic)->role(UserRole::Admin)->create(['name' => 'Admin', 'email' => "admin{$slug}@petpulse.test"]);
        $vet = User::factory()->for($clinic)->role(UserRole::Veterinarian)->create(['name' => 'Dra. Camila Restrepo', 'email' => "vet{$slug}@petpulse.test"]);
        User::factory()->for($clinic)->role(UserRole::Nurse)->create(['name' => 'Aux. Andrés Gómez', 'email' => "nurse{$slug}@petpulse.test"]);

        $drugs = [
            'ketamine' => Drug::create([
                'name' => 'Ketamina 50 mg/mL', 'active_ingredient' => 'Ketamina', 'concentration_amount' => '50',
                'concentration_unit' => 'mg', 'presentation_unit' => 'vial', 'volume_per_presentation' => '10',
                'fraction_unit' => 'mL', 'stock_packages' => 4, 'open_fraction_balance' => '0',
                'reorder_level_packages' => 2, 'stability_hours_after_opening' => 672, 'is_controlled' => true,
            ]),
            'meloxicam' => Drug::create([
                'name' => 'Meloxicam 0.5%', 'active_ingredient' => 'Meloxicam', 'concentration_amount' => '5',
                'concentration_unit' => 'mg', 'presentation_unit' => 'vial', 'volume_per_presentation' => '10',
                'fraction_unit' => 'mL', 'stock_packages' => 6, 'open_fraction_balance' => '3.4000',
                'opened_at' => now()->subDays(2), 'reorder_level_packages' => 2, 'stability_hours_after_opening' => 672,
            ]),
            'ceftriaxone' => Drug::create([
                'name' => 'Ceftriaxona 1 g (reconstituida)', 'active_ingredient' => 'Ceftriaxona', 'concentration_amount' => '100',
                'concentration_unit' => 'mg', 'presentation_unit' => 'vial', 'volume_per_presentation' => '10',
                'fraction_unit' => 'mL', 'stock_packages' => 12, 'open_fraction_balance' => '0',
                'reorder_level_packages' => 4, 'stability_hours_after_opening' => 24,
            ]),
            'maropitant' => Drug::create([
                'name' => 'Maropitant 10 mg/mL', 'active_ingredient' => 'Maropitant', 'concentration_amount' => '10',
                'concentration_unit' => 'mg', 'presentation_unit' => 'vial', 'volume_per_presentation' => '20',
                'fraction_unit' => 'mL', 'stock_packages' => 2, 'open_fraction_balance' => '0',
                'reorder_level_packages' => 1, 'stability_hours_after_opening' => 672,
            ]),
            'buprenorphine' => Drug::create([
                'name' => 'Buprenorfina 0.3 mg/mL', 'active_ingredient' => 'Buprenorfina', 'concentration_amount' => '0.3',
                'concentration_unit' => 'mg', 'presentation_unit' => 'ampolla', 'volume_per_presentation' => '1',
                'fraction_unit' => 'mL', 'stock_packages' => 10, 'open_fraction_balance' => '0',
                'reorder_level_packages' => 3, 'is_controlled' => true,
            ]),
            'omeprazole' => Drug::create([
                'name' => 'Omeprazol 20 mg tab', 'active_ingredient' => 'Omeprazol', 'concentration_amount' => '20',
                'concentration_unit' => 'mg', 'presentation_unit' => 'blíster', 'volume_per_presentation' => '14',
                'fraction_unit' => 'tab', 'stock_packages' => 3, 'open_fraction_balance' => '0',
                'reorder_level_packages' => 1,
            ]),
        ];

        $stays = [
            ['Rocky', 'Canino', 'Pastor Alemán', '32.400', TriageLevel::Critical, 'UCI-1', 'Politraumatismo por atropellamiento'],
            ['Mía', 'Felino', 'Criollo', '3.850', TriageLevel::Critical, 'UCI-2', 'Obstrucción uretral'],
            ['Toby', 'Canino', 'Beagle', '12.100', TriageLevel::Intermediate, 'INT-1', 'Gastroenteritis hemorrágica'],
            ['Luna', 'Canino', 'Criollo', '8.700', TriageLevel::Observation, 'OBS-3', 'Postquirúrgico OVH'],
        ];

        $today = Carbon::now($clinic->timezone)->startOfDay();

        foreach ($stays as [$name, $species, $breed, $weight, $triage, $bed, $reason]) {
            $patient = Patient::factory()->for($clinic)->create(['name' => $name, 'species' => $species, 'breed' => $breed]);

            $stay = Hospitalization::create([
                'patient_id' => $patient->id, 'attending_vet_id' => $vet->id, 'bed_label' => $bed,
                'triage_level' => $triage, 'current_weight_kg' => $weight, 'weight_recorded_at' => now(),
                'admission_reason' => $reason, 'admitted_at' => now()->subDay(),
            ]);

            // [drug, route, every N hours, first hour, dose (fraction units)]
            $plan = match ($triage) {
                TriageLevel::Critical => [
                    ['buprenorphine', AdministrationRoute::Intravenous, 6, 2, $species === 'Felino' ? '0.2567' : '2.1600'],
                    ['ceftriaxone', AdministrationRoute::Intravenous, 12, 8, $species === 'Felino' ? '0.9625' : '8.1000'],
                    ['maropitant', AdministrationRoute::Subcutaneous, 24, 9, $species === 'Felino' ? '0.3850' : '3.2400'],
                    ['ketamine', AdministrationRoute::Intravenous, 8, 4, $species === 'Felino' ? '0.0385' : '0.3240'],
                ],
                TriageLevel::Intermediate => [
                    ['ceftriaxone', AdministrationRoute::Intravenous, 12, 7, '3.0250'],
                    ['maropitant', AdministrationRoute::Subcutaneous, 24, 10, '1.2100'],
                    ['omeprazole', AdministrationRoute::Oral, 24, 7, '0.5000'],
                ],
                TriageLevel::Observation => [
                    ['meloxicam', AdministrationRoute::Subcutaneous, 24, 8, '0.1740'],
                    ['omeprazole', AdministrationRoute::Oral, 24, 8, '0.2500'],
                ],
            };

            foreach ($plan as [$drugKey, $route, $every, $first, $dose]) {
                for ($hour = $first; $hour < 24; $hour += $every) {
                    KardexSchedule::create([
                        'hospitalization_id' => $stay->id,
                        'drug_id' => $drugs[$drugKey]->id,
                        'prescribed_by' => $vet->id,
                        'dose_amount' => $dose,
                        'weight_kg_at_prescription' => $weight,
                        'route' => $route,
                        'scheduled_at' => $today->copy()->setHour($hour)->utc(),
                    ]);
                }
            }
        }
    }
}
