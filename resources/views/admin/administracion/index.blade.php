<x-admin.layout :titulo="$titulo">

    @php
        $urlTab = fn (string $t) => route('admin.administracion.index', array_filter([
            'tab' => $t,
            'empresa' => $filtroEmpresa ?: null,
            'sucursal' => $t === 'sucursales' ? ($filtroSucursal ?: null) : null,
        ]));
    @endphp

    <style>
        .adm-tabs { display:flex; border-bottom:2px solid var(--border); margin-bottom:20px; flex-wrap:wrap; }
        .adm-tab {
            padding:10px 18px; font-size:13px; font-weight:600; color:var(--text-light);
            border-bottom:2px solid transparent; margin-bottom:-2px; text-decoration:none;
            white-space:nowrap; transition:color .15s;
        }
        .adm-tab:hover { color:var(--dark); }
        .adm-tab.active { color:var(--gold-dark); border-bottom-color:var(--gold); }
    </style>

    {{-- El filtro por empresa solo tiene sentido para el superadmin: el
         admin principal ve una sola. El de sucursal, en cambio, le sirve
         a los dos. --}}
    @if ($esSuperadmin || $tab === 'sucursales')
        <div class="card" style="margin-bottom:20px;">
            <form method="GET" action="{{ route('admin.administracion.index') }}"
                  style="padding:16px 20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="tab" value="{{ $tab }}">

                @if ($esSuperadmin)
                    <x-autocompletado
                        nombre="empresa"
                        :opciones="$opcionesEmpresa"
                        :valor="$filtroEmpresa"
                        placeholder="Buscar empresa..." />
                @endif

                @if ($tab === 'sucursales')
                    <x-autocompletado
                        nombre="sucursal"
                        :grupos="$sucursalesPorEmpresa"
                        :padre="$esSuperadmin ? 'empresa' : null"
                        :opciones="$esSuperadmin ? [] : ($sucursalesPorEmpresa[auth()->user()->empresa?->name] ?? [])"
                        :valor="$filtroSucursal"
                        :deshabilitado="$esSuperadmin && ! $filtroEmpresa"
                        :placeholder="$esSuperadmin && ! $filtroEmpresa ? 'Elegí una empresa primero' : 'Buscar sucursal...'" />
                @endif

                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <a href="{{ route('admin.administracion.index', ['tab' => $tab]) }}"
                   class="btn btn-secondary btn-sm">Limpiar</a>
            </form>
        </div>
    @endif

    <div class="adm-tabs">
        @if ($esSuperadmin)
            <a href="{{ $urlTab('empresas') }}" class="adm-tab {{ $tab === 'empresas' ? 'active' : '' }}">
                Empresas
            </a>
        @endif
        <a href="{{ $urlTab('sucursales') }}" class="adm-tab {{ $tab === 'sucursales' ? 'active' : '' }}">
            Sucursales
        </a>
        <a href="{{ $urlTab('usuarios') }}" class="adm-tab {{ $tab === 'usuarios' ? 'active' : '' }}">
            Usuarios
        </a>
    </div>

    @if ($tab === 'empresas')
        @include('admin.administracion._tab-empresas')
    @elseif ($tab === 'sucursales')
        @include('admin.administracion._tab-sucursales')
    @else
        @include('admin.administracion._tab-usuarios')
    @endif

</x-admin.layout>
