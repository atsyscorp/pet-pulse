@php
    $invalid = fn (string $name) => $errors->has("form.$name") ? '!border-late' : '';
    $err = fn (string $name) => $errors->first("form.$name");
@endphp
<div class="mx-auto max-w-3xl">
    <nav class="text-sm text-muted" aria-label="Ruta">
        <a href="{{ route('hospital.drugs.index') }}" class="link-quiet">Medicamentos</a>
        <span aria-hidden="true"> / </span>
        @if ($isNew)
            Nuevo
        @else
            <a href="{{ route('hospital.drugs.show', $form->drug) }}" class="link-quiet">{{ $form->drug->name }}</a>
            <span aria-hidden="true"> / </span> Editar
        @endif
    </nav>

    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-ink">{{ $isNew ? 'Nuevo medicamento' : 'Editar medicamento' }}</h1>

    <form wire:submit="save" class="panel mt-6 p-6 sm:p-8" novalidate>
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-late bg-late-soft px-4 py-3 text-sm font-semibold text-late" role="alert">Revise los campos marcados.</div>
        @endif

        <fieldset>
            <legend class="text-xl font-bold text-ink">Identificación</legend>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-semibold text-ink">Nombre comercial o de uso</label>
                    <input id="name" type="text" wire:model="form.name" maxlength="150" required autofocus class="field mt-1.5 {{ $invalid('name') }}" placeholder="Ketamina 50 mg/mL">
                    @if ($err('name')) <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $err('name') }}</p> @endif
                </div>
                <div>
                    <label for="active_ingredient" class="block text-sm font-semibold text-ink">Principio activo <span class="font-normal text-muted">(opcional)</span></label>
                    <input id="active_ingredient" type="text" wire:model="form.active_ingredient" maxlength="150" class="field mt-1.5 {{ $invalid('active_ingredient') }}" placeholder="Ketamina">
                    @if ($err('active_ingredient')) <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $err('active_ingredient') }}</p> @endif
                </div>
                <div>
                    <label for="sanitary_registry" class="block text-sm font-semibold text-ink">Registro ICA / INVIMA <span class="font-normal text-muted">(opcional)</span></label>
                    <input id="sanitary_registry" type="text" wire:model="form.sanitary_registry" maxlength="64" class="field pp-num mt-1.5 {{ $invalid('sanitary_registry') }}">
                    @if ($err('sanitary_registry')) <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $err('sanitary_registry') }}</p> @endif
                </div>
            </div>
        </fieldset>

        <fieldset class="mt-8 border-t border-line pt-8">
            <legend class="text-xl font-bold text-ink">Presentación y dosificación</legend>
            <p class="mt-1 text-sm text-muted">Cómo llega el producto (el empaque sellado) y en qué unidad se administra cada dosis.</p>

            @if ($unitsLocked)
                <p class="mt-4 rounded-xl border border-line bg-sunken px-4 py-3 text-sm text-ink">
                    El contenido por empaque y la unidad de dosificación no se pueden cambiar porque el medicamento ya tiene movimientos de inventario. Si cambió la presentación, cree un medicamento nuevo e inactive este.
                </p>
            @endif

            <div class="mt-5 grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="presentation_unit" class="block text-sm font-semibold text-ink">Empaque</label>
                    <input id="presentation_unit" type="text" wire:model="form.presentation_unit" list="presentation-list" maxlength="32" required class="field mt-1.5 {{ $invalid('presentation_unit') }}" placeholder="vial">
                    <datalist id="presentation-list">@foreach ($presentationUnits as $u)<option value="{{ $u }}">@endforeach</datalist>
                    @if ($err('presentation_unit')) <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $err('presentation_unit') }}</p> @endif
                </div>
                <div>
                    <label for="volume" class="block text-sm font-semibold text-ink">Contenido por empaque</label>
                    <input id="volume" type="text" inputmode="decimal" wire:model="form.volume_per_presentation" @disabled($unitsLocked) required class="field pp-num mt-1.5 disabled:bg-sunken disabled:text-muted {{ $invalid('volume_per_presentation') }}" placeholder="10">
                    @if ($err('volume_per_presentation')) <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $err('volume_per_presentation') }}</p> @endif
                </div>
                <div>
                    <label for="fraction_unit" class="block text-sm font-semibold text-ink">Unidad de dosificación</label>
                    <input id="fraction_unit" type="text" wire:model="form.fraction_unit" list="fraction-list" maxlength="16" @disabled($unitsLocked) required class="field mt-1.5 disabled:bg-sunken disabled:text-muted {{ $invalid('fraction_unit') }}" placeholder="mL">
                    <datalist id="fraction-list">@foreach ($fractionUnits as $u)<option value="{{ $u }}">@endforeach</datalist>
                    @if ($err('fraction_unit')) <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $err('fraction_unit') }}</p> @endif
                </div>
                <div class="sm:col-span-2">
                    <label for="concentration" class="block text-sm font-semibold text-ink">Concentración <span class="font-normal text-muted">(necesaria para dosificar por kg)</span></label>
                    <div class="mt-1.5 flex gap-2">
                        <input id="concentration" type="text" inputmode="decimal" wire:model="form.concentration_amount" class="field pp-num flex-1 {{ $invalid('concentration_amount') }}" placeholder="50">
                        <select wire:model="form.concentration_unit" aria-label="Unidad de concentración" class="field !w-28 {{ $invalid('concentration_unit') }}">
                            @foreach ($concentrationUnits as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach
                        </select>
                        <span class="flex min-h-12 items-center whitespace-nowrap text-muted">por {{ $form->fraction_unit ?: 'unidad' }}</span>
                    </div>
                    @if ($err('concentration_amount')) <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $err('concentration_amount') }}</p> @endif
                </div>
                <div>
                    <label for="stability" class="block text-sm font-semibold text-ink">Estable tras abrir <span class="font-normal text-muted">(horas)</span></label>
                    <input id="stability" type="number" min="1" max="8760" wire:model="form.stability_hours_after_opening" class="field pp-num mt-1.5 {{ $invalid('stability_hours_after_opening') }}" placeholder="Sin límite">
                    @if ($err('stability_hours_after_opening')) <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $err('stability_hours_after_opening') }}</p> @endif
                </div>
            </div>
        </fieldset>

        <fieldset class="mt-8 border-t border-line pt-8">
            <legend class="text-xl font-bold text-ink">Inventario y control</legend>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                @if ($isNew)
                    <div>
                        <label for="initial" class="block text-sm font-semibold text-ink">Existencias iniciales <span class="font-normal text-muted">(empaques sellados)</span></label>
                        <input id="initial" type="number" min="0" max="100000" wire:model="form.initial_packages" class="field pp-num mt-1.5 {{ $invalid('initial_packages') }}">
                        <p class="mt-1 text-xs text-muted">Queda registrado como una entrada en el libro de movimientos.</p>
                        @if ($err('initial_packages')) <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $err('initial_packages') }}</p> @endif
                    </div>
                @endif
                <div>
                    <label for="reorder" class="block text-sm font-semibold text-ink">Avisar stock bajo con</label>
                    <div class="mt-1.5 flex items-center gap-2">
                        <input id="reorder" type="number" min="0" max="100000" wire:model="form.reorder_level_packages" class="field pp-num flex-1 {{ $invalid('reorder_level_packages') }}">
                        <span class="whitespace-nowrap text-muted">empaques o menos</span>
                    </div>
                    @if ($err('reorder_level_packages')) <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $err('reorder_level_packages') }}</p> @endif
                </div>
                <label class="flex min-h-12 cursor-pointer items-start gap-3 rounded-xl border border-edge p-4 sm:col-span-2">
                    <input type="checkbox" wire:model="form.is_controlled" class="mt-0.5 size-6 shrink-0 rounded-md accent-[var(--pp-accent)]">
                    <span>
                        <span class="block font-semibold text-ink">Medicamento de control especial</span>
                        <span class="block text-sm text-muted">Exige observación en cada administración (Res. 1478 de 2006, FNE).</span>
                    </span>
                </label>
                @unless ($isNew)
                    <label class="flex min-h-12 cursor-pointer items-start gap-3 rounded-xl border border-edge p-4 sm:col-span-2">
                        <input type="checkbox" wire:model="form.is_active" class="mt-0.5 size-6 shrink-0 rounded-md accent-[var(--pp-accent)]">
                        <span>
                            <span class="block font-semibold text-ink">Activo</span>
                            <span class="block text-sm text-muted">Un medicamento inactivo no se puede prescribir ni administrar. Su historial se conserva.</span>
                        </span>
                    </label>
                @endunless
            </div>
        </fieldset>

        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-line pt-6 sm:flex-row sm:justify-end">
            <a href="{{ $isNew ? route('hospital.drugs.index') : route('hospital.drugs.show', $form->drug) }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">{{ $isNew ? 'Crear medicamento' : 'Guardar cambios' }}</button>
        </div>
    </form>
</div>
