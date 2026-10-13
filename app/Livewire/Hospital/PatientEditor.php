<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Livewire\Forms\PatientForm;
use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Create (no route param) or edit (with {patient}) a patient. */
#[Layout('layouts.clinical')]
#[Title('Paciente')]
final class PatientEditor extends Component
{
    public PatientForm $form;

    /** Where to go after saving: "ingreso" opens the admission form right away. */
    public string $next = '';

    public function mount(?Patient $patient = null): void
    {
        if ($patient?->exists) {
            $this->authorize('update', $patient);
            $this->form->setPatient($patient);
        } else {
            $this->authorize('create', Patient::class);
        }

        $this->next = request()->query('siguiente', '') === 'ingreso' ? 'ingreso' : '';
    }

    public function save(): void
    {
        $isNew = $this->form->patient === null;
        $patient = $this->form->save();

        session()->flash('status', $isNew ? "Paciente {$patient->name} registrado." : 'Datos del paciente actualizados.');

        if ($this->next === 'ingreso' && $isNew) {
            $this->redirectRoute('hospital.patients.admit', $patient, navigate: false);

            return;
        }

        $this->redirectRoute('hospital.patients.show', $patient, navigate: false);
    }

    public function render(): View
    {
        return view('livewire.hospital.patient-editor', [
            'isNew' => $this->form->patient === null,
            'speciesOptions' => PatientForm::SPECIES,
        ]);
    }
}
