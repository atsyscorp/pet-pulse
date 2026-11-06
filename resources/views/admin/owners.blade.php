<x-layouts.admin title="Propietarios">
    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Propietarios</h1>
    <p class="mt-1 text-muted">Personas registradas en la app de PetPulse. {{ number_format($owners->total(), 0, ',', '.') }} en total.</p>

    @include('admin.partials.search', ['placeholder' => 'Nombre, correo o celular'])

    <div class="panel mt-6 overflow-hidden">
        @forelse ($owners as $owner)
            <div class="flex flex-col gap-1 border-b border-line px-4 py-3 last:border-b-0 sm:flex-row sm:items-center sm:gap-4 sm:px-5">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold text-ink">{{ $owner->name }}</p>
                    <p class="truncate text-sm text-muted">{{ $owner->email }}@if ($owner->phone) · {{ $owner->phone }}@endif</p>
                </div>
                <p class="text-sm text-muted sm:w-48 sm:text-right">
                    {{ trans_choice(':count mascota|:count mascotas', $owner->pets_count) }}
                    @if ($owner->clinics_count > 0) · {{ trans_choice(':count clínica|:count clínicas', $owner->clinics_count) }}@endif
                </p>
                <p class="text-sm text-muted sm:w-40 sm:text-right">Registro: {{ $owner->created_at->setTimezone('America/Bogota')->translatedFormat('j M Y') }}</p>
            </div>
        @empty
            @include('admin.partials.empty', ['empty' => 'Aún no se ha registrado ningún propietario en la app.'])
        @endforelse
    </div>

    <div class="mt-4">{{ $owners->links() }}</div>
</x-layouts.admin>
