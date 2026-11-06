<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\Drug;
use App\Support\DecimalInput;
use Livewire\Form;

final class DrugForm extends Form
{
    public ?Drug $drug = null;

    public string $name = '';

    public string $active_ingredient = '';

    public string $sanitary_registry = '';

    public string $concentration_amount = '';

    public string $concentration_unit = 'mg';

    public string $presentation_unit = '';

    public string $volume_per_presentation = '';

    public string $fraction_unit = 'mL';

    public string $reorder_level_packages = '0';

    public string $stability_hours_after_opening = '';

    public bool $is_controlled = false;

    public bool $is_active = true;

    /** Create only: sealed packages on hand today, booked as a ledger entry. */
    public string $initial_packages = '0';

    public const array CONCENTRATION_UNITS = ['mg', 'mcg', 'g', 'UI', 'mEq'];

    public const array PRESENTATION_UNITS = ['vial', 'ampolla', 'frasco', 'blíster', 'caja', 'bolsa', 'tubo', 'sobre', 'jeringa prellenada'];

    public const array FRACTION_UNITS = ['mL', 'tab', 'cáps', 'g', 'mg', 'UI', 'gota', 'sobre', 'aplicación'];

    /**
     * Once a drug has ledger movements, the unit system it was counted in is
     * frozen: changing the volume per vial or the fraction unit would silently
     * reinterpret every past movement and the open balance.
     */
    public function unitsLocked(): bool
    {
        return $this->drug !== null && $this->drug->movements()->exists();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'active_ingredient' => ['nullable', 'string', 'max:150'],
            'sanitary_registry' => ['nullable', 'string', 'max:64'],
            'concentration_amount' => ['nullable', 'regex:'.DecimalInput::PATTERN, function (string $a, mixed $v, \Closure $fail): void {
                if ($v !== null && $v !== '' && (float) DecimalInput::normalize((string) $v) <= 0) {
                    $fail('La concentración debe ser mayor que cero.');
                }
            }],
            'concentration_unit' => ['required_with:concentration_amount', 'nullable', 'string', 'max:16'],
            'presentation_unit' => ['required', 'string', 'max:32'],
            'volume_per_presentation' => ['required', 'regex:'.DecimalInput::PATTERN, function (string $a, mixed $v, \Closure $fail): void {
                if ((float) DecimalInput::normalize((string) $v) <= 0) {
                    $fail('El contenido por empaque debe ser mayor que cero.');
                }
            }],
            'fraction_unit' => ['required', 'string', 'max:16'],
            'reorder_level_packages' => ['required', 'integer', 'min:0', 'max:100000'],
            'stability_hours_after_opening' => ['nullable', 'integer', 'min:1', 'max:8760'],
            'is_controlled' => ['boolean'],
            'is_active' => ['boolean'],
            'initial_packages' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre', 'active_ingredient' => 'principio activo', 'sanitary_registry' => 'registro sanitario',
            'concentration_amount' => 'concentración', 'concentration_unit' => 'unidad de concentración',
            'presentation_unit' => 'presentación', 'volume_per_presentation' => 'contenido por empaque',
            'fraction_unit' => 'unidad de dosificación', 'reorder_level_packages' => 'nivel de reposición',
            'stability_hours_after_opening' => 'estabilidad tras abrir', 'initial_packages' => 'existencias iniciales',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'concentration_amount.regex' => 'Escriba la concentración con hasta 4 decimales (p. ej. 50 o 0,3).',
            'volume_per_presentation.regex' => 'Escriba el contenido con hasta 4 decimales (p. ej. 10 o 2,5).',
        ];
    }

    public function setDrug(Drug $drug): void
    {
        $this->drug = $drug;
        $this->fill([
            'name' => $drug->name,
            'active_ingredient' => $drug->active_ingredient ?? '',
            'sanitary_registry' => $drug->sanitary_registry ?? '',
            'concentration_amount' => $drug->concentration_amount !== null ? self::trim4((string) $drug->concentration_amount) : '',
            'concentration_unit' => $drug->concentration_unit ?? 'mg',
            'presentation_unit' => $drug->presentation_unit,
            'volume_per_presentation' => self::trim4((string) $drug->volume_per_presentation),
            'fraction_unit' => $drug->fraction_unit,
            'reorder_level_packages' => (string) $drug->reorder_level_packages,
            'stability_hours_after_opening' => $drug->stability_hours_after_opening !== null ? (string) $drug->stability_hours_after_opening : '',
            'is_controlled' => $drug->is_controlled,
            'is_active' => $drug->is_active,
        ]);
    }

    /** @return array<string, mixed> Attributes ready for Drug::create()/update(). */
    public function attributes(): array
    {
        $data = $this->validate();
        $concentration = DecimalInput::normalize($data['concentration_amount']);

        $attributes = [
            'name' => trim($data['name']),
            'active_ingredient' => self::nullable($data['active_ingredient']),
            'sanitary_registry' => self::nullable($data['sanitary_registry']),
            'concentration_amount' => $concentration,
            'concentration_unit' => $concentration !== null ? trim((string) $data['concentration_unit']) : null,
            'presentation_unit' => trim($data['presentation_unit']),
            'volume_per_presentation' => DecimalInput::normalize($data['volume_per_presentation']),
            'fraction_unit' => trim($data['fraction_unit']),
            'reorder_level_packages' => (int) $data['reorder_level_packages'],
            'stability_hours_after_opening' => $data['stability_hours_after_opening'] !== '' && $data['stability_hours_after_opening'] !== null ? (int) $data['stability_hours_after_opening'] : null,
            'is_controlled' => (bool) $data['is_controlled'],
            'is_active' => (bool) $data['is_active'],
        ];

        if ($this->unitsLocked()) {
            unset($attributes['volume_per_presentation'], $attributes['fraction_unit']);
        }

        return $attributes;
    }

    private static function nullable(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    private static function trim4(string $value): string
    {
        return str_replace('.', ',', rtrim(rtrim($value, '0'), '.'));
    }
}
