<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-ink">Pacientes</h1>
            <p class="mt-1 text-muted">Registro de pacientes y propietarios de la clínica.</p>
        </div>
        @can('create', App\Models\Patient::class)
            <a href="{{ route('hospital.patients.create') }}" class="btn-primary">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Nuevo paciente
            </a>
        @endcan
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <label class="relative flex-1">
            <span class="sr-only">Buscar</span>
            <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-muted" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nombre del paciente, propietario, documento, teléfono o microchip" class="field !pl-12" autocomplete="off">
        </label>
        <div class="flex rounded-xl border border-line bg-surface p-1" role="group" aria-label="Filtro">
            <button type="button" wire:click="$set('filter', '')" @class(['min-h-10 rounded-lg px-4 text-sm font-bold', 'bg-accent text-on-accent' => $filter === '', 'text-muted hover:text-ink' => $filter !== ''])>Todos</button>
            <button type="button" wire:click="$set('filter', 'hospitalizados')" @class(['min-h-10 rounded-lg px-4 text-sm font-bold', 'bg-accent text-on-accent' => $filter === 'hospitalizados', 'text-muted hover:text-ink' => $filter !== 'hospitalizados'])>Hospitalizados</button>
        </div>
    </div>

    <div class="panel mt-6 overflow-hidden" wire:loading.class="opacity-60">
        @forelse ($this->patients as $patient)
            @php($stay = $patient->activeHospitalization)
            <a href="{{ route('hospital.patients.show', $patient) }}" wire:key="patient-{{ $patient->id }}" class="flex min-h-16 items-center gap-4 border-b border-line px-4 py-3 transition-colors last:border-b-0 hover:bg-sunken sm:px-5">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold text-ink">
                        {{ $patient->name }}
                        <span class="font-normal text-muted">· {{ $patient->species }}@if ($patient->breed) · {{ $patient->breed }}@endif</span>
                    </p>
                    <p class="truncate text-sm text-muted">
                        {{ $patient->owner_name }}@if ($patient->owner_phone) · {{ $patient->owner_phone }}@endif
                        @if ($patient->age()) · {{ $patient->age() }}@endif
                    </p>
                    @if ($stay)
                        {{-- On phones the stay badge goes under the name instead of squeezing it. --}}
                        <span class="mt-1.5 inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset sm:hidden {{ $stay->triage_level->badgeClasses() }}">
                            {{ $stay->bed_label ?? 'Hospitalizado' }} · {{ $stay->triage_level->label() }}
                        </span>
                    @endif
                </div>
                @if ($stay)
                    <span class="hidden items-center rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset sm:inline-flex {{ $stay->triage_level->badgeClasses() }}">
                        {{ $stay->bed_label ?? 'Hospitalizado' }} · {{ $stay->triage_level->label() }}
                    </span>
                @endif
                <svg viewBox="0 0 24 24" class="size-5 shrink-0 text-muted" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </a>
        @empty
            <div class="px-6 py-16 text-center">
                <p class="text-xl font-bold text-ink">{{ trim($search) !== '' ? 'Sin resultados para "'.$search.'"' : 'Aún no hay pacientes registrados' }}</p>
                <p class="mt-2 text-muted">{{ trim($search) !== '' ? 'Pruebe con otro nombre, documento o microchip.' : 'Registre el primer paciente para poder ingresarlo a hospitalización.' }}</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $this->patients->links() }}
    </div>
</div>
