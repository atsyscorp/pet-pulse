<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f1f5f9">
    <title>{{ $title ?? 'PetPulse' }} · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pp-body min-h-full font-sans antialiased">
    <x-clinical-nav />

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        @if (session('status'))
            {{-- A component that shows its own in-page notice dispatches `clinical-notice` to retire this one. --}}
            <div class="mb-6 flex items-start gap-3 rounded-2xl border border-ok bg-ok-soft px-4 py-3 text-base font-semibold text-ok" role="status" x-data x-on:clinical-notice.window="$el.remove()">
                <svg viewBox="0 0 24 24" class="mt-0.5 size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
