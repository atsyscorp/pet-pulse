{{-- Public site header: sticky, tonal, collapses to a menu on phones. --}}
<header class="sticky top-0 z-40 border-b border-line bg-page/95 backdrop-blur-sm">
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6" aria-label="Principal">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="PetPulse, inicio">
            <img src="{{ asset('images/icon-192.png') }}" alt="" width="40" height="40" class="size-10 rounded-xl">
            <span class="text-xl font-extrabold tracking-tight text-ink">Pet<span class="text-trace">Pulse</span></span>
        </a>

        <div class="hidden items-center gap-8 text-sm font-semibold text-muted lg:flex">
            <a href="{{ route('home') }}#funciones" class="flex min-h-12 items-center transition-colors hover:text-ink">Funciones</a>
            <a href="{{ route('home') }}#como-funciona" class="flex min-h-12 items-center transition-colors hover:text-ink">Cómo funciona</a>
            <a href="{{ route('home') }}#para-quien" class="flex min-h-12 items-center transition-colors hover:text-ink">Para quién</a>
            <a href="{{ route('home') }}#contacto" class="flex min-h-12 items-center transition-colors hover:text-ink">Contacto</a>
        </div>

        <div class="hidden items-center gap-3 lg:flex">
            <x-theme-toggle />
            @auth
                <a href="{{ route('hospital.kardex') }}" class="btn-primary !py-2 !text-sm">Ir al kárdex</a>
            @else
                <a href="{{ route('login') }}" class="btn-secondary !py-2 !text-sm">Iniciar sesión</a>
                <a href="{{ route('access.create') }}" class="btn-primary !py-2 !text-sm">Solicitar acceso</a>
            @endauth
        </div>

        <button type="button" data-menu-toggle class="grid size-12 place-items-center rounded-xl border border-line bg-surface text-ink lg:hidden" aria-expanded="false" aria-controls="menu-movil" aria-label="Abrir menú">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </nav>

    <div id="menu-movil" hidden class="border-t border-line bg-page px-4 pb-5 pt-3 lg:hidden">
        <div class="flex flex-col text-base font-semibold text-ink">
            <a href="{{ route('home') }}#funciones" class="rounded-xl px-3 py-3.5 hover:bg-sunken">Funciones</a>
            <a href="{{ route('home') }}#como-funciona" class="rounded-xl px-3 py-3.5 hover:bg-sunken">Cómo funciona</a>
            <a href="{{ route('home') }}#para-quien" class="rounded-xl px-3 py-3.5 hover:bg-sunken">Para quién</a>
            <a href="{{ route('home') }}#contacto" class="rounded-xl px-3 py-3.5 hover:bg-sunken">Contacto</a>
        </div>
        <div class="mt-3 flex flex-col gap-3">
            @auth
                <a href="{{ route('hospital.kardex') }}" class="btn-primary">Ir al kárdex</a>
            @else
                <a href="{{ route('access.create') }}" class="btn-primary">Solicitar acceso</a>
                <a href="{{ route('login') }}" class="btn-secondary">Iniciar sesión</a>
            @endauth
            <div class="flex items-center justify-between pt-2">
                <span class="text-sm font-semibold text-muted">Tema</span>
                <x-theme-toggle />
            </div>
        </div>
    </div>
</header>
