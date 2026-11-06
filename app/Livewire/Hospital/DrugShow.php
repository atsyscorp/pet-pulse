<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Models\Drug;
use App\Models\DrugFractionLog;
use App\Models\KardexSchedule;
use App\Services\Pharmacy\ReceiveDrugStockService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.clinical')]
#[Title('Medicamento')]
final class DrugShow extends Component
{
    use WithPagination;

    public Drug $drug;

    public bool $showReceive = false;

    public string $packages = '';

    public string $receiveNotes = '';

    public ?string $notice = null;

    public function mount(Drug $drug): void
    {
        $this->authorize('view', $drug);
        $this->drug = $drug;
    }

    /** @return LengthAwarePaginator<int, DrugFractionLog> */
    #[Computed]
    public function movements(): LengthAwarePaginator
    {
        return $this->drug->movements()
            ->with([
                'user:id,name',
                // Dose movements point at a kárdex row; show whose dose it was.
                'source' => fn (MorphTo $morph) => $morph->morphWith([
                    KardexSchedule::class => ['hospitalization:id,patient_id,bed_label', 'hospitalization.patient:id,name'],
                ]),
            ])
            ->latest('id')
            ->paginate(25);
    }

    public function openReceive(): void
    {
        $this->authorize('receiveStock', $this->drug);
        $this->reset('packages', 'receiveNotes');
        $this->resetValidation();
        $this->showReceive = true;
    }

    public function receive(ReceiveDrugStockService $service): void
    {
        $this->authorize('receiveStock', $this->drug);

        $this->validate([
            'packages' => ['required', 'integer', 'min:1', 'max:10000'],
            'receiveNotes' => ['nullable', 'string', 'max:500'],
        ], [], ['packages' => 'cantidad de empaques', 'receiveNotes' => 'observaciones']);

        $service->receive($this->drug, (int) $this->packages, (int) auth()->id(), trim($this->receiveNotes) ?: null);

        $this->showReceive = false;
        $this->notice = sprintf('Entrada registrada: %d %s. Existencias selladas: %d.', (int) $this->packages, $this->drug->presentation_unit, $this->drug->stock_packages);
        $this->dispatch('clinical-notice');
        unset($this->movements);
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.hospital.drug-show', [
            'timezone' => auth()->user()?->clinic?->timezone ?? config('app.timezone'),
        ]);
    }
}
