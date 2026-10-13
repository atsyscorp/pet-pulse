<x-layouts.admin title="Clínicas">
    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Clínicas</h1>
    <p class="mt-1 text-muted">Clínicas veterinarias con cuenta en PetPulse. {{ number_format($clinics->total(), 0, ',', '.') }} en total.</p>

    @include('admin.partials.search', ['placeholder' => 'Nombre o NIT'])

    <div class="panel mt-6 overflow-hidden">
        @forelse ($clinics as $clinic)
            <div class="flex flex-col gap-1 border-b border-line px-4 py-3 last:border-b-0 sm:flex-row sm:items-center sm:gap-4 sm:px-5">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold text-ink">
                        {{ $clinic->name }}
                        @unless ($clinic->is_active)
                            <span class="ml-1 rounded-full px-2.5 py-0.5 align-middle text-xs font-bold text-muted ring-1 ring-inset ring-line">Inactiva</span>
                        @endunless
                    </p>
                    <p class="truncate text-sm text-muted">NIT {{ $clinic->tax_id ?? 'sin registrar' }} · {{ $clinic->country_code }}</p>
                </div>
                <p class="text-sm text-muted sm:text-right">
                    {{ trans_choice(':count usuario|:count usuarios', $clinic->users_count) }}
                    · {{ trans_choice(':count paciente|:count pacientes', $clinic->patients_count) }}
                    · {{ trans_choice(':count mascota en la app|:count mascotas en la app', $clinic->linked_pets_count) }}
                </p>
                <p class="text-sm text-muted sm:w-40 sm:text-right">Alta: {{ $clinic->created_at->setTimezone('America/Bogota')->translatedFormat('j M Y') }}</p>
            </div>
        @empty
            @include('admin.partials.empty', ['empty' => 'Aún no hay clínicas registradas.'])
        @endforelse
    </div>

    <div class="mt-4">{{ $clinics->links() }}</div>
</x-layouts.admin>
