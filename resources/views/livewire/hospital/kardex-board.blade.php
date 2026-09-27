{{--
    Kárdex de hospitalización — ICU nursing board.

    Layout decisions:
    - Timeline grid (patients × 24 h). The patient column is sticky so the grid
      scrolls horizontally on tablets while identity/triage stay visible.
    - All status colours are computed server-side (KardexBoard::cellFor) so two
      screens with drifting clocks never disagree about what is overdue.
    - Every tap target is ≥ 48 px (WCAG 2.5.5 / gloved hands).
    - Administration requires an explicit Alpine confirmation modal with the
      "5 correctos" checklist before the Livewire action is called.
--}}
<div
    class="flex min-h-full flex-col"
    wire:poll.60s.visible
    x-data="{
        {{-- Confirmation modal state --}}
        open: false,
        busy: false,
        dose: null,
        notes: '',
        checks: { patient: false, drug: false, dose: false, route: false, time: false },
        show(dose) {
            this.dose = dose;
            this.notes = '';
            this.checks = { patient: false, drug: false, dose: false, route: false, time: false };
            this.open = true;
        },
        close() {
            if (this.busy) return;
            this.open = false;
        },
        get allChecked() {
            return Object.values(this.checks).every(Boolean);
        },
        get canConfirm() {
            return this.dose?.actionable
                && this.allChecked
                && !this.busy
                && (!this.dose.controlled || this.notes.trim().length > 0);
        },
        async confirm() {
            if (!this.canConfirm) return;
            this.busy = true;
            try {
                await $wire.markAsAdministered(this.dose.id, this.notes || null);
                this.open = false;
            } finally {
                this.busy = false;
            }
        },
    }"
    x-on:keydown.escape.window="close()"
