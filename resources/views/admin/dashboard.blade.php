<x-layouts.admin title="Resumen">
    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Resumen de la plataforma</h1>
    <p class="mt-1 text-muted">Quién se ha registrado en PetPulse y quién está pidiendo acceso.</p>

    @php($cards = [
        ['label' => 'Propietarios registrados', 'value' => $stats['owners'], 'note' => $stats['owners_week'].' en los últimos 7 días', 'route' => 'admin.owners'],
        ['label' => 'Mascotas vinculadas a la app', 'value' => $stats['linked_pets'], 'note' => 'Vínculos activos', 'route' => 'admin.owners'],
        ['label' => 'Clínicas activas', 'value' => $stats['clinics'], 'note' => 'Con acceso al kárdex', 'route' => 'admin.clinics'],
        ['label' => 'Personal de clínicas', 'value' => $stats['staff'], 'note' => 'Cuentas de usuario', 'route' => 'admin.staff'],
        ['label' => 'Solicitudes pendientes', 'value' => $stats['pending_requests'], 'note' => 'Clínicas que pidieron acceso', 'route' => 'admin.access-requests'],
    ])
    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
        @foreach ($cards as $card)
            <a href="{{ route($card['route']) }}" class="panel block p-4 transition-colors hover:bg-sunken sm:p-5">
                <p class="text-sm font-semibold text-muted">{{ $card['label'] }}</p>
                <p class="pp-num mt-2 text-4xl font-extrabold tracking-tight text-ink">{{ number_format($card['value'], 0, ',', '.') }}</p>
                <p class="mt-1 text-sm text-muted">{{ $card['note'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-lg font-bold text-ink">Últimos propietarios registrados</h2>
                <a href="{{ route('admin.owners') }}" class="link-quiet shrink-0 text-sm">Ver todos</a>
            </div>
            @forelse ($recentOwners as $owner)
                <div class="flex items-center gap-4 border-b border-line px-5 py-3 last:border-b-0">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold text-ink">{{ $owner->name }}</p>
                        <p class="truncate text-sm text-muted">{{ $owner->email }}</p>
                    </div>
                    <p class="shrink-0 text-right text-sm text-muted">
                        {{ $owner->created_at->setTimezone('America/Bogota')->translatedFormat('j M Y') }}<br>
                        {{ trans_choice(':count mascota|:count mascotas', $owner->pets_count) }}
                    </p>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-muted">Aún no se ha registrado ningún propietario en la app.</p>
            @endforelse
        </section>

        <section class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-lg font-bold text-ink">Últimas solicitudes de acceso</h2>
                <a href="{{ route('admin.access-requests') }}" class="link-quiet shrink-0 text-sm">Ver todas</a>
            </div>
            @forelse ($recentRequests as $req)
                <div class="flex items-center gap-4 border-b border-line px-5 py-3 last:border-b-0">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold text-ink">{{ $req->clinic_name }}</p>
                        <p class="truncate text-sm text-muted">{{ $req->city }} · {{ $req->contact_name }}</p>
                    </div>
                    <p class="shrink-0 text-sm text-muted">{{ $req->created_at->setTimezone('America/Bogota')->translatedFormat('j M Y') }}</p>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-muted">Ninguna clínica ha solicitado acceso todavía.</p>
            @endforelse
        </section>
    </div>
</x-layouts.admin>
