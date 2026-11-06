<x-layouts.admin title="Personal de clínicas">
    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Personal de clínicas</h1>
    <p class="mt-1 text-muted">Cuentas del equipo de cada clínica. {{ number_format($users->total(), 0, ',', '.') }} en total.</p>

    @include('admin.partials.search', ['placeholder' => 'Nombre, correo o clínica'])

    <div class="panel mt-6 overflow-hidden">
        @forelse ($users as $user)
            <div class="flex flex-col gap-1 border-b border-line px-4 py-3 last:border-b-0 sm:flex-row sm:items-center sm:gap-4 sm:px-5">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-lg font-bold text-ink">{{ $user->name }}</p>
                    <p class="truncate text-sm text-muted">{{ $user->email }}</p>
                </div>
                <p class="text-sm text-muted sm:w-64 sm:text-right">{{ $user->role->label() }} · {{ $user->clinic?->name }}</p>
                <p class="text-sm text-muted sm:w-40 sm:text-right">Alta: {{ $user->created_at->setTimezone('America/Bogota')->translatedFormat('j M Y') }}</p>
            </div>
        @empty
            @include('admin.partials.empty', ['empty' => 'Aún no hay personal registrado.'])
        @endforelse
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.admin>
