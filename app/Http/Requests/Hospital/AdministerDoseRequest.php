<?php

declare(strict_types=1);

namespace App\Http\Requests\Hospital;

use App\Models\KardexSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HTTP entry point for barcode scanners / mobile clients that register a dose
 * without the Livewire board.
 */
final class AdministerDoseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administer', $this->schedule()) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'notes' => [
                Rule::requiredIf(fn (): bool => (bool) $this->schedule()->drug?->is_controlled),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'notes.required' => 'Los medicamentos de control especial requieren una observación.',
        ];
    }

    public function schedule(): KardexSchedule
    {
        /** @var KardexSchedule $schedule */
        $schedule = $this->route('schedule');

        return $schedule;
    }
}
