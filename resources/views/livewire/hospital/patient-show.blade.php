@php($stay = $this->activeStay)
<div class="mx-auto max-w-5xl">
    @if ($notice)
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-ok bg-ok-soft px-4 py-3 text-base font-semibold text-ok" role="status">
            <svg viewBox="0 0 24 24" class="mt-0.5 size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>
            <span>{{ $notice }}</span>
        </div>
    @endif
    <nav class="text-sm text-muted" aria-label="Ruta">
        <a href="{{ route('hospital.patients.index') }}" class="link-quiet">Pacientes</a>
        <span aria-hidden="true"> / </span> {{ $patient->name }}
    </nav>

    <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-ink">{{ $patient->name }}</h1>
            <p class="mt-1 text-lg text-muted">
                {{ $patient->species }}@if ($patient->breed) · {{ $patient->breed }}@endif
                @if ($patient->sex) · {{ $patient->sex->label() }}@endif
                @if ($patient->age()) · {{ $patient->age() }}@endif
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('update', $patient)
                <a href="{{ route('hospital.patients.edit', $patient) }}" class="btn-secondary">Editar datos</a>
            @endcan
            @if ($stay === null)
                @can('create', App\Models\Hospitalization::class)
                    <a href="{{ route('hospital.patients.admit', $patient) }}" class="btn-primary">Ingresar a hospitalización</a>
                @endcan
            @else
                <a href="{{ route('hospital.kardex', ['paciente' => $stay->id]) }}" class="btn-primary">Ver kárdex</a>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
            {{-- Current stay --}}
            @if ($stay)
                <section class="panel overflow-hidden" aria-labelledby="estancia">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                        <h2 id="estancia" class="text-xl font-bold text-ink">Hospitalización en curso</h2>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ $stay->triage_level->badgeClasses() }}">{{ $stay->triage_level->label() }}</span>
                    </div>
                    <dl class="grid gap-x-6 gap-y-4 px-5 py-5 sm:grid-cols-2">
                        <div><dt class="text-sm font-semibold text-muted">Cama</dt><dd class="text-lg font-bold text-ink">{{ $stay->bed_label ?? '—' }}</dd></div>
                        <div><dt class="text-sm font-semibold text-muted">Peso actual</dt><dd class="pp-num text-lg font-bold text-ink">{{ str_replace('.', ',', rtrim(rtrim($stay->current_weight_kg, '0'), '.')) }} kg</dd></div>
                        <div><dt class="text-sm font-semibold text-muted">Ingreso</dt><dd class="pp-num text-ink">{{ $stay->admitted_at->setTimezone($timezone)->isoFormat('D MMM YYYY, HH:mm') }}</dd></div>
                        <div><dt class="text-sm font-semibold text-muted">Veterinario tratante</dt><dd class="text-ink">{{ $stay->attendingVet?->name ?? '—' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-sm font-semibold text-muted">Motivo de ingreso</dt><dd class="whitespace-pre-line text-ink">{{ $stay->admission_reason }}</dd></div>
                        @if ($stay->diagnosis)
                            <div class="sm:col-span-2"><dt class="text-sm font-semibold text-muted">Diagnóstico presuntivo</dt><dd class="whitespace-pre-line text-ink">{{ $stay->diagnosis }}</dd></div>
                        @endif
                    </dl>
                    @can('discharge', $stay)
                        <div class="border-t border-line px-5 py-4">
                            <button type="button" wire:click="openDischarge" class="btn-secondary">Dar de alta / cerrar hospitalización</button>
                        </div>
                    @endcan
                </section>

                {{-- Medication plan --}}
                <section class="panel overflow-hidden" aria-labelledby="plan">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                        <h2 id="plan" class="text-xl font-bold text-ink">Plan de medicación</h2>
                        @can('prescribe', $stay)
                            <a href="{{ route('hospital.stays.prescribe', $stay) }}" class="btn-primary !py-2 !text-sm">
                                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                Nueva prescripción
                            </a>
                        @endcan
                    </div>
                    @forelse ($this->prescriptions as $rx)
                        <article class="border-b border-line px-5 py-4 last:border-b-0" wire:key="rx-{{ $rx->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-lg font-bold text-ink">
                                        {{ $rx->drug->name }}
                                        @if ($rx->drug->is_controlled)
                                            <span class="ml-1 rounded-full bg-sunken px-2 py-0.5 align-middle text-xs font-bold text-ink ring-1 ring-inset ring-edge">Control especial</span>
                                        @endif
                                    </p>
                                    <p class="pp-num text-ink">
                                        <strong>{{ $rx->dose()->display() }} {{ $rx->drug->fraction_unit }}</strong>
                                        @if ($rx->dose_per_kg !== null)
                                            <span class="text-muted">({{ str_replace('.', ',', rtrim(rtrim((string) $rx->dose_per_kg, '0'), '.')) }} {{ $rx->drug->concentration_unit }}/kg)</span>
                                        @endif
                                        · {{ $rx->route->value }} · {{ $rx->frequencyLabel() }}
                                    </p>
                                    <p class="pp-num mt-1 text-sm text-muted">
                                        {{ $rx->starts_at->setTimezone($timezone)->isoFormat('D MMM, HH:mm') }}
                                        @if ($rx->frequency_hours) → {{ $rx->ends_at->setTimezone($timezone)->isoFormat('D MMM, HH:mm') }}@endif
                                        · {{ $rx->administered_count }}/{{ $rx->doses_planned }} administradas
                                        · {{ $rx->prescriber?->name ?? '—' }}
                                    </p>
                                    @if ($rx->instructions)
                                        <p class="mt-1 text-sm text-ink">{{ $rx->instructions }}</p>
                                    @endif
                                    @if ($rx->status === App\Enums\PrescriptionStatus::Suspended)
                                        <p class="mt-1 text-sm text-muted">Suspendida {{ $rx->suspended_at?->setTimezone($timezone)->isoFormat('D MMM, HH:mm') }}: {{ $rx->suspension_reason }}</p>
                                    @endif
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    {{-- Neutral on purpose: green/red/amber are reserved for dose state (DESIGN.md). --}}
                                    <span @class([
                                        'rounded-full px-3 py-1 text-xs font-bold',
                                        'text-ink ring-1 ring-inset ring-ink' => $rx->isActive(),
                                        'bg-sunken text-muted' => ! $rx->isActive(),
                                    ])>{{ $rx->status->label() }}</span>
                                    @can('suspend', $rx)
                                        <button type="button" wire:click="openSuspend({{ $rx->id }})" class="btn-secondary !min-h-10 !px-3 !py-1.5 !text-sm">Suspender</button>
                                    @endcan
                                </div>
                            </div>
                        </article>
                    @empty
                        <p class="px-5 py-6 text-muted">Sin prescripciones. @can('prescribe', $stay)Use "Nueva prescripción" para programar las dosis en el kárdex.@endcan</p>
                    @endforelse
                </section>

                {{-- What the owner sees in the app --}}
                <section class="panel overflow-hidden" aria-labelledby="propietario-app">
                    <div class="border-b border-line px-5 py-4">
                        <h2 id="propietario-app" class="text-xl font-bold text-ink">Comunicación con el propietario</h2>
                        <p class="text-sm text-muted">
                            Lo que se publica aquí aparece en la app PetPulse del propietario.
                            @if ($this->ownerLinks->isEmpty()) Aún no hay propietarios vinculados a este paciente. @endif
                        </p>
                    </div>
                    @can('informOwner', $stay)
                        <form wire:submit="saveOwnerStatus" class="flex flex-col gap-3 border-b border-line px-5 py-4 sm:flex-row sm:items-end">
                            <label class="flex-1">
                                <span class="text-sm font-semibold text-ink">Estado visible para el propietario</span>
                                <select wire:model="ownerStatus" class="field mt-1.5">
                                    <option value="">Sin estado (solo "Hospitalizado")</option>
                                    @foreach ($ownerStatuses as $st)
                                        <option value="{{ $st->value }}">{{ $st->label() }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button type="submit" class="btn-secondary" wire:loading.attr="disabled">Actualizar estado</button>
                        </form>
                        <form wire:submit="publishOwnerUpdate" class="border-b border-line px-5 py-4">
                            <label class="block">
                                <span class="text-sm font-semibold text-ink">Nuevo parte médico</span>
                                <textarea wire:model="updateBody" rows="3" maxlength="2000" class="field mt-1.5 @error('updateBody') !border-late @enderror" placeholder="Hoy comió bien y está más activo. Continúa con antibiótico; mañana repetimos exámenes."></textarea>
                                @error('updateBody') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                            </label>
                            <div class="mt-3 flex justify-end">
                                <button type="submit" class="btn-primary !py-2 !text-sm" wire:loading.attr="disabled">Publicar parte</button>
                            </div>
                        </form>
                    @endcan
                    @if ($stay->owner_status)
                        <p class="border-b border-line px-5 py-3 text-sm text-muted">El propietario ve: <strong class="text-ink">{{ $stay->owner_status->label() }}</strong> (actualizado {{ $stay->owner_status_updated_at?->setTimezone($timezone)->isoFormat('D MMM, HH:mm') }})</p>
                    @endif
                    @forelse ($this->ownerUpdates as $u)
                        <article class="border-b border-line px-5 py-3 last:border-b-0" wire:key="ou-{{ $u->id }}">
                            <p class="whitespace-pre-line text-ink">{{ $u->body }}</p>
                            <p class="pp-num mt-1 text-xs text-muted">{{ $u->author?->name ?? '—' }} · {{ $u->created_at->setTimezone($timezone)->isoFormat('D MMM, HH:mm') }}</p>
                        </article>
                    @empty
                        <p class="px-5 py-4 text-sm text-muted">Sin partes médicos publicados en esta hospitalización.</p>
                    @endforelse
                </section>
            @else
                <section class="panel px-5 py-8 text-center">
                    <p class="text-lg font-bold text-ink">Sin hospitalización activa</p>
                    <p class="mt-1 text-muted">El paciente no ocupa cama en este momento.</p>
                </section>
            @endif

            {{-- History --}}
            <section class="panel overflow-hidden" aria-labelledby="historial">
                <h2 id="historial" class="border-b border-line px-5 py-4 text-xl font-bold text-ink">Hospitalizaciones anteriores</h2>
                @forelse ($this->history as $past)
                    <article class="border-b border-line px-5 py-4 last:border-b-0" wire:key="stay-{{ $past->id }}">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="pp-num font-bold text-ink">
                                {{ $past->admitted_at->setTimezone($timezone)->isoFormat('D MMM YYYY') }}
                                → {{ $past->discharged_at?->setTimezone($timezone)->isoFormat('D MMM YYYY') ?? '—' }}
                            </p>
                            <span class="rounded-full bg-sunken px-3 py-1 text-xs font-bold text-ink">{{ $past->status->label() }}</span>
                        </div>
                        <p class="mt-1 text-sm text-muted">{{ $past->triage_level->label() }}@if ($past->bed_label) · {{ $past->bed_label }}@endif · {{ $past->admission_reason }}</p>
                        @if ($past->discharge_notes)
                            <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $past->discharge_notes }}</p>
                        @endif
                    </article>
                @empty
                    <p class="px-5 py-6 text-muted">Sin hospitalizaciones anteriores.</p>
                @endforelse
            </section>
        </div>

        {{-- Owner --}}
        <div class="space-y-6 self-start">
        <aside class="panel p-5" aria-labelledby="propietario">
            <h2 id="propietario" class="text-xl font-bold text-ink">Propietario</h2>
            <dl class="mt-4 space-y-3">
                <div><dt class="text-sm font-semibold text-muted">Nombre</dt><dd class="text-ink">{{ $patient->owner_name }}</dd></div>
                <div><dt class="text-sm font-semibold text-muted">Documento</dt><dd class="pp-num text-ink">{{ $patient->owner_document ?? '—' }}</dd></div>
                <div><dt class="text-sm font-semibold text-muted">Teléfono</dt><dd class="pp-num text-ink">@if ($patient->owner_phone)<a href="tel:{{ preg_replace('/\D+/', '', $patient->owner_phone) }}" class="link-quiet">{{ $patient->owner_phone }}</a>@else — @endif</dd></div>
                <div><dt class="text-sm font-semibold text-muted">Microchip</dt><dd class="pp-num text-ink">{{ $patient->microchip ?? '—' }}</dd></div>
                <div><dt class="text-sm font-semibold text-muted">Registrado</dt><dd class="pp-num text-ink">{{ $patient->created_at->setTimezone($timezone)->isoFormat('D MMM YYYY') }}</dd></div>
            </dl>
        </aside>

        <section class="panel p-5" aria-labelledby="acceso-app">
            <h2 id="acceso-app" class="text-xl font-bold text-ink">App del propietario</h2>
            <p class="mt-1 text-sm text-muted">El propietario ve la ficha, el estado de la hospitalización, los partes médicos y los medicamentos aplicados.</p>

            @if ($issuedCodeDisplay)
                <div class="mt-4 rounded-2xl border border-line bg-sunken p-4 text-center" role="status">
                    <p class="text-sm font-semibold text-muted">Código de vinculación</p>
                    <p class="pp-num mt-1 select-all text-3xl font-extrabold tracking-widest text-ink">{{ $issuedCodeDisplay }}</p>
                    <div class="mx-auto mt-3 w-44 rounded-xl bg-white p-2 [&>svg]:h-auto [&>svg]:w-full" aria-label="Código QR de vinculación">{!! $issuedCodeQr !!}</div>
                    <p class="mt-3 text-xs text-muted">
                        Un solo uso · vence el {{ \Illuminate\Support\Carbon::parse($issuedCodeExpiresAt)->setTimezone($timezone)->isoFormat('D MMM, HH:mm') }}.
                        Entréguelo solo al propietario. Generar otro anula este.
                    </p>
                </div>
            @endif

            @can('manageOwnerAccess', $patient)
                <button type="button" wire:click="issueLinkCode" class="btn-secondary mt-4 w-full" wire:loading.attr="disabled">
                    {{ $issuedCodeDisplay ? 'Generar otro código' : 'Generar código de vinculación' }}
                </button>
            @endcan

            <h3 class="mt-5 text-sm font-semibold text-muted">Con acceso</h3>
            <ul class="mt-2 divide-y divide-line">
                @forelse ($this->ownerLinks as $link)
                    <li class="flex items-center justify-between gap-3 py-2" wire:key="link-{{ $link->id }}">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink">{{ $link->owner->name }}</p>
                            <p class="truncate text-xs text-muted">{{ $link->owner->email }} · desde {{ $link->linked_at->setTimezone($timezone)->isoFormat('D MMM YYYY') }}</p>
                        </div>
                        @can('manageOwnerAccess', $patient)
                            <button type="button" wire:click="revokeOwnerLink({{ $link->id }})" wire:confirm="¿Retirar el acceso de {{ $link->owner->name }} a esta mascota en la app?" class="btn-secondary !min-h-10 !px-3 !py-1.5 !text-sm">Retirar</button>
                        @endcan
                    </li>
                @empty
                    <li class="py-2 text-sm text-muted">Nadie todavía.</li>
                @endforelse
            </ul>
        </section>
        </div>
    </div>

    {{-- Suspend prescription modal --}}
    @if ($suspendingId)
        @php($suspending = $this->prescriptions->firstWhere('id', $suspendingId))
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/60 sm:items-center sm:p-6" role="dialog" aria-modal="true" aria-labelledby="suspender-titulo" x-data x-trap.noscroll="true" x-on:keydown.escape.window="$wire.set('suspendingId', null)">
            <form wire:submit="suspend" class="panel w-full max-w-lg rounded-b-none p-6 sm:rounded-b-3xl sm:p-8" x-on:click.outside="$wire.set('suspendingId', null)">
                <h2 id="suspender-titulo" class="text-2xl font-extrabold text-ink">Suspender prescripción</h2>
                <p class="mt-1 text-muted">{{ $suspending?->drug->name }} · {{ $suspending?->frequencyLabel() }}. Las dosis pendientes se cancelan; las ya administradas quedan en el registro.</p>
                <label class="mt-6 block">
                    <span class="text-sm font-semibold text-ink">Motivo</span>
                    <textarea wire:model="suspensionReason" rows="3" maxlength="500" autofocus class="field mt-1.5 @error('suspensionReason') !border-late @enderror" placeholder="Cambio de antibiótico según antibiograma"></textarea>
                    @error('suspensionReason') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </label>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="$set('suspendingId', null)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">Suspender</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Discharge modal --}}
    @if ($showDischarge && $stay)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/60 p-0 sm:items-center sm:p-6" role="dialog" aria-modal="true" aria-labelledby="alta-titulo" x-data x-trap.noscroll="true" x-on:keydown.escape.window="$wire.set('showDischarge', false)">
            <form wire:submit="discharge" class="panel w-full max-w-lg rounded-b-none p-6 sm:rounded-b-3xl sm:p-8" x-on:click.outside="$wire.set('showDischarge', false)">
                <h2 id="alta-titulo" class="text-2xl font-extrabold text-ink">Cerrar hospitalización</h2>
                <p class="mt-1 text-muted">{{ $patient->name }} · {{ $stay->bed_label ?? 'sin cama' }}. Las dosis pendientes del kárdex se cancelarán automáticamente.</p>

                <fieldset class="mt-6">
                    <legend class="text-sm font-semibold text-ink">Resultado</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach ($dischargeOptions as $option)
                            <label @class(['flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border px-4 font-semibold', 'border-ink bg-sunken text-ink' => $outcome === $option->value, 'border-edge text-ink' => $outcome !== $option->value])>
                                <input type="radio" wire:model.live="outcome" value="{{ $option->value }}" class="size-5 accent-[var(--pp-accent)]">
                                {{ $option->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('outcome') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </fieldset>

                <label class="mt-5 block">
                    <span class="text-sm font-semibold text-ink">Observaciones de egreso <span class="font-normal text-muted">(opcional)</span></span>
                    <textarea wire:model="dischargeNotes" rows="3" maxlength="2000" class="field mt-1.5" placeholder="Indicaciones al propietario, control, medicación ambulatoria…"></textarea>
                    @error('dischargeNotes') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </label>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="$set('showDischarge', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">Confirmar cierre</button>
                </div>
            </form>
        </div>
    @endif
</div>
