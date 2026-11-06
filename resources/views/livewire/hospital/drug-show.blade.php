@php($low = $drug->is_active && $drug->isBelowReorderLevel())
<div class="mx-auto max-w-5xl">
    @if ($notice)
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-ok bg-ok-soft px-4 py-3 text-base font-semibold text-ok" role="status">
            <svg viewBox="0 0 24 24" class="mt-0.5 size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>
            <span>{{ $notice }}</span>
        </div>
    @endif

    <nav class="text-sm text-muted" aria-label="Ruta">
        <a href="{{ route('hospital.drugs.index') }}" class="link-quiet">Medicamentos</a>
        <span aria-hidden="true"> / </span> {{ $drug->name }}
    </nav>

    <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-ink">{{ $drug->name }}</h1>
            <p class="mt-1 text-lg text-muted">
                {{ ucfirst($drug->presentation_unit) }} de {{ $drug->packageVolume()->display() }} {{ $drug->fraction_unit }}
                @if ($drug->concentration_amount !== null)
                    · {{ App\Support\Pharmacy\Quantity::of($drug->concentration_amount)->display() }} {{ $drug->concentration_unit }}/{{ $drug->fraction_unit }}
                @endif
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($drug->is_controlled)
                    <span class="rounded-full bg-sunken px-3 py-1 text-xs font-bold text-ink ring-1 ring-inset ring-edge">Control especial</span>
                @endif
                @unless ($drug->is_active)
                    <span class="rounded-full bg-sunken px-3 py-1 text-xs font-bold text-muted">Inactivo</span>
                @endunless
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('update', $drug)
                <a href="{{ route('hospital.drugs.edit', $drug) }}" class="btn-secondary">Editar</a>
            @endcan
            @can('receiveStock', $drug)
                <button type="button" wire:click="openReceive" class="btn-primary">Registrar entrada</button>
            @endcan
        </div>
    </div>

    {{-- Stock --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-3" aria-label="Existencias">
        <div @class(['panel p-5', '!border-soon' => $low])>
            <p class="text-sm font-semibold text-muted">Empaques sellados</p>
            <p class="pp-num mt-1 text-4xl font-extrabold text-ink">{{ $drug->stock_packages }}</p>
            @if ($low)
                <p class="mt-2 inline-flex items-center gap-1.5 text-sm font-bold text-soon">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 8v5M12 17h.01"/></svg>
                    Stock bajo (aviso con {{ $drug->reorder_level_packages }} o menos)
                </p>
            @endif
        </div>
        <div class="panel p-5">
            <p class="text-sm font-semibold text-muted">Abierto en uso</p>
            <p class="pp-num mt-1 text-4xl font-extrabold text-ink">{{ $drug->openBalance()->display() }} <span class="text-xl font-bold text-muted">{{ $drug->fraction_unit }}</span></p>
            @if ($drug->opened_at && $drug->openBalance()->isPositive())
                <p class="mt-2 text-sm text-muted">
                    Abierto el {{ $drug->opened_at->setTimezone($timezone)->isoFormat('D MMM, HH:mm') }}
                    @if ($drug->stability_hours_after_opening)
                        · vence {{ $drug->opened_at->copy()->addHours($drug->stability_hours_after_opening)->setTimezone($timezone)->isoFormat('D MMM, HH:mm') }}
                    @endif
                </p>
            @endif
        </div>
        <div class="panel p-5">
            <p class="text-sm font-semibold text-muted">Total disponible</p>
            <p class="pp-num mt-1 text-4xl font-extrabold text-ink">{{ $drug->totalAvailable()->display() }} <span class="text-xl font-bold text-muted">{{ $drug->fraction_unit }}</span></p>
        </div>
    </section>

    <dl class="panel mt-4 grid gap-x-6 gap-y-4 p-5 sm:grid-cols-3">
        <div><dt class="text-sm font-semibold text-muted">Principio activo</dt><dd class="text-ink">{{ $drug->active_ingredient ?? '—' }}</dd></div>
        <div><dt class="text-sm font-semibold text-muted">Registro ICA / INVIMA</dt><dd class="pp-num text-ink">{{ $drug->sanitary_registry ?? '—' }}</dd></div>
        <div><dt class="text-sm font-semibold text-muted">Estable tras abrir</dt><dd class="text-ink">{{ $drug->stability_hours_after_opening ? $drug->stability_hours_after_opening.' horas' : 'Sin límite registrado' }}</dd></div>
    </dl>

    {{-- Ledger --}}
    <section class="panel mt-6 overflow-hidden" aria-labelledby="movimientos">
        <div class="border-b border-line px-5 py-4">
            <h2 id="movimientos" class="text-xl font-bold text-ink">Libro de movimientos</h2>
            <p class="text-sm text-muted">Cada entrada, apertura, dosis y descarte, con el saldo resultante. No se puede editar ni borrar.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead class="bg-sunken text-xs font-bold uppercase tracking-wide text-muted">
                    <tr>
                        <th scope="col" class="px-5 py-3">Fecha</th>
                        <th scope="col" class="px-3 py-3">Movimiento</th>
                        <th scope="col" class="px-3 py-3 text-right">Empaques</th>
                        <th scope="col" class="px-3 py-3 text-right">{{ $drug->fraction_unit }}</th>
                        <th scope="col" class="px-3 py-3 text-right">Saldo</th>
                        <th scope="col" class="px-5 py-3">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->movements as $m)
                        @php($fraction = App\Support\Pharmacy\Quantity::of($m->fraction_delta))
                        <tr class="border-t border-line align-top" wire:key="mov-{{ $m->id }}">
                            <td class="pp-num whitespace-nowrap px-5 py-3 text-ink">{{ $m->created_at->setTimezone($timezone)->isoFormat('D MMM, HH:mm') }}</td>
                            <td class="px-3 py-3 font-semibold text-ink">{{ $m->movement_type->label() }}</td>
                            <td class="pp-num px-3 py-3 text-right text-ink">{{ $m->packages_delta === 0 ? '—' : ($m->packages_delta > 0 ? '+' : '').$m->packages_delta }}</td>
                            <td class="pp-num px-3 py-3 text-right text-ink">{{ $fraction->isZero() ? '—' : ($fraction->isPositive() ? '+' : '').$fraction->display() }}</td>
                            <td class="pp-num whitespace-nowrap px-3 py-3 text-right text-muted">{{ $m->stock_packages_after }} + {{ App\Support\Pharmacy\Quantity::of($m->open_fraction_balance_after)->display() }}</td>
                            <td class="px-5 py-3 text-muted">
                                @if ($m->source instanceof App\Models\KardexSchedule && $m->source->hospitalization)
                                    {{ $m->source->hospitalization->patient?->name }}@if ($m->source->hospitalization->bed_label) · {{ $m->source->hospitalization->bed_label }}@endif ·
                                @endif
                                {{ $m->user?->name ?? 'Sistema' }}
                                @if ($m->notes)
                                    <span class="block text-xs">{{ $m->notes }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-muted">Sin movimientos todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <div class="mt-4">{{ $this->movements->links() }}</div>

    {{-- Receive stock --}}
    @if ($showReceive)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/60 sm:items-center sm:p-6" role="dialog" aria-modal="true" aria-labelledby="entrada-titulo" x-data x-trap.noscroll="true" x-on:keydown.escape.window="$wire.set('showReceive', false)">
            <form wire:submit="receive" class="panel w-full max-w-md rounded-b-none p-6 sm:rounded-b-3xl sm:p-8" x-on:click.outside="$wire.set('showReceive', false)">
                <h2 id="entrada-titulo" class="text-2xl font-extrabold text-ink">Registrar entrada</h2>
                <p class="mt-1 text-muted">{{ $drug->name }} · {{ $drug->presentation_unit }} de {{ $drug->packageVolume()->display() }} {{ $drug->fraction_unit }}</p>

                <label class="mt-6 block">
                    <span class="text-sm font-semibold text-ink">Empaques sellados recibidos</span>
                    <input type="number" min="1" max="10000" wire:model="packages" autofocus class="field pp-num mt-1.5 @error('packages') !border-late @enderror">
                    @error('packages') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </label>
                <label class="mt-5 block">
                    <span class="text-sm font-semibold text-ink">Observaciones <span class="font-normal text-muted">(lote, vencimiento, factura)</span></span>
                    <textarea wire:model="receiveNotes" rows="2" maxlength="500" class="field mt-1.5" placeholder="Lote 23A14, vence 2027-08, factura 4512"></textarea>
                    @error('receiveNotes') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </label>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="$set('showReceive', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">Registrar entrada</button>
                </div>
            </form>
        </div>
    @endif
</div>
