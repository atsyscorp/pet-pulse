<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Wall displays / tablets pinned to the home screen run full-screen. --}}
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0f172a">
    <title>{{ $title ?? 'Kárdex' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-900 antialiased select-none">
    {{ $slot }}

    {{-- Global toast stack: fed by `kardex-toast` browser events from Livewire. --}}
    <div
        x-data="{
            toasts: [],
            push(detail) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, ...detail });
                setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), detail.type === 'error' ? 9000 : 5000);
            },
        }"
        x-on:kardex-toast.window="push($event.detail)"
        class="pointer-events-none fixed inset-x-0 bottom-4 z-[60] flex flex-col items-center gap-2 px-4"
        aria-live="assertive"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition.opacity.duration.200ms
                class="pointer-events-auto w-full max-w-lg rounded-2xl px-5 py-4 text-base font-semibold shadow-2xl ring-1"
                :class="{
                    'bg-emerald-600 text-white ring-emerald-700': toast.type === 'success',
                    'bg-red-600 text-white ring-red-700': toast.type === 'error',
                    'bg-amber-400 text-amber-950 ring-amber-500': toast.type === 'warning',
                }"
                role="status"
                x-text="toast.message"
                x-on:click="toasts = toasts.filter(t => t.id !== toast.id)"
            ></div>
        </template>
    </div>
</body>
</html>
