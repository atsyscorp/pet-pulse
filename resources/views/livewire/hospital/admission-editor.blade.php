@php
    $invalid = fn (string $name) => $errors->has("form.$name") ? '!border-late' : '';
@endphp
<div class="mx-auto max-w-3xl">
    <nav class="text-sm text-muted" aria-label="Ruta">
        <a href="{{ route('hospital.patients.index') }}" class="link-quiet">Pacientes</a>
        <span aria-hidden="true"> / </span>
        <a href="{{ route('hospital.patients.show', $patient) }}" class="link-quiet">{{ $patient->name }}</a>
        <span aria-hidden="true"> / </span> Nuevo ingreso
    </nav>

    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-ink">Ingreso a hospitalización</h1>
    <p class="mt-1 text-lg text-muted">
        {{ $patient->name }} · {{ $patient->species }}@if ($patient->breed) · {{ $patient->breed }}@endif · Propietario: {{ $patient->owner_name }}
    </p>

    @if ($alreadyAdmitted)
        <div class="mt-6 rounded-2xl border border-soon bg-soon-soft px-5 py-4 text-soon" role="alert">
            <p class="font-bold">Este paciente ya está hospitalizado.</p>
            <p class="mt-1 text-sm">Ciérrele la hospitalización actual desde su ficha antes de registrar un nuevo ingreso.</p>
            <a href="{{ route('hospital.patients.show', $patient) }}" class="link-quiet mt-3 inline-block text-sm">Ir a la ficha</a>
        </div>
    @else
        <form wire:submit="save" class="panel mt-6 p-6 sm:p-8" novalidate>
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-late bg-late-soft px-4 py-3 text-sm font-semibold text-late" role="alert">
                    Revise los campos marcados.
                </div>
            @endif

            <fieldset>
                <legend class="text-xl font-bold text-ink">Estado al ingreso</legend>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="weight" class="block text-sm font-semibold text-ink">Peso actual (kg)</label>
                        <input id="weight" type="text" inputmode="decimal" wire:model="form.current_weight_kg" required autofocus class="field pp-num mt-1.5 {{ $invalid('current_weight_kg') }}" placeholder="4,250">
                        <p class="mt-1 text-xs text-muted">Se usa para calcular las dosis por kilogramo.</p>
                        @error('form.current_weight_kg') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="bed" class="block text-sm font-semibold text-ink">Cama <span class="font-normal text-muted">(opcional)</span></label>
                        <input id="bed" type="text" wire:model="form.bed_label" maxlength="32" class="field mt-1.5 {{ $invalid('bed_label') }}" placeholder="UCI-1">
                        @error('form.bed_label') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-sm font-semibold text-ink">Nivel de triage</span>
                        <div class="mt-1.5 grid gap-2 sm:grid-cols-3" role="radiogroup" aria-label="Nivel de triage">
                            @foreach ($triageLevels as $level)
                                <label @class(['flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border px-4 font-bold', 'border-ink bg-sunken text-ink' => $form->triage_level === $level->value, 'border-edge text-ink' => $form->triage_level !== $level->value])>
                                    <input type="radio" wire:model.live="form.triage_level" value="{{ $level->value }}" class="sr-only">
                                    <span class="size-3 rounded-full ring-1 ring-inset {{ $level->badgeClasses() }}" aria-hidden="true"></span>
                                    {{ $level->label() }}
                                </label>
                            @endforeach
                        </div>
                        @error('form.triage_level') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="vet" class="block text-sm font-semibold text-ink">Veterinario tratante</label>
                        <select id="vet" wire:model="form.attending_vet_id" class="field mt-1.5 {{ $invalid('attending_vet_id') }}">
                            <option value="">Sin asignar</option>
                            @foreach ($vets as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('form.attending_vet_id') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="admitted_at" class="block text-sm font-semibold text-ink">Fecha y hora de ingreso</label>
                        <input id="admitted_at" type="datetime-local" wire:model="form.admitted_at" required class="field pp-num mt-1.5 {{ $invalid('admitted_at') }}">
                        @error('form.admitted_at') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="mt-8 border-t border-line pt-8">
                <legend class="text-xl font-bold text-ink">Motivo</legend>
                <div class="mt-5 grid gap-5">
                    <div>
                        <label for="reason" class="block text-sm font-semibold text-ink">Motivo de ingreso</label>
                        <textarea id="reason" wire:model="form.admission_reason" rows="3" required maxlength="2000" class="field mt-1.5 {{ $invalid('admission_reason') }}" placeholder="Vómito y diarrea con sangre de 48 horas de evolución, deshidratación del 8 %…"></textarea>
                        @error('form.admission_reason') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="diagnosis" class="block text-sm font-semibold text-ink">Diagnóstico presuntivo <span class="font-normal text-muted">(opcional)</span></label>
                        <textarea id="diagnosis" wire:model="form.diagnosis" rows="2" maxlength="2000" class="field mt-1.5 {{ $invalid('diagnosis') }}" placeholder="Gastroenteritis hemorrágica"></textarea>
                        @error('form.diagnosis') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <div class="mt-8 flex flex-col-reverse gap-3 border-t border-line pt-6 sm:flex-row sm:justify-end">
                <a href="{{ route('hospital.patients.show', $patient) }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">Registrar ingreso</button>
            </div>
        </form>
    @endif
</div>
