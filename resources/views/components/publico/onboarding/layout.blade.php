@props(['titulo' => 'Alta de empresa'])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }} — Rise UP Compliance</title>
    <style>
        :root {
            --gold:#BFAE76; --gold-dark:#8a7a45; --dark:#1a1710;
            --text:#333; --text-light:#888; --border:#e0e0e0; --white:#fff;
        }
        * { box-sizing:border-box; }
        body {
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
            background:#f5f5f0; color:var(--text); margin:0; padding:32px 16px;
            line-height:1.5;
        }
        .ob-wrap { max-width:760px; margin:0 auto; }
        .ob-logo {
            text-align:center; color:var(--gold-dark); font-weight:700;
            font-size:18px; margin-bottom:8px;
        }
        .ob-logo .marca { color:var(--gold); }
        .ob-sub { text-align:center; color:var(--text-light); font-size:13px; margin-bottom:28px; }

        .ob-pasos { display:flex; gap:8px; margin-bottom:24px; }
        .ob-paso {
            flex:1; text-align:center; padding:10px 6px; border-radius:8px;
            font-size:12px; font-weight:600; background:#eceae2; color:#aaa;
        }
        .ob-paso.activo { background:var(--gold); color:var(--dark); }
        .ob-paso.hecho { background:#d9d3bd; color:var(--gold-dark); }

        .ob-card {
            background:var(--white); border-radius:14px; padding:28px;
            box-shadow:0 4px 20px rgba(0,0,0,.06); margin-bottom:20px;
        }
        .ob-card h2 { font-size:17px; margin:0 0 4px; color:var(--dark); }
        .ob-card .ayuda { font-size:13px; color:var(--text-light); margin:0 0 20px; }

        .ob-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
        .ob-grid .ancho { grid-column:1 / -1; }
        .campo { display:flex; flex-direction:column; gap:5px; }
        .campo label { font-size:12px; font-weight:700; color:var(--text-light); text-transform:uppercase; letter-spacing:.04em; }
        .campo input, .campo select, .campo textarea {
            padding:10px 13px; border:1px solid var(--border); border-radius:8px;
            font-size:14px; font-family:inherit; outline:none; background:var(--white);
        }
        .campo input:focus, .campo select:focus { border-color:var(--gold); }
        .campo .nota { font-size:11px; color:var(--text-light); }

        .ob-btn {
            padding:11px 22px; border:none; border-radius:8px; font-size:14px;
            font-weight:600; cursor:pointer; font-family:inherit;
        }
        .ob-btn-principal { background:var(--gold); color:var(--dark); }
        .ob-btn-principal:hover { background:var(--gold-dark); color:var(--white); }
        .ob-btn-secundario { background:#f0f0f0; color:var(--text); }

        .ob-error {
            background:#fde8e8; color:#b00020; padding:12px 16px;
            border-radius:8px; font-size:13px; margin-bottom:18px;
        }
        .ob-error div + div { margin-top:4px; }

        .ob-fila {
            display:grid; grid-template-columns:1fr 1fr auto; gap:10px;
            align-items:end; margin-bottom:10px;
        }
        .ob-quitar {
            background:none; border:1px solid var(--border); color:#b00020;
            border-radius:8px; padding:10px 13px; cursor:pointer; font-size:14px;
        }

        @media (max-width:640px) {
            .ob-grid { grid-template-columns:1fr; }
            .ob-fila { grid-template-columns:1fr; }
            .ob-paso { font-size:10px; padding:8px 4px; }
        }
    </style>
</head>
<body>
<div class="ob-wrap">

    <div class="ob-logo"><span class="marca">◆</span> Rise UP Compliance</div>
    <p class="ob-sub">Alta de empresa</p>

    {{ $slot }}

</div>
</body>
</html>
