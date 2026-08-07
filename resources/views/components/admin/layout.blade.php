@props(['titulo' => 'Panel'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titulo }} — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#BFAE76">
</head>
<body>
<div class="admin-layout">

    <x-admin.sidebar />

    <div class="main-content">

        <x-admin.topbar :titulo="$titulo" />

        <div class="page-content">

            @if (session('estado'))
                <div class="alert alert-success">{{ session('estado') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            {{ $slot }}

        </div>
    </div>
</div>
</body>
</html>
