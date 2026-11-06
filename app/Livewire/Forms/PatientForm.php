<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\PatientSex;
use App\Models\Patient;
use App\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class PatientForm extends Form
{
    public ?Patient $patient = null;

    public string $name = '';

    public string $species = '';

    public string $breed = '';

    public string $sex = '';

    public string $birth_date = '';

    public string $microchip = '';

    public string $owner_name = '';

    public string $owner_document = '';

    public string $owner_phone = '';

    /** Common species in LATAM small-animal practice; the field also accepts free text. */
    public const array SPECIES = ['Canino', 'Felino', 'Conejo', 'Cobayo', 'Hámster', 'Hurón', 'Ave', 'Reptil', 'Equino', 'Bovino', 'Porcino', 'Caprino', 'Ovino'];

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $clinicId = app(TenantContext::class)->idOrFail();

        return [
            'name' => ['required', 'string', 'max:100'],
            'species' => ['required', 'string', 'max:32'],
            'breed' => ['nullable', 'string', 'max:100'],
            'sex' => ['nullable', Rule::enum(PatientSex::class)],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today', 'after:1990-01-01'],
            'microchip' => [
                'nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('patients', 'microchip')
                    ->where('clinic_id', $clinicId)
                    ->whereNull('deleted_at')
                    ->ignore($this->patient?->getKey()),
            ],
            'owner_name' => ['required', 'string', 'max:120'],
            'owner_document' => ['nullable', 'string', 'max:32'],
            'owner_phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+() \-]+$/'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre', 'species' => 'especie', 'breed' => 'raza', 'sex' => 'sexo',
            'birth_date' => 'fecha de nacimiento', 'microchip' => 'microchip',
            'owner_name' => 'nombre del propietario', 'owner_document' => 'documento del propietario',
            'owner_phone' => 'teléfono del propietario',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'microchip.unique' => 'Ya existe un paciente con este microchip en la clínica.',
            'microchip.regex' => 'El microchip solo admite letras, números y guiones.',
            'owner_phone.regex' => 'El teléfono solo admite dígitos, espacios, paréntesis y el signo +.',
            'birth_date.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        ];
    }

    public function setPatient(Patient $patient): void
    {
        $this->patient = $patient;
        $this->fill([
            'name' => $patient->name,
            'species' => $patient->species,
            'breed' => $patient->breed ?? '',
            'sex' => $patient->sex?->value ?? '',
            'birth_date' => $patient->birth_date?->toDateString() ?? '',
            'microchip' => $patient->microchip ?? '',
            'owner_name' => $patient->owner_name,
            'owner_document' => $patient->owner_document ?? '',
            'owner_phone' => $patient->owner_phone ?? '',
        ]);
    }

    public function save(): Patient
    {
        $data = $this->validate();

        $attributes = [
            'name' => trim($data['name']),
            'species' => trim($data['species']),
            'breed' => $this->nullable($data['breed']),
            'sex' => $this->nullable($data['sex']),
            'birth_date' => $this->nullable($data['birth_date']),
            'microchip' => $this->nullable($data['microchip']),
            'owner_name' => trim($data['owner_name']),
            'owner_document' => $this->nullable($data['owner_document']),
            'owner_phone' => $this->nullable($data['owner_phone']),
        ];

        if ($this->patient === null) {
            $this->patient = Patient::query()->create($attributes);
        } else {
            $this->patient->update($attributes);
        }

        return $this->patient;
    }

    private function nullable(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
