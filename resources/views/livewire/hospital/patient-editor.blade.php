@php
    $invalid = fn (string $name) => $errors->has("form.$name") ? '!border-late' : '';
@endphp
<div class="mx-auto max-w-3xl">
    <nav class="text-sm text-muted" aria-label="Ruta">
        <a href="{{ route('hospital.patients.index') }}" class="link-quiet">Pacientes</a>
        <span aria-hidden="true"> / </span>
        @if ($isNew)
            Nuevo paciente
        @else
            <a href="{{ route('hospital.patients.show', $form->patient) }}" class="link-quiet">{{ $form->patient->name }}</a>
            <span aria-hidden="true"> / </span> Editar
        @endif
    </nav>

    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-ink">{{ $isNew ? 'Nuevo paciente' : 'Editar paciente' }}</h1>
    <p class="mt-1 text-muted">Los datos del propietario son datos personales (Ley 1581 de 2012): registre solo lo necesario para la atención.</p>

    <form wire:submit="save" class="panel mt-6 p-6 sm:p-8" novalidate>
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-late bg-late-soft px-4 py-3 text-sm font-semibold text-late" role="alert">
                Revise los campos marcados.
            </div>
        @endif

        <fieldset>
            <legend class="text-xl font-bold text-ink">El paciente</legend>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-semibold text-ink">Nombre</label>
                    <input id="name" type="text" wire:model="form.name" required maxlength="100" autofocus class="field mt-1.5 {{ $invalid('name') }}" @error('form.name') aria-invalid="true" @enderror>
                    @error('form.name') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="species" class="block text-sm font-semibold text-ink">Especie</label>
                    <input id="species" type="text" wire:model="form.species" list="species-list" required maxlength="32" class="field mt-1.5 {{ $invalid('species') }}" placeholder="Canino, Felino…">
                    <datalist id="species-list">
                        @foreach ($speciesOptions as $option)<option value="{{ $option }}">@endforeach
                    </datalist>
                    @error('form.species') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="breed" class="block text-sm font-semibold text-ink">Raza <span class="font-normal text-muted">(opcional)</span></label>
                    <input id="breed" type="text" wire:model="form.breed" maxlength="100" class="field mt-1.5 {{ $invalid('breed') }}" placeholder="Criollo">
                    @error('form.breed') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <span class="block text-sm font-semibold text-ink">Sexo</span>
                    <div class="mt-1.5 flex rounded-xl border border-edge bg-surface p-1" role="radiogroup" aria-label="Sexo">
                        @foreach ([['', 'Sin dato'], ['M', 'Macho'], ['H', 'Hembra']] as [$value, $label])
                            <label @class(['flex min-h-10 flex-1 cursor-pointer items-center justify-center rounded-lg text-sm font-bold', 'bg-accent text-on-accent' => $form->sex === $value, 'text-muted hover:text-ink' => $form->sex !== $value])>
                                <input type="radio" wire:model.live="form.sex" value="{{ $value }}" class="sr-only">{{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('form.sex') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="birth_date" class="block text-sm font-semibold text-ink">Fecha de nacimiento <span class="font-normal text-muted">(opcional)</span></label>
                    <input id="birth_date" type="date" wire:model="form.birth_date" max="{{ now()->toDateString() }}" class="field mt-1.5 {{ $invalid('birth_date') }}">
                    @error('form.birth_date') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="microchip" class="block text-sm font-semibold text-ink">Microchip <span class="font-normal text-muted">(opcional)</span></label>
                    <input id="microchip" type="text" wire:model="form.microchip" maxlength="32" inputmode="numeric" class="field pp-num mt-1.5 {{ $invalid('microchip') }}" placeholder="900182000123456">
                    @error('form.microchip') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        <fieldset class="mt-8 border-t border-line pt-8">
            <legend class="text-xl font-bold text-ink">El propietario</legend>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="owner_name" class="block text-sm font-semibold text-ink">Nombre completo</label>
                    <input id="owner_name" type="text" wire:model="form.owner_name" required maxlength="120" autocomplete="off" class="field mt-1.5 {{ $invalid('owner_name') }}">
                    @error('form.owner_name') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="owner_document" class="block text-sm font-semibold text-ink">Documento <span class="font-normal text-muted">(opcional)</span></label>
                    <input id="owner_document" type="text" wire:model="form.owner_document" maxlength="32" inputmode="numeric" class="field pp-num mt-1.5 {{ $invalid('owner_document') }}" placeholder="C.C. 1020304050">
                    @error('form.owner_document') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="owner_phone" class="block text-sm font-semibold text-ink">Teléfono <span class="font-normal text-muted">(opcional)</span></label>
                    <input id="owner_phone" type="tel" wire:model="form.owner_phone" maxlength="32" inputmode="tel" class="field pp-num mt-1.5 {{ $invalid('owner_phone') }}" placeholder="300 123 4567">
                    @error('form.owner_phone') <p class="mt-2 text-sm font-semibold text-late" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-line pt-6 sm:flex-row sm:justify-end">
            <a href="{{ $isNew ? route('hospital.patients.index') : route('hospital.patients.show', $form->patient) }}" class="btn-secondary">Cancelar</a>
            @if ($isNew)
                <button type="submit" wire:click="$set('next', 'ingreso')" class="btn-secondary" wire:loading.attr="disabled">Guardar e ingresar a hospitalización</button>
                <button type="submit" wire:click="$set('next', '')" class="btn-primary" wire:loading.attr="disabled">Guardar paciente</button>
            @else
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">Guardar cambios</button>
            @endif
        </div>
    </form>
</div>
