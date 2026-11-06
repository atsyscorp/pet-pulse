<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-ink">Medicamentos</h1>
            <p class="mt-1 text-muted">Catálogo de la clínica y existencias: empaques sellados más el volumen ya abierto.</p>
        </div>
        @can('create', App\Models\Drug::class)
            <a href="{{ route('hospital.drugs.create') }}" class="btn-primary">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Nuevo medicamento
            </a>
        @endcan
    </div>

    <div class="mt-6 flex flex-col gap-3 lg:flex-row">
        <label class="relative flex-1">
            <span class="sr-only">Buscar</span>
            <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-muted" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nombre, principio activo o registro sanitario" class="field !pl-12" autocomplete="off">
        </label>
        <div class="flex flex-wrap rounded-xl border border-line bg-surface p-1" role="group" aria-label="Filtro">
            @foreach (['' => 'Activos', 'stock-bajo' => 'Stock bajo', 'control-especial' => 'Control especial', 'inactivos' => 'Inactivos'] as $value => $label)
                <button type="button" wire:click="$set('filter', '{{ $value }}')" @class(['min-h-10 rounded-lg px-4 text-sm font-bold', 'bg-accent text-on-accent' => $filter === $value, 'text-muted hover:text-ink' => $filter !== $value])>{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="panel mt-6 overflow-hidden" wire:loading.class="opacity-60">
        @forelse ($this->drugs as $drug)
            @php($low = $drug->is_active && $drug->isBelowReorderLevel())
            <a href="{{ route('hospital.drugs.show', $drug) }}" wire:key="drug-{{ $drug->id }}" class="flex min-h-16 flex-col gap-2 border-b border-line px-4 py-3 transition-colors last:border-b-0 hover:bg-sunken sm:flex-row sm:items-center sm:gap-4 sm:px-5">
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-bold text-ink">
                        {{ $drug->name }}
                        @if ($drug->active_ingredient && $drug->active_ingredient !== $drug->name)
                            <span class="font-normal text-muted">· {{ $drug->active_ingredient }}</span>
                        @endif
                    </p>
                    <p class="text-sm text-muted">
                        {{ ucfirst($drug->presentation_unit) }} de {{ $drug->packageVolume()->display() }} {{ $drug->fraction_unit }}
                        @if ($drug->concentration_amount !== null)
                            · {{ App\Support\Pharmacy\Quantity::of($drug->concentration_amount)->display() }} {{ $drug->concentration_unit }}/{{ $drug->fraction_unit }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($drug->is_controlled)
                        <span class="rounded-full bg-sunken px-3 py-1 text-xs font-bold text-ink ring-1 ring-inset ring-edge">Control especial</span>
                    @endif
                    @if ($low)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-soon-soft px-3 py-1 text-xs font-bold text-soon">
                            <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 8v5M12 17h.01"/></svg>
                            Stock bajo
                        </span>
                    @endif
                    <span class="pp-num min-w-40 text-right text-sm text-ink sm:text-base">
                        <strong>{{ $drug->stock_packages }}</strong> sellados
                        @if ($drug->openBalance()->isPositive())
                            <span class="text-muted">+ {{ $drug->openBalance()->display() }} {{ $drug->fraction_unit }}</span>
                        @endif
                    </span>
                    <svg viewBox="0 0 24 24" class="hidden size-5 shrink-0 text-muted sm:block" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                </div>
            </a>
        @empty
            <div class="px-6 py-16 text-center">
                <p class="text-xl font-bold text-ink">{{ trim($search) !== '' ? 'Sin resultados para "'.$search.'"' : 'No hay medicamentos en este filtro' }}</p>
                <p class="mt-2 text-muted">Los medicamentos del catálogo son los que se pueden prescribir.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $this->drugs->links() }}
    </div>
</div>
