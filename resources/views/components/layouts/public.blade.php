<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}PetPulse</title>
    <meta name="description" content="{{ $description ?? 'PetPulse: hospitalización veterinaria con control exacto de cada dosis. Kárdex de enfermería en tiempo real, inventario fraccional y trazabilidad para clínicas en Colombia y LATAM.' }}">
    <meta name="theme-color" content="#120c36">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="PetPulse">
    <meta property="og:title" content="{{ $title ?? 'PetPulse · Hospitalización veterinaria con control de cada dosis' }}">
    <meta property="og:description" content="{{ $description ?? 'Kárdex de enfermería en tiempo real, inventario fraccional y trazabilidad para clínicas veterinarias.' }}">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-night text-slate-100 antialiased">
    {{ $slot }}
</body>
</html>
