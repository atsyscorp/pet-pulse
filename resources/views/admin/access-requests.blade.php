<x-layouts.admin title="Solicitudes de acceso">
    <h1 class="text-3xl font-extrabold tracking-tight text-ink">Solicitudes de acceso</h1>
    <p class="mt-1 text-muted">Clínicas que pidieron usar PetPulse desde la página pública. {{ number_format($requests->total(), 0, ',', '.') }} en total.</p>

    @include('admin.partials.search', ['placeholder' => 'Clínica, contacto, correo o ciudad'])

    <div class="mt-6 space-y-4">
        @forelse ($requests as $req)
            <article class="panel p-5">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-ink">{{ $req->clinic_name }}</h2>
                        <p class="text-sm text-muted">
                            {{ $req->city }}@if ($req->tax_id) · NIT {{ $req->tax_id }}@endif
                            @if ($req->beds) · {{ trans_choice(':count jaula o cama|:count jaulas o camas', $req->beds) }}@endif
                        </p>
                    </div>
                    <p class="shrink-0 text-sm text-muted">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset ring-line">{{ $req->status === 'pending' ? 'Pendiente' : ucfirst($req->status) }}</span>
                        {{ $req->created_at->setTimezone('America/Bogota')->translatedFormat('j M Y, g:i a') }}
                    </p>
                </div>
                <p class="mt-3 text-sm text-ink">
                    {{ $req->contact_name }} ·
                    <a href="mailto:{{ $req->email }}" class="link-quiet">{{ $req->email }}</a> ·
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $req->phone) }}" class="link-quiet">{{ $req->phone }}</a>
                </p>
                @if ($req->message)
                    <p class="mt-2 whitespace-pre-line text-sm text-muted">{{ $req->message }}</p>
                @endif
            </article>
        @empty
            <div class="panel">
                @include('admin.partials.empty', ['empty' => 'Ninguna clínica ha solicitado acceso todavía.'])
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</x-layouts.admin>
