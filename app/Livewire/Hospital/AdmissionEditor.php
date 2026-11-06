<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Enums\TriageLevel;
use App\Exceptions\Hospitalization\PatientAlreadyAdmittedException;
use App\Livewire\Forms\AdmissionForm;
use App\Models\Hospitalization;
use App\Models\Patient;
use App\Services\Hospitalization\AdmitPatientService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.clinical')]
#[Title('Nuevo ingreso')]
final class AdmissionEditor extends Component
{
    public Patient $patient;

    public AdmissionForm $form;

    public string $timezone = 'America/Bogota';

    public function mount(Patient $patient): void
    {
        $this->authorize('create', Hospitalization::class);

        $this->patient = $patient;
        $this->timezone = auth()->user()?->clinic?->timezone ?? $this->timezone;
        $this->form->timezone = $this->timezone;
        $this->form->admitted_at = Carbon::now($this->timezone)->format('Y-m-d\TH:i');

        // Pre-select the current user when they are a vet.
        if (auth()->user()?->role->value === 'veterinarian') {
            $this->form->attending_vet_id = (string) auth()->id();
        }
    }

    public function save(AdmitPatientService $service): void
    {
        $payload = $this->form->payload();

        try {
            $service->admit($this->patient, $payload);
        } catch (PatientAlreadyAdmittedException $e) {
            $this->addError('form.admission_reason', $e->getMessage());

            return;
        }

        session()->flash('status', "{$this->patient->name} ingresó a hospitalización. Las dosis se programan desde el kárdex.");

        $this->redirectRoute('hospital.patients.show', $this->patient, navigate: false);
    }

    public function render(): View
    {
        return view('livewire.hospital.admission-editor', [
            'triageLevels' => TriageLevel::cases(),
            'vets' => AdmissionForm::vetOptions(),
            'alreadyAdmitted' => $this->patient->activeHospitalization()->exists(),
        ]);
    }
}
