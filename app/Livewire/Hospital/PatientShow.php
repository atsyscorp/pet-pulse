<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Enums\HospitalizationStatus;
use App\Enums\KardexStatus;
use App\Enums\PrescriptionStatus;
use App\Exceptions\Prescription\PrescriptionNotAllowedException;
use App\Models\Hospitalization;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\Hospitalization\DischargePatientService;
use App\Services\Hospitalization\SuspendPrescriptionService;
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

    // Suspend-prescription modal state
    public ?int $suspendingId = null;

    public string $suspensionReason = '';

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

    /**
     * Orders of the current stay: active first, then the rest, newest first.
     *
     * @return Collection<int, Prescription>
     */
    #[Computed]
    public function prescriptions(): Collection
    {
        $stay = $this->activeStay;

        if ($stay === null) {
            return new Collection;
        }

        return $stay->prescriptions()
            ->with(['drug:id,name,fraction_unit,concentration_unit,is_controlled', 'prescriber:id,name'])
            ->withCount([
                'schedules as administered_count' => fn ($q) => $q->where('status', KardexStatus::Administered),
            ])
            ->orderByRaw('case when status = ? then 0 else 1 end', [PrescriptionStatus::Active->value])
            ->latest('starts_at')
            ->get();
    }

    public function openSuspend(int $prescriptionId): void
    {
        $prescription = $this->findStayPrescription($prescriptionId);
        $this->authorize('suspend', $prescription);

        $this->suspendingId = $prescription->getKey();
        $this->suspensionReason = '';
        $this->resetValidation();
    }

    public function suspend(SuspendPrescriptionService $service): void
    {
        $prescription = $this->findStayPrescription((int) $this->suspendingId);
        $this->authorize('suspend', $prescription);

        $this->validate(
            ['suspensionReason' => ['required', 'string', 'min:3', 'max:500']],
            [],
            ['suspensionReason' => 'motivo de la suspensión'],
        );

        try {
            $cancelled = $service->suspend($prescription, (int) auth()->id(), trim($this->suspensionReason));
        } catch (PrescriptionNotAllowedException $e) {
            $this->addError('suspensionReason', $e->getMessage());

            return;
        }

        $this->suspendingId = null;
        unset($this->prescriptions);
        $this->notice = sprintf('Prescripción de %s suspendida. %d dosis pendientes canceladas.', $prescription->drug->name, $cancelled);
        $this->dispatch('clinical-notice');
    }

    private function findStayPrescription(int $id): Prescription
    {
        $stay = $this->activeStay;
        abort_if($stay === null, 404);

        return $stay->prescriptions()->with('drug:id,name')->findOrFail($id);
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
        unset($this->activeStay, $this->history, $this->prescriptions);
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
