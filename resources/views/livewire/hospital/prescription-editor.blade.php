@php
    $stay = $this->stay;
    $drug = $this->drug;
    $plan = $this->plan;
    $perKg = $dose_mode === 'per_kg';
    $weight = str_replace('.', ',', rtrim(rtrim((string) $stay->current_weight_kg, '0'), '.'));
    $invalid = fn (string $name) => $errors->has($name) ? '!border-late' : '';
@endphp
<div class="mx-auto max-w-6xl">
    <nav class="text-sm text-muted" aria-label="Ruta">
        <a href="{{ route('hospital.patients.index') }}" class="link-quiet">Pacientes</a>
        <span aria-hidden="true"> / </span>
        <a href="{{ route('hospital.patients.show', $stay->patient_id) }}" class="link-quiet">{{ $stay->patient->name }}</a>
        <span aria-hidden="true"> / </span> Nueva prescripción
    </nav>

    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-ink">Nueva prescripción</h1>
    <p class="mt-1 text-lg text-muted">
        {{ $stay->patient->name }} · {{ $stay->patient->species }} ·
        <span class="pp-num">{{ $weight }} kg</span>
        @if ($stay->bed_label) · {{ $stay->bed_label }}@endif
        · {{ $stay->triage_level->label() }}
    </p>

    <form wire:submit="save" class="mt-6 grid items-start gap-6 lg:grid-cols-[1.25fr_0.75fr]" novalidate>
        <div class="panel p-6 sm:p-8">
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-late bg-late-soft px-4 py-3 text-sm font-semibold text-late" role="alert">Revise los campos marcados.</div>
            @endif

            {{-- Drug --}}
            <div>
                <label for="drug" class="block text-sm font-semibold text-ink">Medicamento</label>
                <select id="drug" wire:model.live="drug_id" class="field mt-1.5 {{ $invalid('drug_id') }}" autofocus>
                    <option value="">Seleccione…</option>
                    @foreach ($this->drugs as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} · {{ $d->presentation_unit }} {{ $d->packageVolume()->display() }} {{ $d->fraction_unit }}{{ $d->is_controlled ? ' · control especial' : '' }}</option>
                    @endforeach
                </select>
                @error('drug_id') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                @if ($this->drugs->isEmpty())
                    <p class="mt-2 text-sm text-muted">No hay medicamentos activos. <a href="{{ route('hospital.drugs.create') }}" class="link-quiet">Cree uno en el catálogo</a>.</p>
                @elseif ($drug)
                    <p class="mt-2 text-sm text-muted">
                        @if ($drug->concentration_amount !== null)
                            Concentración {{ App\Support\Pharmacy\Quantity::of($drug->concentration_amount)->display() }} {{ $drug->concentration_unit }}/{{ $drug->fraction_unit }} ·
                        @endif
                        Disponible: <span class="pp-num">{{ $drug->stock_packages }}</span> sellados + <span class="pp-num">{{ $drug->openBalance()->display() }}</span> {{ $drug->fraction_unit }} abierto
                    </p>
                @endif
            </div>

            {{-- Dose --}}
            <fieldset class="mt-6">
                <legend class="text-sm font-semibold text-ink">Dosis</legend>
                <div class="mt-1.5 flex rounded-xl border border-edge bg-surface p-1" role="radiogroup" aria-label="Modo de dosis">
                    @foreach (App\Enums\DoseMode::cases() as $mode)
                        @php($disabled = $mode === App\Enums\DoseMode::PerKg && $drug && $drug->concentration_amount === null)
                        <label @class([
                            'flex min-h-10 flex-1 items-center justify-center rounded-lg text-sm font-bold',
                            'bg-accent text-on-accent' => $dose_mode === $mode->value,
                            'text-muted hover:text-ink cursor-pointer' => $dose_mode !== $mode->value && ! $disabled,
                            'cursor-not-allowed text-muted/50' => $disabled,
                        ])>
                            <input type="radio" wire:model.live="dose_mode" value="{{ $mode->value }}" class="sr-only" @disabled($disabled)>
                            {{ $mode === App\Enums\DoseMode::PerKg ? 'Por peso ('.($drug?->concentration_unit ?? 'mg').'/kg)' : 'Dosis fija ('.($drug?->fraction_unit ?? 'unidad').')' }}
                        </label>
                    @endforeach
                </div>
                @error('dose_mode') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror

                <div class="mt-3">
                    @if ($perKg)
                        <div class="flex items-center gap-2">
                            <input type="text" inputmode="decimal" wire:model.live.debounce.300ms="dose_per_kg" aria-label="Dosis por kilogramo" class="field pp-num flex-1 {{ $invalid('dose_per_kg') }}" placeholder="0,2">
                            <span class="whitespace-nowrap text-muted">{{ $drug?->concentration_unit ?? 'mg' }}/kg × {{ $weight }} kg</span>
                        </div>
                        @error('dose_per_kg') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                    @else
                        <div class="flex items-center gap-2">
                            <input type="text" inputmode="decimal" wire:model.live.debounce.300ms="dose_amount" aria-label="Cantidad por dosis" class="field pp-num flex-1 {{ $invalid('dose_amount') }}" placeholder="0,5">
                            <span class="whitespace-nowrap text-muted">{{ $drug?->fraction_unit ?? 'unidades' }} por dosis</span>
                        </div>
                        @error('dose_amount') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                    @endif
                </div>
            </fieldset>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="route" class="block text-sm font-semibold text-ink">Vía</label>
                    <select id="route" wire:model.live="route" class="field mt-1.5 {{ $invalid('route') }}">
                        <option value="">Seleccione…</option>
                        @foreach ($routes as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }} ({{ $r->value }})</option>
                        @endforeach
                    </select>
                    @error('route') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="frequency" class="block text-sm font-semibold text-ink">Frecuencia</label>
                    <select id="frequency" wire:model.live="frequency_hours" class="field mt-1.5 {{ $invalid('frequency_hours') }}">
                        @foreach ($frequencies as $f)
                            <option value="{{ $f }}">{{ $f === 0 ? 'Dosis única' : 'Cada '.$f.' h' }}</option>
                        @endforeach
                    </select>
                    @error('frequency_hours') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="starts_at" class="block text-sm font-semibold text-ink">Primera dosis</label>
                    <input id="starts_at" type="datetime-local" wire:model.live="starts_at" class="field pp-num mt-1.5 {{ $invalid('starts_at') }}">
                    @error('starts_at') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
                @if ($frequency_hours !== '0')
                    <div>
                        <label for="days" class="block text-sm font-semibold text-ink">Duración</label>
                        <div class="mt-1.5 flex items-center gap-2">
                            <input id="days" type="number" min="1" max="30" wire:model.live.debounce.300ms="duration_days" class="field pp-num flex-1 {{ $invalid('duration_days') }}">
                            <span class="text-muted">días</span>
                        </div>
                        @error('duration_days') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div class="sm:col-span-2">
                    <label for="instructions" class="block text-sm font-semibold text-ink">Indicaciones <span class="font-normal text-muted">(opcional)</span></label>
                    <textarea id="instructions" wire:model="instructions" rows="2" maxlength="1000" class="field mt-1.5 {{ $invalid('instructions') }}" placeholder="Diluir en 10 mL de SSN y pasar en 5 minutos"></textarea>
                    @error('instructions') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Live preview: exactly what will be written to the kárdex. --}}
        <aside class="panel p-6 lg:sticky lg:top-24" aria-live="polite" aria-labelledby="resumen">
            <h2 id="resumen" class="text-xl font-bold text-ink">Resumen</h2>
            @if ($plan)
                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-sm font-semibold text-muted">Cada dosis</dt>
                        <dd class="pp-num text-3xl font-extrabold text-ink">{{ $plan->dose->display() }} <span class="text-lg font-bold text-muted">{{ $drug->fraction_unit }}</span></dd>
                        @if ($perKg)
                            <dd class="pp-num text-sm text-muted">{{ str_replace('.', ',', $plan->dosePerKg) }} {{ $drug->concentration_unit }}/kg × {{ $weight }} kg ÷ {{ App\Support\Pharmacy\Quantity::of($drug->concentration_amount)->display() }} {{ $drug->concentration_unit }}/{{ $drug->fraction_unit }}</dd>
                        @endif
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-muted">Dosis programadas</dt>
                        <dd class="pp-num text-lg font-bold text-ink">{{ $plan->doseCount() }}</dd>
                        <dd class="pp-num text-sm text-muted">
                            {{ $plan->firstTime()->copy()->setTimezone($timezone)->isoFormat('ddd D MMM, HH:mm') }}
                            @if ($plan->doseCount() > 1)
                                → {{ $plan->lastTime()->copy()->setTimezone($timezone)->isoFormat('ddd D MMM, HH:mm') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-semibold text-muted">Total del tratamiento</dt>
                        <dd class="pp-num text-lg font-bold text-ink">{{ $plan->totalRequired()->display() }} {{ $drug->fraction_unit }}</dd>
                    </div>
                </dl>
                @unless ($plan->stockCovers())
                    <p class="mt-4 flex items-start gap-2 rounded-xl bg-soon-soft px-4 py-3 text-sm font-semibold text-soon" role="status">
                        <svg viewBox="0 0 24 24" class="mt-0.5 size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 8v5M12 17h.01"/></svg>
                        El inventario actual ({{ $drug->totalAvailable()->display() }} {{ $drug->fraction_unit }}) no alcanza para todo el tratamiento. Puede prescribir igual; registre una entrada antes de que se agote.
                    </p>
                @endunless
                @if ($drug->is_controlled)
                    <p class="mt-4 rounded-xl bg-sunken px-4 py-3 text-sm text-ink">Control especial: cada administración exigirá una observación.</p>
                @endif
            @else
                <p class="mt-4 text-muted">Complete el medicamento, la dosis, la frecuencia y la duración para ver el cálculo y los horarios.</p>
            @endif

            <div class="mt-6 flex flex-col gap-3 border-t border-line pt-5">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">Prescribir</button>
                <a href="{{ route('hospital.patients.show', $stay->patient_id) }}" class="btn-secondary">Cancelar</a>
            </div>
        </aside>
    </form>
</div>