>
    {{-- ───────────── Header ───────────── --}}
    <header class="z-30 md:sticky md:top-0 border-b border-slate-800 bg-slate-900 text-white shadow-lg">
        <div class="flex flex-wrap items-center gap-3 px-4 py-3 lg:px-6">
            <div class="mr-auto flex items-center gap-3">
                <svg class="size-8 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l3-8 4 16 3-8h4" />
                </svg>
                <div>
                    <h1 class="text-xl font-bold leading-tight tracking-tight">Kárdex UCI</h1>
                    <p class="flex items-center gap-2 text-xs text-slate-300">
                        <span class="relative flex size-2.5">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex size-2.5 rounded-full bg-emerald-500"></span>
                        </span>
                        En vivo
                        @if ($lastSyncAt)
                            · última señal {{ \Illuminate\Support\Carbon::parse($lastSyncAt)->format('H:i:s') }}
                        @endif
                    </p>
                </div>
            </div>

            {{-- Date navigation --}}
            <div class="flex items-center gap-1 rounded-xl bg-slate-800 p-1">
                <button type="button" wire:click="shiftDay(-1)" class="grid size-12 place-items-center rounded-lg text-2xl hover:bg-slate-700 active:bg-slate-600" aria-label="Día anterior">‹</button>
                <input
                    type="date"
                    wire:model.live="date"
                    class="h-12 rounded-lg border-0 bg-slate-900 px-3 text-base font-semibold text-white [color-scheme:dark] focus:ring-2 focus:ring-emerald-400"
                    aria-label="Fecha del kárdex"
                >
                <button type="button" wire:click="shiftDay(1)" class="grid size-12 place-items-center rounded-lg text-2xl hover:bg-slate-700 active:bg-slate-600" aria-label="Día siguiente">›</button>
                <button
                    type="button"
                    wire:click="today"
                    @class([
                        'h-12 rounded-lg px-4 text-sm font-bold uppercase tracking-wide',
                        'bg-emerald-500 text-slate-900' => $this->isToday(),
                        'hover:bg-slate-700' => ! $this->isToday(),
                    ])
                >Hoy</button>
            </div>

            {{-- Patient focus --}}
            <select
                wire:model.live="hospitalizationId"
                class="h-12 min-w-48 rounded-xl border-0 bg-slate-800 px-3 text-base text-white focus:ring-2 focus:ring-emerald-400"
                aria-label="Filtrar por paciente"
            >
                <option value="">Todos los pacientes</option>
                @foreach ($this->patientOptions as $id => $label)
                    <option value="{{ $id }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Triage filter chips --}}
        <div class="flex flex-wrap items-center gap-2 border-t border-slate-800 px-4 pb-3 pt-2 lg:px-6">
            <button
                type="button"
                wire:click="$set('triage', '')"
                @class([
                    'h-11 rounded-full px-4 text-sm font-semibold ring-1 ring-inset',
                    'bg-white text-slate-900 ring-white' => $triage === '',
                    'text-slate-200 ring-slate-600 hover:bg-slate-800' => $triage !== '',
                ])
            >Todos</button>
            @foreach ($triageLevels as $level)
                <button
                    type="button"
                    wire:click="$set('triage', '{{ $level->value }}')"
                    @class([
                        'h-11 rounded-full px-4 text-sm font-semibold ring-1 ring-inset',
                        $level->badgeClasses() => $triage === $level->value,
                        'text-slate-200 ring-slate-600 hover:bg-slate-800' => $triage !== $level->value,
                    ])
                >{{ $level->label() }}</button>
            @endforeach

            @if ($hospitalizationId || $triage !== '')
                <button type="button" wire:click="clearFilters" class="h-11 px-3 text-sm font-semibold text-emerald-300 underline-offset-4 hover:underline">Limpiar filtros</button>
            @endif

            <span wire:loading.delay class="ml-auto text-xs font-medium text-slate-400">Actualizando…</span>
        </div>
    </header>

    {{-- ───────────── Summary tiles ───────────── --}}
    @php($summary = $this->summary)
    <section class="grid grid-cols-2 gap-3 px-4 pt-4 md:grid-cols-4 lg:px-6" aria-label="Resumen del turno">
        <div @class([
            'rounded-2xl p-4 shadow-sm ring-1',
            'bg-red-600 text-white ring-red-700' => $summary['overdue'] > 0,
            'bg-white ring-slate-200' => $summary['overdue'] === 0,
        ])>
            <p class="text-xs font-semibold uppercase tracking-wider opacity-80">Vencidas</p>
            <p class="text-4xl font-black tabular-nums">{{ $summary['overdue'] }}</p>
        </div>
        <div @class([
            'rounded-2xl p-4 shadow-sm ring-1',
            'bg-amber-400 text-amber-950 ring-amber-500' => $summary['due'] > 0,
            'bg-white ring-slate-200' => $summary['due'] === 0,
        ])>
            <p class="text-xs font-semibold uppercase tracking-wider opacity-80">Próximos 30 min</p>
            <p class="text-4xl font-black tabular-nums">{{ $summary['due'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pendientes</p>
            <p class="text-4xl font-black tabular-nums">{{ $summary['pending'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Administradas</p>
            <p class="text-4xl font-black tabular-nums">
                {{ $summary['administered'] }}<span class="text-xl font-bold text-slate-400">/{{ $summary['total'] }}</span>
            </p>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $summary['total'] > 0 ? round($summary['administered'] / $summary['total'] * 100) : 0 }}%"></div>
            </div>
        </div>
    </section>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-x-5 gap-y-1 px-4 pt-3 text-xs font-medium text-slate-600 lg:px-6" aria-hidden="true">
        <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-red-600"></span>Vencida</span>
        <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-amber-400"></span>Próxima</span>
        <span class="flex items-center gap-1.5"><span class="size-3 rounded border-2 border-slate-400 bg-white"></span>Programada</span>
        <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-emerald-600"></span>Administrada</span>
        <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-slate-300"></span>Omitida / cancelada</span>
        <span class="flex items-center gap-1.5"><span class="rounded bg-purple-700 px-1 text-[10px] font-black text-white">CE</span>Control especial</span>
    </div>

    {{-- ───────────── Timeline grid ───────────── --}}
    <main class="flex-1 px-4 py-4 lg:px-6">
        @if ($this->board->isEmpty())
            <div class="grid place-items-center rounded-3xl border-2 border-dashed border-slate-300 bg-white px-6 py-20 text-center">
                <p class="text-2xl font-bold text-slate-700">Sin pacientes hospitalizados</p>
                <p class="mt-2 text-slate-500">No hay dosis programadas para los filtros seleccionados.</p>
            </div>
        @else
            <div
                class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 [scrollbar-gutter:stable]"
                x-init="$nextTick(() => {
                    const col = $el.querySelector('[data-current-hour]');
                    if (col) $el.scrollLeft = Math.max(0, col.offsetLeft - $el.querySelector('thead th').offsetWidth - 16);
                })"
            >
                <table class="w-max min-w-full border-separate border-spacing-0 text-left">
                    <thead>
                        <tr>
                            <th scope="col" class="sticky left-0 top-0 z-20 w-44 min-w-44 border-b sm:w-72 sm:min-w-72 border-r border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                Paciente
                            </th>
                            @foreach ($hours as $hour)
                                <th
                                    scope="col"
                                    @if ($hour === $currentHour) data-current-hour @endif
                                    @class([
                                        'w-36 min-w-36 border-b border-slate-200 px-2 py-3 text-center text-sm font-bold tabular-nums',
                                        'bg-emerald-600 text-white' => $hour === $currentHour,
                                        'bg-slate-50 text-slate-500' => $hour !== $currentHour,
                                    ])
                                >{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->board as $stay)
                            @php($byHour = $stay->kardexSchedules->groupBy(fn ($s) => (int) $s->scheduled_at->setTimezone($timezone)->format('G')))
                            <tr wire:key="stay-{{ $stay->id }}" class="group">
                                {{-- Sticky patient card --}}
                                <th scope="row" @class([
                                    'sticky left-0 z-10 max-w-44 border-b border-l-8 border-r border-slate-200 bg-white px-3 py-3 align-top font-normal sm:max-w-72 sm:px-4',
                                    $stay->triage_level->railClasses(),
                                ])>
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-base font-bold leading-tight sm:text-lg">{{ $stay->patient->name }}</p>
                                            <p class="truncate text-sm text-slate-500">
                                                {{ $stay->patient->species }}@if ($stay->patient->breed) · {{ $stay->patient->breed }}@endif
                                            </p>
                                        </div>
                                        @if ($stay->bed_label)
                                            <span class="shrink-0 rounded-lg bg-slate-900 px-2 py-1 text-sm font-black text-white">{{ $stay->bed_label }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $stay->triage_level->badgeClasses() }}">
                                            {{ $stay->triage_level->label() }}
                                        </span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-slate-700">
                                            {{ rtrim(rtrim((string) $stay->current_weight_kg, '0'), '.') }} kg
                                        </span>
                                        @unless ($stay->isActive())
                                            <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">Egresado</span>
                                        @endunless
                                    </div>
                                </th>

                                @foreach ($hours as $hour)
                                    <td @class([
                                        'h-28 border-b border-slate-100 p-1.5 align-top',
                                        'bg-emerald-50/70' => $hour === $currentHour,
                                        'border-l border-l-slate-100' => true,
                                    ])>
                                        <div class="flex w-36 flex-col gap-1.5">
                                            @foreach ($byHour->get($hour, []) as $schedule)
                                                @php($cell = $this->cellFor($schedule, $stay))
                                                <button
                                                    type="button"
                                                    wire:key="dose-{{ $cell['id'] }}"
                                                    x-on:click="show(@js($cell))"
                                                    @class([
                                                        'relative w-full min-h-14 rounded-xl px-2 py-1.5 text-left text-xs leading-tight shadow-sm ring-1 ring-inset transition active:scale-[0.97]',
                                                        'bg-emerald-600 text-white ring-emerald-700' => $cell['state'] === 'administered',
                                                        'bg-red-600 text-white ring-red-700' => $cell['state'] === 'overdue',
                                                        'bg-amber-400 text-amber-950 ring-amber-500' => $cell['state'] === 'due',
                                                        'bg-white text-slate-900 ring-slate-300 hover:ring-slate-500' => $cell['state'] === 'pending',
                                                        'bg-slate-200 text-slate-500 ring-slate-300 line-through' => in_array($cell['state'], ['omitted', 'cancelled'], true),
                                                    ])
                                                    aria-label="{{ $cell['drug'] }} {{ $cell['dose'] }} {{ $cell['unit'] }} {{ $cell['routeLabel'] }} a las {{ $cell['time'] }}"
                                                >
                                                    @if ($cell['state'] === 'overdue')
                                                        {{-- Pulse only a corner dot: the text must stay fully legible. --}}
                                                        <span class="absolute -right-1 -top-1 flex size-3.5" aria-hidden="true">
                                                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                                            <span class="relative inline-flex size-3.5 rounded-full bg-red-500 ring-2 ring-white"></span>
                                                        </span>
                                                    @endif
                                                    <span class="flex items-center justify-between gap-1">
                                                        <span class="font-black tabular-nums">{{ $cell['time'] }}</span>
                                                        <span class="flex items-center gap-1">
                                                            @if ($cell['controlled'])
                                                                <span class="rounded bg-purple-700 px-1 text-[10px] font-black text-white no-underline">CE</span>
                                                            @endif
                                                            <span class="rounded bg-black/10 px-1 text-[10px] font-bold">{{ $cell['route'] }}</span>
                                                        </span>
                                                    </span>
                                                    <span class="mt-0.5 block break-words font-semibold">{{ $cell['drug'] }}</span>
                                                    <span class="block tabular-nums opacity-90">{{ $cell['dose'] }} {{ $cell['unit'] }}</span>
                                                    @if ($cell['state'] === 'administered')
                                                        <span class="mt-0.5 flex items-center gap-1 text-[10px] font-semibold opacity-90">
                                                            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                                                            {{ $cell['administeredAt'] }}
                                                        </span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </main>

    {{-- ───────────── Confirmation modal (Alpine) ───────────── --}}
    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/70 p-0 backdrop-blur-sm sm:items-center sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="kardex-modal-title"
    >
        <div
            x-show="open"
            x-transition
            x-trap.noscroll="open"
            x-on:click.outside="close()"
            class="max-h-[95vh] w-full max-w-xl overflow-y-auto rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl"
        >
            <template x-if="dose">
                <div>
                    {{-- Header coloured by route: injectables vs. oral / other --}}
                    <div
                        class="flex items-start gap-4 rounded-t-3xl px-6 py-5 text-white"
                        :class="dose.injectable ? 'bg-indigo-700' : 'bg-teal-700'"
                    >
                        <div class="grid size-14 shrink-0 place-items-center rounded-2xl bg-white/15">
                            {{-- syringe / pill icon --}}
                            <svg x-show="dose.injectable" class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m18 2 4 4M17 7l3-3M19 9 8.7 19.3a2.4 2.4 0 0 1-3.4 0l-.6-.6a2.4 2.4 0 0 1 0-3.4L15 5M9 11l4 4M5 19l-3 3M14 4l6 6"/></svg>
                            <svg x-show="!dose.injectable" class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7ZM8.5 8.5l7 7"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold uppercase tracking-wider opacity-80" x-text="dose.actionable ? 'Confirmar administración' : 'Detalle de dosis'"></p>
                            <h2 id="kardex-modal-title" class="truncate text-2xl font-black" x-text="dose.drug"></h2>
                            <p class="text-lg font-bold tabular-nums">
                                <span x-text="dose.dose"></span> <span x-text="dose.unit"></span>
                                · <span x-text="dose.routeLabel"></span>
                            </p>
                        </div>
                        <button type="button" x-on:click="close()" class="grid size-12 shrink-0 place-items-center rounded-xl text-3xl hover:bg-white/15" aria-label="Cerrar">&times;</button>
                    </div>

                    <div class="space-y-4 px-6 py-5">
                        {{-- Patient identity --}}
                        <div class="flex items-center justify-between rounded-2xl bg-slate-100 px-4 py-3">
                            <div>
                                <p class="text-xl font-bold" x-text="dose.patient"></p>
                                <p class="text-sm text-slate-600"><span x-text="dose.species"></span> · <span x-text="dose.weight"></span> kg</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold uppercase text-slate-500">Cama</p>
                                <p class="text-2xl font-black" x-text="dose.bed ?? '—'"></p>
                            </div>
                        </div>

                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
                                <dt class="text-xs font-semibold uppercase text-slate-500">Hora programada</dt>
                                <dd class="text-xl font-black tabular-nums" x-text="dose.time"></dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
                                <dt class="text-xs font-semibold uppercase text-slate-500">Inventario</dt>
                                <dd class="font-semibold tabular-nums">
                                    <span x-text="dose.openBalance"></span> <span x-text="dose.unit"></span> abierto ·
                                    <span x-text="dose.stockPackages"></span> sellados
                                </dd>
                            </div>
                        </dl>

                        {{-- Clinical warnings --}}
                        <template x-if="dose.state === 'overdue' && dose.actionable">
                            <p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 ring-1 ring-red-200">
                                Dosis vencida (más de 30 min de retraso). Registre el motivo en observaciones.
                            </p>
                        </template>
                        <template x-if="dose.packagesToOpen > 0 && dose.actionable">
                            <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900 ring-1 ring-amber-200">
                                Se abrirá(n) <span x-text="dose.packagesToOpen"></span> <span x-text="dose.presentation"></span>(s) nuevo(s). Rotule fecha y hora de apertura.
                            </p>
                        </template>
                        <template x-if="dose.controlled">
                            <p class="rounded-xl bg-purple-50 px-4 py-3 text-sm font-semibold text-purple-900 ring-1 ring-purple-200">
                                Medicamento de control especial: la observación es obligatoria (lote, testigo, justificación).
                            </p>
                        </template>

                        {{-- Read-only details for already-closed doses --}}
                        <template x-if="!dose.actionable">
                            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm ring-1 ring-slate-200">
                                <p x-show="dose.administeredAt">
                                    Administrada a las <strong x-text="dose.administeredAt"></strong>
                                    por <strong x-text="dose.administeredBy ?? '—'"></strong>.
                                </p>
                                <p x-show="!dose.administeredAt" class="font-semibold">Estado: <span x-text="dose.state"></span></p>
                                <p x-show="dose.notes" class="mt-2 whitespace-pre-line text-slate-600" x-text="dose.notes"></p>
                            </div>
                        </template>

                        {{-- "5 correctos" checklist --}}
                        <template x-if="dose.actionable">
                            <div class="space-y-4">
                                <fieldset class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <legend class="mb-2 text-sm font-bold uppercase tracking-wider text-slate-500">Verificación de los 5 correctos</legend>
                                    @foreach ([
                                        'patient' => 'Paciente correcto',
                                        'drug' => 'Medicamento correcto',
                                        'dose' => 'Dosis correcta',
                                        'route' => 'Vía correcta',
                                        'time' => 'Hora correcta',
                                    ] as $key => $label)
                                        <label
                                            class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl px-4 ring-2 ring-inset transition"
                                            :class="checks.{{ $key }} ? 'bg-emerald-50 ring-emerald-500' : 'bg-white ring-slate-200'"
                                        >
                                            <input type="checkbox" x-model="checks.{{ $key }}" class="size-6 rounded-md border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                            <span class="text-base font-semibold">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </fieldset>

                                <label class="block">
                                    <span class="text-sm font-bold uppercase tracking-wider text-slate-500">
                                        Observaciones <span x-show="dose.controlled" class="text-purple-700">(obligatorio)</span>
                                    </span>
                                    <textarea
                                        x-model="notes"
                                        rows="3"
                                        maxlength="1000"
                                        class="mt-1 block w-full rounded-xl border-slate-300 text-base select-text focus:border-emerald-500 focus:ring-emerald-500"
                                        placeholder="Lote, reacción, motivo de retraso…"
                                    ></textarea>
                                </label>
                            </div>
                        </template>
                    </div>

                    <div class="sticky bottom-0 flex gap-3 border-t border-slate-200 bg-white px-6 py-4">
                        <button type="button" x-on:click="close()" class="h-14 flex-1 rounded-2xl bg-slate-100 text-lg font-bold text-slate-700 hover:bg-slate-200 active:bg-slate-300">
                            <span x-text="dose.actionable ? 'Cancelar' : 'Cerrar'"></span>
                        </button>
                        <button
                            type="button"
                            x-show="dose.actionable"
                            x-on:click="confirm()"
                            :disabled="!canConfirm"
                            class="flex h-14 flex-[2] items-center justify-center gap-2 rounded-2xl bg-emerald-600 text-lg font-black text-white shadow-lg transition hover:bg-emerald-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none"
                        >
                            <svg x-show="busy" class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" class="opacity-75"/></svg>
                            <span x-text="busy ? 'Registrando…' : 'Confirmar administración'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
