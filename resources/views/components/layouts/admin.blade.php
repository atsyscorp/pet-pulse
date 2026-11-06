@props(['title' => 'Panel'])
@php($admin = auth('admin')->user())
@php($links = [
    'admin.dashboard' => 'Resumen',
    'admin.owners' => 'Propietarios',
    'admin.clinics' => 'Clínicas',
    'admin.staff' => 'Personal',
    'admin.access-requests' => 'Solicitudes',
])
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#f1f5f9">
    <title>{{ $title }} · Superadministración · PetPulse</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pp-body min-h-full font-sans antialiased">
    <header class="sticky top-0 z-40 border-b border-line bg-page/95 backdrop-blur-sm">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3" aria-label="PetPulse, superadministración">
                <img src="{{ asset('images/icon-192.png') }}" alt="" width="40" height="40" class="size-9 rounded-xl sm:size-10">
                <span class="text-xl font-extrabold tracking-tight text-ink">Pet<span class="text-trace">Pulse</span></span>
            </a>
            <span class="rounded-full px-3 py-1 text-xs font-bold text-muted ring-1 ring-inset ring-line">Superadmin</span>

            <div class="ml-auto flex items-center gap-3">
                <span class="hidden text-sm text-muted md:inline">{{ $admin?->name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary !px-3 !py-2 !text-sm sm:!px-4">Salir</button>
                </form>
            </div>
        </div>
        {{-- Five sections: on phones the row scrolls sideways instead of wrapping. --}}
        <nav class="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-2 pb-2 text-sm font-semibold sm:px-4" aria-label="Superadministración">
            @foreach ($links as $route => $label)
                <a href="{{ route($route) }}" @class([
                    'flex min-h-12 shrink-0 items-center rounded-xl px-3 transition-colors',
                    'bg-sunken text-ink' => request()->routeIs($route),
                    'text-muted hover:text-ink' => ! request()->routeIs($route),
                ]) @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        {{ $slot }}

        <p class="mt-10 text-xs text-muted">
            Contiene datos personales de clínicas y propietarios (Ley 1581 de 2012). Úselos solo para administrar la plataforma.
        </p>
    </main>
</body>
</html>
