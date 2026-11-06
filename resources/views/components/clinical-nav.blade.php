{{-- Clinical app header: shared by the patient and admission screens (the kárdex keeps its own dark chrome). --}}
@php($user = auth()->user())
<header class="sticky top-0 z-40 border-b border-line bg-page/95 backdrop-blur-sm">
    <nav class="mx-auto flex h-16 max-w-7xl items-center gap-2 px-3 sm:gap-3 sm:px-6" aria-label="Aplicación clínica">
        <a href="{{ route('hospital.kardex') }}" class="flex items-center gap-3" aria-label="PetPulse, kárdex">
            <img src="{{ asset('images/icon-192.png') }}" alt="" width="40" height="40" class="size-9 rounded-xl sm:size-10">
            <span class="hidden text-xl font-extrabold tracking-tight text-ink sm:inline">Pet<span class="text-trace">Pulse</span></span>
        </a>

        <div class="flex items-center text-sm font-semibold sm:ml-2 sm:gap-1">
            <a href="{{ route('hospital.kardex') }}" @class(['flex min-h-12 items-center rounded-xl px-2 transition-colors sm:px-3', 'bg-sunken text-ink' => request()->routeIs('hospital.kardex'), 'text-muted hover:text-ink' => ! request()->routeIs('hospital.kardex')])>Kárdex</a>
            <a href="{{ route('hospital.patients.index') }}" @class(['flex min-h-12 items-center rounded-xl px-2 transition-colors sm:px-3', 'bg-sunken text-ink' => request()->routeIs('hospital.patients.*', 'hospital.stays.*'), 'text-muted hover:text-ink' => ! request()->routeIs('hospital.patients.*', 'hospital.stays.*')])>Pacientes</a>
            <a href="{{ route('hospital.drugs.index') }}" @class(['flex min-h-12 items-center rounded-xl px-2 transition-colors sm:px-3', 'bg-sunken text-ink' => request()->routeIs('hospital.drugs.*'), 'text-muted hover:text-ink' => ! request()->routeIs('hospital.drugs.*')])>Medicamentos</a>
        </div>

        <div class="ml-auto flex items-center gap-3">
            <span class="hidden text-sm text-muted md:inline" title="{{ $user?->role->label() }}">{{ $user?->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-secondary !px-3 !py-2 !text-sm sm:!px-4">Salir</button>
            </form>
        </div>
    </nav>
</header>
