@props(['titulo' => 'Panel'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }} — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32x32.png') }}">
    <meta name="theme-color" content="#BFAE76">
    <meta name="mobile-web-app-capable" content="yes">
</head>
<body class="login-body">

<div class="login-wrapper">
    <div class="login-card">

        <div class="login-logo">
            <span class="logo-mark">◆</span>
            <span class="logo-text">Rise UP Compliance</span>
        </div>

        {{ $slot }}

    </div>

    <p class="login-footer">
        {{ config('app.name') }} &mdash; Acceso exclusivo para personal autorizado
    </p>
</div>

{{-- Rutas relativas, no absolutas: el /Red-denuncias/ hardcodeado del
     original se rompía en cualquier despliegue que no fuera ese XAMPP. --}}
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('{{ asset('service-worker.js') }}');
    }
</script>

</body>
</html>
