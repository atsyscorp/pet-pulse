<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Models\Drug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.clinical')]
#[Title('Medicamentos')]
final class DrugIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    /** '' | 'stock-bajo' | 'control-especial' | 'inactivos' */
    #[Url(as: 'filtro')]
    public string $filter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Drug::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, Drug> */
    #[Computed]
    public function drugs(): LengthAwarePaginator
    {
        $term = trim($this->search);

        return Drug::query()
            ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereLike('name', "%{$term}%")
                ->orWhereLike('active_ingredient', "%{$term}%")
                ->orWhereLike('sanitary_registry', "{$term}%")))
            ->when($this->filter === 'stock-bajo', fn (Builder $q) => $q->where('is_active', true)->whereColumn('stock_packages', '<=', 'reorder_level_packages'))
            ->when($this->filter === 'control-especial', fn (Builder $q) => $q->where('is_controlled', true))
            ->when($this->filter === 'inactivos', fn (Builder $q) => $q->where('is_active', false), fn (Builder $q) => $q->where('is_active', true))
            ->orderBy(DB::raw('lower(name)'))
            ->paginate(25);
    }

    public function render(): View
    {
        return view('livewire.hospital.drug-index');
    }
}
