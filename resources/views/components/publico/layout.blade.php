@props(['titulo' => 'Canal de denuncias', 'empresa' => null])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $titulo }}</title>
    <style>
        :root {
            --gold:#BFAE76; --gold-dark:#8a7a45; --dark:#1a1710;
            --text:#333; --text-light:#888; --border:#e0e0e0; --white:#fff;
        }
        * { box-sizing:border-box; }
        body {
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
            background:#f5f5f0; color:var(--text); margin:0; padding:0;
            line-height:1.55;
        }
        /* Franja oscura: el logo de GoHarv es blanco, así que necesita
           fondo negro para verse. */
        .cn-banner {
            background: #0a0a0a; color:#fff; text-align:center;
            padding:14px 20px 13px;
        }
        .cn-marca { margin-bottom:9px; }
        .cn-logo-img {
            height:26px; width:auto; display:inline-block; vertical-align:middle;
        }
        .cn-banner-texto {
            font-size:12px; line-height:1.5; color:#ccc;
            max-width:560px; margin:0 auto;
        }
        @media (max-width:640px) {
            .cn-logo-img { height:22px; }
            .cn-banner-texto { font-size:11px; }
        }
        .cn-wrap { max-width:780px; margin:0 auto; padding:28px 16px 60px; }
        .cn-logo {
            text-align:center; color:var(--gold-dark); font-weight:700;
            font-size:18px; margin-bottom:6px;
        }
        .cn-logo .marca { color:var(--gold); }
        .cn-sub { text-align:center; color:var(--text-light); font-size:13px; margin-bottom:26px; }

        .cn-card {
            background:var(--white); border-radius:14px; padding:26px;
            box-shadow:0 4px 20px rgba(0,0,0,.06); margin-bottom:18px;
        }
        .cn-card h2 { font-size:17px; margin:0 0 4px; color:var(--dark); }
        .cn-card .ayuda { font-size:13px; color:var(--text-light); margin:0 0 18px; }

        .cn-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:15px; }
        .cn-grid .ancho { grid-column:1 / -1; }
        .campo { display:flex; flex-direction:column; gap:5px; }
        .campo label { font-size:12px; font-weight:700; color:var(--text-light); text-transform:uppercase; letter-spacing:.04em; }
        .campo input, .campo select, .campo textarea {
            padding:10px 13px; border:1px solid var(--border); border-radius:8px;
            font-size:14px; font-family:inherit; outline:none; background:var(--white);
        }
        .campo input:focus, .campo select:focus, .campo textarea:focus { border-color:var(--gold); }
        .campo .nota { font-size:11px; color:var(--text-light); }

        .cn-btn {
            padding:12px 24px; border:none; border-radius:8px; font-size:14px;
            font-weight:600; cursor:pointer; font-family:inherit; text-decoration:none;
            display:inline-block; text-align:center;
        }
        .cn-btn-principal { background:var(--gold); color:var(--dark); }
        .cn-btn-principal:hover { background:var(--gold-dark); color:var(--white); }
        .cn-btn-secundario { background:#f0f0f0; color:var(--text); }

        .cn-error {
            background:#fde8e8; color:#b00020; padding:12px 16px;
            border-radius:8px; font-size:13px; margin-bottom:18px;
        }
        .cn-error div + div { margin-top:4px; }
        .cn-ok {
            background:#d4edda; color:#155724; padding:12px 16px;
            border-radius:8px; font-size:13px; margin-bottom:18px;
        }
        .cn-aviso {
            background:#fff8e1; border:1px solid #ffe082; border-left:4px solid #f59e0b;
            border-radius:8px; padding:13px 15px; font-size:13px; color:#78350f;
            margin-bottom:18px;
        }

        .cn-pie { text-align:center; font-size:12px; color:#aaa; margin-top:28px; }
        .cn-pie a { color:var(--gold-dark); }

        @media (max-width:640px) { .cn-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>

{{--
    El original decía "No rastreamos IP ni metadatos", y eso no es cierto:
    el sistema guarda tracking_sessions con la IP hasheada y cifrada, y
    complaint_events con la IP de origen. Es información necesaria para
    detectar abuso, pero afirmar lo contrario en un canal de denuncias es
    una promesa que el sistema no cumple.
--}}
<div class="cn-banner">
    <div class="cn-marca">
        <img src="{{ asset('img/goharv-logo.png') }}" alt="GoHarv" class="cn-logo-img">
    </div>
    <div class="cn-banner-texto">
        Tu denuncia puede ser anónima. Registramos datos técnicos mínimos para
        prevenir abusos, cifrados y sin vincularlos con tu identidad.
    </div>
</div>

<div class="cn-wrap">
    <div class="cn-logo"><span class="marca">◆</span> Rise UP Compliance</div>
    <p class="cn-sub">
        @if ($empresa)
            Canal de denuncias · {{ $empresa->name }}
        @else
            Canal de denuncias
        @endif
    </p>

    {{ $slot }}

    <p class="cn-pie">
        <a href="{{ route('portada.inicio') }}">Inicio</a> ·
        <a href="{{ route('seguimiento.formulario') }}">Consultar el estado de una denuncia</a>
    </p>
</div>

</body>
</html>
