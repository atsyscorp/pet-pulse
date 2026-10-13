<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Models\Patient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.clinical')]
#[Title('Pacientes')]
final class PatientIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'solo')]
    public string $filter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Patient::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, Patient> */
    #[Computed]
    public function patients(): LengthAwarePaginator
    {
        $term = trim($this->search);

        return Patient::query()
            ->with(['activeHospitalization:id,patient_id,bed_label,triage_level,status,admitted_at'])
            ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereLike('name', "%{$term}%")
                ->orWhereLike('owner_name', "%{$term}%")
                ->orWhereLike('owner_document', "{$term}%")
                ->orWhereLike('owner_phone', "%{$term}%")
                ->orWhereLike('microchip', "{$term}%")))
            ->when($this->filter === 'hospitalizados', fn (Builder $q) => $q->whereHas('activeHospitalization'))
            ->orderBy('name')
            ->paginate(20);
    }

    public function render(): View
    {
        return view('livewire.hospital.patient-index');
    }
}
