<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Enums\AdministrationRoute;
use App\Enums\DoseMode;
use App\Exceptions\Prescription\PrescriptionNotAllowedException;
use App\Models\Drug;
use App\Models\Hospitalization;
use App\Services\Hospitalization\PrescribeMedicationService;
use App\Services\Hospitalization\PrescriptionPlan;
use App\Support\DecimalInput;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.clinical')]
#[Title('Nueva prescripción')]
final class PrescriptionEditor extends Component
{
    #[Locked]
    public int $hospitalizationId;

    #[Locked]
    public string $timezone = 'America/Bogota';

    public string $drug_id = '';

    public string $dose_mode = DoseMode::PerKg->value;

    public string $dose_per_kg = '';

    public string $dose_amount = '';

    public string $route = '';

    /** '0' = single dose. */
    public string $frequency_hours = '12';

    public string $duration_days = '3';

    /** datetime-local in the clinic's timezone. */
    public string $starts_at = '';

    public string $instructions = '';

    /** Allowed intervals, in hours. 0 = single dose. */
    public const array FREQUENCIES = [0, 1, 2, 3, 4, 6, 8, 12, 24, 48, 72];

    public function mount(Hospitalization $hospitalization): void
    {
        $this->authorize('prescribe', $hospitalization);

        $this->hospitalizationId = $hospitalization->getKey();
        $this->timezone = auth()->user()?->clinic?->timezone ?? $this->timezone;
        // Default first dose: the next full hour.
        $this->starts_at = Carbon::now($this->timezone)->addHour()->startOfHour()->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function stay(): Hospitalization
    {
        return Hospitalization::query()->with('patient')->findOrFail($this->hospitalizationId);
    }

    /** @return Collection<int, Drug> */
    #[Computed]
    public function drugs(): Collection
    {
        return Drug::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function drug(): ?Drug
    {
        return $this->drug_id !== '' ? $this->drugs->firstWhere('id', (int) $this->drug_id) : null;
    }

    public function updatedDrugId(): void
    {
        $drug = $this->drug;

        // A drug without a registered concentration cannot be dosed per kg.
        if ($drug !== null && $drug->concentration_amount === null) {
            $this->dose_mode = DoseMode::Fixed->value;
        }
    }

    /**
     * Live preview. Returns null while the inputs are incomplete or invalid,
     * so the panel simply shows a hint instead of an error.
     */
    #[Computed]
    public function plan(): ?PrescriptionPlan
    {
        $drug = $this->drug;
        $mode = DoseMode::tryFrom($this->dose_mode);
        $route = AdministrationRoute::tryFrom($this->route) ?? AdministrationRoute::Intravenous;
        $frequency = (int) $this->frequency_hours;

        if ($drug === null || $mode === null || ! in_array($frequency, self::FREQUENCIES, true)) {
            return null;
        }

        $amountInput = $mode === DoseMode::PerKg ? $this->dose_per_kg : $this->dose_amount;
        if (! preg_match(DecimalInput::PATTERN, trim($amountInput))) {
            return null;
        }

        $days = (int) $this->duration_days;
        if ($frequency !== 0 && ($days < 1 || $days > 30)) {
            return null;
        }

        try {
            return PrescriptionPlan::make(
                drug: $drug,
                mode: $mode,
                dosePerKg: $mode === DoseMode::PerKg ? DecimalInput::normalize($this->dose_per_kg) : null,
                fixedAmount: $mode === DoseMode::Fixed ? DecimalInput::normalize($this->dose_amount) : null,
                weightKg: (string) $this->stay->current_weight_kg,
                route: $route,
                frequencyHours: $frequency === 0 ? null : $frequency,
                startsAt: Carbon::parse($this->starts_at, $this->timezone),
                durationHours: $frequency === 0 ? null : $days * 24,
            );
        } catch (InvalidArgumentException|\Throwable) {
            return null;
        }
    }

    public function save(PrescribeMedicationService $service): void
    {
        $stay = $this->stay;
        $this->authorize('prescribe', $stay);

        $clinicId = (int) $stay->clinic_id;
        $this->validate([
            'drug_id' => ['required', Rule::exists('drugs', 'id')->where('clinic_id', $clinicId)->where('is_active', true)->whereNull('deleted_at')],
            'dose_mode' => ['required', Rule::enum(DoseMode::class)],
            'dose_per_kg' => ['exclude_unless:dose_mode,per_kg', 'required', 'regex:'.DecimalInput::PATTERN],
            'dose_amount' => ['exclude_unless:dose_mode,fixed', 'required', 'regex:'.DecimalInput::PATTERN],
            'route' => ['required', Rule::enum(AdministrationRoute::class)],
            'frequency_hours' => ['required', Rule::in(array_map('strval', self::FREQUENCIES))],
            'duration_days' => ['exclude_if:frequency_hours,0', 'required', 'integer', 'min:1', 'max:30'],
            'starts_at' => ['required', 'date', function (string $a, mixed $v, \Closure $fail): void {
                $start = Carbon::parse((string) $v, $this->timezone);
                $now = Carbon::now($this->timezone);
                if ($start->lessThan($now->copy()->subHours(12))) {
                    $fail('La primera dosis no puede ser de hace más de 12 horas.');
                } elseif ($start->greaterThan($now->copy()->addDays(7))) {
                    $fail('La primera dosis debe estar dentro de los próximos 7 días.');
                }
            }],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ], [
            'dose_per_kg.regex' => 'Escriba la dosis con hasta 4 decimales (p. ej. 0,2).',
            'dose_amount.regex' => 'Escriba la cantidad con hasta 4 decimales (p. ej. 0,5).',
        ], [
            'drug_id' => 'medicamento', 'dose_per_kg' => 'dosis por kg', 'dose_amount' => 'cantidad por dosis',
            'route' => 'vía', 'frequency_hours' => 'frecuencia', 'duration_days' => 'duración',
            'starts_at' => 'primera dosis', 'instructions' => 'indicaciones',
        ]);

        $drug = $this->drug;
        if ($this->dose_mode === DoseMode::PerKg->value && $drug?->concentration_amount === null) {
            $this->addError('dose_mode', 'Este medicamento no tiene concentración registrada; use dosis fija o complete la concentración en el catálogo.');

            return;
        }

        $plan = $this->plan;
        if ($plan === null) {
            $this->addError($this->dose_mode === DoseMode::PerKg->value ? 'dose_per_kg' : 'dose_amount', 'La dosis calculada es menor que 0,0001; revise la dosis.');

            return;
        }

        try {
            $service->prescribe($stay, $plan, (int) auth()->id(), trim($this->instructions) ?: null);
        } catch (PrescriptionNotAllowedException $e) {
            $this->addError('drug_id', $e->getMessage());

            return;
        }

        session()->flash('status', sprintf('Prescripción registrada: %s, %d dosis programadas en el kárdex.', $drug->name, $plan->doseCount()));
        $this->redirectRoute('hospital.patients.show', $stay->patient_id, navigate: false);
    }

    public function render(): View
    {
        return view('livewire.hospital.prescription-editor', [
            'routes' => AdministrationRoute::cases(),
            'frequencies' => self::FREQUENCIES,
        ]);
    }
}
