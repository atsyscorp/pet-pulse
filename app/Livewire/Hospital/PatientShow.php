<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Enums\HospitalizationStatus;
use App\Models\Hospitalization;
use App\Models\Patient;
use App\Services\Hospitalization\DischargePatientService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.clinical')]
#[Title('Ficha del paciente')]
final class PatientShow extends Component
{
    public Patient $patient;

    // Discharge modal state
    public bool $showDischarge = false;

    public string $outcome = HospitalizationStatus::Discharged->value;

    public string $dischargeNotes = '';

    /** In-page confirmation after an action that does not navigate away. */
    public ?string $notice = null;

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);
        $this->patient = $patient;
    }

    #[Computed]
    public function activeStay(): ?Hospitalization
    {
        return $this->patient->hospitalizations()
            ->with('attendingVet:id,name')
            ->where('status', HospitalizationStatus::Admitted)
            ->latest('admitted_at')
            ->first();
    }

    /** @return Collection<int, Hospitalization> */
    #[Computed]
    public function history(): Collection
    {
        return $this->patient->hospitalizations()
            ->with('attendingVet:id,name')
            ->where('status', '!=', HospitalizationStatus::Admitted)
            ->latest('admitted_at')
            ->limit(20)
            ->get();
    }

    public function openDischarge(): void
    {
        $stay = $this->activeStay;
        abort_if($stay === null, 404);
        $this->authorize('discharge', $stay);

        $this->reset('outcome', 'dischargeNotes');
        $this->showDischarge = true;
    }

    public function discharge(DischargePatientService $service): void
    {
        $stay = $this->activeStay;
        abort_if($stay === null, 404);
        $this->authorize('discharge', $stay);

        $this->validate([
            'outcome' => ['required', Rule::in(array_map(fn ($o) => $o->value, HospitalizationStatus::dischargeOptions()))],
            'dischargeNotes' => ['nullable', 'string', 'max:2000'],
        ], [], ['outcome' => 'resultado', 'dischargeNotes' => 'observaciones']);

        try {
            $service->discharge($stay, HospitalizationStatus::from($this->outcome), trim($this->dischargeNotes) ?: null);
        } catch (Throwable $e) {
            report($e);
            $this->addError('outcome', 'No se pudo cerrar la hospitalización. Recargue la ficha e intente de nuevo.');

            return;
        }

        $this->showDischarge = false;
        unset($this->activeStay, $this->history);
        $this->notice = 'Hospitalización cerrada: '.HospitalizationStatus::from($this->outcome)->label().'. Las dosis pendientes fueron canceladas.';
        $this->dispatch('clinical-notice');
    }

    public function render(): View
    {
        return view('livewire.hospital.patient-show', [
            'dischargeOptions' => HospitalizationStatus::dischargeOptions(),
            'timezone' => auth()->user()?->clinic?->timezone ?? config('app.timezone'),
        ]);
    }
}
