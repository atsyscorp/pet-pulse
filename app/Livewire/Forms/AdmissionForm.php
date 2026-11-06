<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\TriageLevel;
use App\Enums\UserRole;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class AdmissionForm extends Form
{
    public string $current_weight_kg = '';

    public string $triage_level = TriageLevel::Observation->value;

    public string $bed_label = '';

    public string $attending_vet_id = '';

    public string $admission_reason = '';

    public string $diagnosis = '';

    /** datetime-local value in the clinic's timezone. */
    public string $admitted_at = '';

    /** Clinic timezone, set by the component; needed to judge "not in the future". */
    public string $timezone = 'America/Bogota';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clinicId = app(TenantContext::class)->idOrFail();

        return [
            'current_weight_kg' => ['required', 'regex:/^\d{1,3}([.,]\d{1,3})?$/', function (string $attribute, mixed $value, \Closure $fail): void {
                $kg = (float) str_replace(',', '.', (string) $value);
                if ($kg < 0.05 || $kg > 999) {
                    $fail('El peso debe estar entre 0,050 y 999 kg.');
                }
            }],
            'triage_level' => ['required', Rule::enum(TriageLevel::class)],
            'bed_label' => ['nullable', 'string', 'max:32'],
            'attending_vet_id' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where('clinic_id', $clinicId)
                    ->whereIn('role', [UserRole::Veterinarian->value, UserRole::Admin->value]),
            ],
            'admission_reason' => ['required', 'string', 'min:5', 'max:2000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'admitted_at' => ['required', 'date', function (string $attribute, mixed $value, \Closure $fail): void {
                if (Carbon::parse((string) $value, $this->timezone)->isAfter(Carbon::now($this->timezone)->addMinutes(5))) {
                    $fail('La fecha de ingreso no puede ser futura.');
                }
            }],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'current_weight_kg' => 'peso', 'triage_level' => 'nivel de triage', 'bed_label' => 'cama',
            'attending_vet_id' => 'veterinario tratante', 'admission_reason' => 'motivo de ingreso',
            'diagnosis' => 'diagnóstico presuntivo', 'admitted_at' => 'fecha y hora de ingreso',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'current_weight_kg.regex' => 'Escriba el peso en kilogramos, con hasta tres decimales (p. ej. 4,250).',
            'attending_vet_id.exists' => 'Seleccione un veterinario de la clínica.',
        ];
    }

    /** Vets of the active clinic, for the select. @return array<int, string> */
    public static function vetOptions(): array
    {
        return User::query()
            ->whereIn('role', [UserRole::Veterinarian, UserRole::Admin])
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Normalised payload for AdmitPatientService.
     *
     * @return array{current_weight_kg: string, triage_level: TriageLevel, bed_label: ?string, attending_vet_id: ?int, admission_reason: string, diagnosis: ?string, admitted_at: Carbon}
     */
    public function payload(): array
    {
        $data = $this->validate();

        return [
            'current_weight_kg' => number_format((float) str_replace(',', '.', $data['current_weight_kg']), 3, '.', ''),
            'triage_level' => TriageLevel::from($data['triage_level']),
            'bed_label' => trim($data['bed_label']) !== '' ? trim($data['bed_label']) : null,
            'attending_vet_id' => $data['attending_vet_id'] !== '' ? (int) $data['attending_vet_id'] : null,
            'admission_reason' => trim($data['admission_reason']),
            'diagnosis' => trim($data['diagnosis']) !== '' ? trim($data['diagnosis']) : null,
            'admitted_at' => Carbon::parse($data['admitted_at'], $this->timezone)->utc(),
        ];
    }
}
