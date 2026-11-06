<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Livewire\Forms\DrugForm;
use App\Models\Drug;
use App\Services\Pharmacy\ReceiveDrugStockService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Create (no route param) or edit (with {drug}) a catalog entry. */
#[Layout('layouts.clinical')]
#[Title('Medicamento')]
final class DrugEditor extends Component
{
    public DrugForm $form;

    public function mount(?Drug $drug = null): void
    {
        if ($drug?->exists) {
            $this->authorize('update', $drug);
            $this->form->setDrug($drug);
        } else {
            $this->authorize('create', Drug::class);
        }
    }

    public function save(ReceiveDrugStockService $receive): void
    {
        $attributes = $this->form->attributes();

        if ($this->form->drug === null) {
            $initial = (int) $this->form->initial_packages;

            // Create the drug at zero and book the opening stock through the
            // ledger, so "the ledger is the truth" holds from the first unit.
            $drug = DB::transaction(function () use ($attributes, $initial, $receive): Drug {
                $drug = Drug::query()->create([...$attributes, 'stock_packages' => 0, 'open_fraction_balance' => '0']);

                if ($initial > 0) {
                    $receive->receive($drug, $initial, (int) auth()->id(), 'Existencias iniciales al crear el medicamento.');
                }

                return $drug;
            });

            session()->flash('status', "Medicamento {$drug->name} creado.");
        } else {
            $drug = $this->form->drug;
            $drug->update($attributes);
            session()->flash('status', 'Medicamento actualizado.');
        }

        $this->redirectRoute('hospital.drugs.show', $drug, navigate: false);
    }

    public function render(): View
    {
        return view('livewire.hospital.drug-editor', [
            'isNew' => $this->form->drug === null,
            'unitsLocked' => $this->form->unitsLocked(),
            'concentrationUnits' => DrugForm::CONCENTRATION_UNITS,
            'presentationUnits' => DrugForm::PRESENTATION_UNITS,
            'fractionUnits' => DrugForm::FRACTION_UNITS,
        ]);
    }
}
