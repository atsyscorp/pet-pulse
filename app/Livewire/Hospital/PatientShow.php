<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Enums\HospitalizationStatus;
use App\Enums\KardexStatus;
use App\Enums\OwnerVisibleStatus;
use App\Enums\PrescriptionStatus;
use App\Exceptions\Prescription\PrescriptionNotAllowedException;
use App\Models\Hospitalization;
use App\Models\OwnerPatientLink;
use App\Models\OwnerUpdate;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\Hospitalization\DischargePatientService;
use App\Services\Hospitalization\SuspendPrescriptionService;
use App\Services\OwnerApp\LinkCode;
use App\Services\OwnerApp\LinkCodeQr;
use App\Services\OwnerApp\OwnerAccessService;
use App\Services\OwnerApp\OwnerCommunicationService;
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

    // Owner app: the code is shown once (only its hash is stored).
    public ?string $issuedCode = null;

    public ?string $issuedCodeExpiresAt = null;

    public string $ownerStatus = '';

    public string $updateBody = '';

    /** In-page confirmation after an action that does not navigate away. */
    public ?string $notice = null;

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);
        $this->patient = $patient;
        $this->ownerStatus = $this->activeStay?->owner_status?->value ?? '';
    }

    // ───────────── Owner app ─────────────

    /** @return Collection<int, OwnerPatientLink> */
    #[Computed]
    public function ownerLinks(): Collection
    {
        return $this->patient->ownerLinks()
            ->whereNull('revoked_at')
            ->with('owner:id,name,email,phone')
            ->latest('linked_at')
            ->get();
    }

    /** @return Collection<int, OwnerUpdate> */
    #[Computed]
    public function ownerUpdates(): Collection
    {
        $stay = $this->activeStay;

        return $stay === null ? new Collection : $stay->ownerUpdates()->with('author:id,name')->latest('id')->limit(10)->get();
    }

    public function issueLinkCode(OwnerAccessService $access): void
    {
        $this->authorize('manageOwnerAccess', $this->patient);

        $issued = $access->issueCode($this->patient, (int) auth()->id());
        $this->issuedCode = $issued['code'];
        $this->issuedCodeExpiresAt = $issued['expires_at']->toIso8601String();
    }

    public function revokeOwnerLink(int $linkId, OwnerAccessService $access): void
    {
        $this->authorize('manageOwnerAccess', $this->patient);

        $link = $this->patient->ownerLinks()->whereNull('revoked_at')->with('owner:id,name')->findOrFail($linkId);
        $access->revoke($link, (int) auth()->id());

        unset($this->ownerLinks);
        $this->notice = "Se retiró el acceso de {$link->owner->name} a la app.";
        $this->dispatch('clinical-notice');
    }

    public function saveOwnerStatus(OwnerCommunicationService $comms): void
    {
        $stay = $this->activeStay;
        abort_if($stay === null, 404);
        $this->authorize('informOwner', $stay);

        $this->validate(['ownerStatus' => ['nullable', Rule::enum(OwnerVisibleStatus::class)]], [], ['ownerStatus' => 'estado para el propietario']);

        $comms->setStatus($stay, OwnerVisibleStatus::tryFrom($this->ownerStatus));
        unset($this->activeStay);
        $this->notice = $this->ownerStatus === ''
            ? 'El propietario verá solo "Hospitalizado", sin estado.'
            : 'El propietario ahora ve: '.OwnerVisibleStatus::from($this->ownerStatus)->label().'.';
        $this->dispatch('clinical-notice');
    }

    public function publishOwnerUpdate(OwnerCommunicationService $comms): void
    {
        $stay = $this->activeStay;
        abort_if($stay === null, 404);
        $this->authorize('informOwner', $stay);

        $this->validate(['updateBody' => ['required', 'string', 'min:5', 'max:2000']], [], ['updateBody' => 'parte médico']);

        $comms->publishUpdate($stay, (int) auth()->id(), $this->updateBody);
        $this->updateBody = '';
        unset($this->ownerUpdates);
        $this->notice = 'Parte médico publicado en la app del propietario.';
        $this->dispatch('clinical-notice');
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
            'issuedCodeDisplay' => $this->issuedCode !== null ? LinkCode::display($this->issuedCode) : null,
            'issuedCodeQr' => $this->issuedCode !== null ? LinkCodeQr::svg($this->issuedCode) : null,
            'ownerStatuses' => OwnerVisibleStatus::cases(),
            'dischargeOptions' => HospitalizationStatus::dischargeOptions(),
            'timezone' => auth()->user()?->clinic?->timezone ?? config('app.timezone'),
        ]);
    }
}
