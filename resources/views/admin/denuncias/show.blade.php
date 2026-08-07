@php
    $coloresPrioridad = [
        'low'      => ['#e0e0e0', '#555'],
        'medium'   => ['#fff3cd', '#856404'],
        'high'     => ['#ffe0b2', '#e65100'],
        'critical' => ['#fde8e8', '#b00020'],
    ];
    [$prioFondo, $prioTexto] = $coloresPrioridad[$denuncia->priority->value] ?? ['#eee', '#333'];

    $notasPrioritarias = $notas->where('is_priority', true)->whereNull('deleted_at')->count();
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $denuncia->internal_code }} — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <style>
        .det-tabs {
            display: flex;
            border-bottom: 2px solid var(--border);
            margin-bottom: 24px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .det-tab {
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-light);
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            text-decoration: none;
            transition: color .15s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .det-tab:hover { color: var(--dark); }
        .det-tab.active { color: var(--gold-dark); border-bottom-color: var(--gold); }
        .det-tab .tab-badge {
            display: inline-block;
            background: #fde8e8;
            color: #b00020;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 999px;
            margin-left: 5px;
            vertical-align: middle;
        }
        @media (max-width: 768px) {
            .det-tab { padding: 8px 12px; font-size: 12px; }
            .action-card .btn { width: 100%; justify-content: center; }
            .action-card select,
            .action-card textarea { margin-bottom: 10px; }
        }
    </style>
</head>
<body>
<div class="admin-layout">

    <x-admin.sidebar />

    <div class="main-content">

        <div class="topbar" style="flex-wrap:wrap;height:auto;min-height:var(--header-h);padding:8px 16px;gap:6px;">
            <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>

            <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0;">
                <a href="{{ $volver }}" style="color:#aaa;font-size:13px;white-space:nowrap;">← Volver</a>
                <span class="topbar-title"
                      style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:14px;">
                    {{ $denuncia->internal_code }}
                </span>
            </div>

            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                <span class="badge badge-{{ $denuncia->status->value }}">{{ $denuncia->status->etiqueta() }}</span>
                <span class="badge" style="background:{{ $prioFondo }};color:{{ $prioTexto }};">
                    {{ $denuncia->priority->etiqueta() }}
                </span>
            </div>
        </div>

        <div class="page-content">

            @if (session('estado'))
                <div class="alert alert-success">{{ session('estado') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            @php
                $urlTab = fn (string $t) => route('admin.denuncias.show', $denuncia)
                    . '?' . http_build_query(array_filter([
                        'tab' => $t,
                        'volver' => $volverCrudo,
                    ]));
            @endphp

            <div class="det-tabs">
                <a href="{{ $urlTab('info') }}" class="det-tab {{ $tab === 'info' ? 'active' : '' }}">
                    Información
                </a>
                <a href="{{ $urlTab('historial') }}" class="det-tab {{ $tab === 'historial' ? 'active' : '' }}">
                    Historial
                </a>
                @if ($puedeVerChat)
                    <a href="{{ $urlTab('chat') }}" class="det-tab {{ $tab === 'chat' ? 'active' : '' }}">
                        Chat
                    </a>
                @endif
                @if ($puedeVerNotas)
                    <a href="{{ $urlTab('notas') }}" class="det-tab {{ $tab === 'notas' ? 'active' : '' }}">
                        Notas internas
                        @if ($notasPrioritarias)
                            <span class="tab-badge">{{ $notasPrioritarias }}</span>
                        @endif
                    </a>
                @endif
            </div>

            @if ($tab === 'info')
                @include('admin.denuncias._tab-info')
            @elseif ($tab === 'historial')
                @include('admin.denuncias._tab-historial')
            @elseif ($tab === 'chat')
                @include('admin.denuncias._tab-chat')
            @elseif ($tab === 'notas')
                @include('admin.denuncias._tab-notas')
            @endif

        </div>
    </div>
</div>
</body>
</html>
