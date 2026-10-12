{{-- Public site header: sticky, translucent, collapses to a menu on phones. --}}
<header
    x-data="{ open: false }"
    class="sticky top-0 z-40 border-b border-white/10 bg-night/80 backdrop-blur-md"
>
    <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6" aria-label="Principal">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="PetPulse, inicio">
            <img src="{{ asset('images/icon-192.png') }}" alt="" width="40" height="40" class="size-10 rounded-xl">
            <span class="text-xl font-extrabold tracking-tight text-white">Pet<span class="text-neon-cyan">Pulse</span></span>
        </a>

        <div class="hidden items-center gap-8 text-sm font-semibold text-slate-300 md:flex">
            <a href="{{ route('home') }}#funciones" class="hover:text-white">Funciones</a>
            <a href="{{ route('home') }}#como-funciona" class="hover:text-white">Cómo funciona</a>
            <a href="{{ route('home') }}#para-quien" class="hover:text-white">Para quién</a>
            <a href="{{ route('home') }}#contacto" class="hover:text-white">Contacto</a>
        </div>

        <div class="hidden items-center gap-3 md:flex">
            @auth
                <a href="{{ route('hospital.kardex') }}" class="btn-neon">Ir al kárdex</a>
            @else
                <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-sm font-bold text-white ring-1 ring-inset ring-white/20 hover:bg-white/5">Iniciar sesión</a>
                <a href="{{ route('home') }}#contacto" class="btn-neon">Solicitar demostración</a>
            @endauth
        </div>

        <button type="button" class="grid size-11 place-items-center rounded-xl text-white md:hidden" x-on:click="open = !open" :aria-expanded="open" aria-controls="menu-movil" aria-label="Abrir menú">
            <svg x-show="!open" class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            <svg x-show="open" x-cloak class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </nav>

    <div id="menu-movil" x-show="open" x-cloak x-transition.opacity class="border-t border-white/10 bg-night px-4 py-4 md:hidden">
        <div class="flex flex-col gap-1 text-base font-semibold text-slate-200">
            <a href="{{ route('home') }}#funciones" x-on:click="open = false" class="rounded-lg px-3 py-3 hover:bg-white/5">Funciones</a>
            <a href="{{ route('home') }}#como-funciona" x-on:click="open = false" class="rounded-lg px-3 py-3 hover:bg-white/5">Cómo funciona</a>
            <a href="{{ route('home') }}#para-quien" x-on:click="open = false" class="rounded-lg px-3 py-3 hover:bg-white/5">Para quién</a>
            <a href="{{ route('home') }}#contacto" x-on:click="open = false" class="rounded-lg px-3 py-3 hover:bg-white/5">Contacto</a>
        </div>
        <div class="mt-4 flex flex-col gap-3">
            @auth
                <a href="{{ route('hospital.kardex') }}" class="btn-neon justify-center">Ir al kárdex</a>
            @else
                <a href="{{ route('login') }}" class="rounded-xl px-4 py-3 text-center font-bold text-white ring-1 ring-inset ring-white/20">Iniciar sesión</a>
                <a href="{{ route('home') }}#contacto" x-on:click="open = false" class="btn-neon justify-center">Solicitar demostración</a>
            @endauth
        </div>
    </div>
</header>
