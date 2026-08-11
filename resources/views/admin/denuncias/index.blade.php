<x-admin.layout :titulo="$titulo">

    @php
        // Colores de prioridad: el CSS no tiene clases propias para esto,
        // así que se portan los estilos inline del listado original.
        $coloresPrioridad = [
            'low'      => ['#e0e0e0', '#555'],
            'medium'   => ['#fff3cd', '#856404'],
            'high'     => ['#ffe0b2', '#e65100'],
            'critical' => ['#fde8e8', '#b00020'],
        ];
    @endphp

    {{-- FILTROS --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">Filtros rápidos</span>
        </div>

        <form method="GET" action="{{ route('admin.denuncias.index') }}" style="padding:16px 20px;">
            <div class="filters" style="margin-bottom:12px;">

                <input type="text" name="codigo"
                       placeholder="Ej: A-CC-539270-027194"
                       value="{{ $filtros['codigo'] ?? '' }}">

                @if ($esSuperadmin)
                    <x-autocompletado
                        nombre="empresa"
                        :opciones="$empresas"
                        :valor="$filtros['empresa'] ?? ''"
                        placeholder="Buscar empresa..." />
                @endif

                @php
                    $sucursalBloqueada = $esSuperadmin && empty($filtros['empresa']);
                @endphp

                <x-autocompletado
                    nombre="sucursal"
                    :opciones="$sucursales"
                    :valor="$filtros['sucursal'] ?? ''"
                    :deshabilitado="$sucursalBloqueada"
                    :placeholder="$sucursalBloqueada ? 'Elegí una empresa primero' : 'Buscar sucursal...'" />

                <select name="estado">
                    <option value="">Todos los estados</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}"
                            @selected(($filtros['estado'] ?? null) === $estado->value)>
                            {{ $estado->etiqueta() }}
                        </option>
                    @endforeach
                </select>

                <select name="prioridad">
                    <option value="">Todas las prioridades</option>
                    @foreach ($prioridades as $prioridad)
                        <option value="{{ $prioridad->value }}"
                            @selected(($filtros['prioridad'] ?? null) === $prioridad->value)>
                            {{ $prioridad->etiqueta() }}
                        </option>
                    @endforeach
                </select>

                @php
                    $fechaActual = $filtros['fecha'] ?? null;
                @endphp

                <select name="fecha" id="filtro-fecha">
                    <option value="">Fecha ingreso</option>
                    <option value="hoy" @selected($fechaActual === 'hoy')>Hoy</option>
                    <option value="semana" @selected($fechaActual === 'semana')>Última semana</option>
                    <option value="mes" @selected($fechaActual === 'mes')>Último mes</option>
                    <option value="personalizado" @selected($fechaActual === 'personalizado')>Personalizado</option>
                </select>

            </div>

            <div id="rango-personalizado"
                 style="display:{{ $fechaActual === 'personalizado' ? 'flex' : 'none' }};gap:10px;margin-bottom:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <label style="font-size:13px;font-weight:600;">Desde</label>
                    <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}">
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <label style="font-size:13px;font-weight:600;">Hasta</label>
                    <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}">
                </div>
            </div>

            <input type="hidden" name="per_page" value="{{ $porPagina }}">

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.denuncias.index') }}" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- TABLA --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                @if ($denuncias->total())
                    Mostrando {{ $denuncias->firstItem() }}–{{ $denuncias->lastItem() }}
                    de {{ $denuncias->total() }} resultados
                @else
                    Sin resultados
                @endif
            </span>

            <div style="display:flex;align-items:center;gap:10px;">
                <label style="font-size:13px;">Filas por página:</label>
                <select onchange="cambiarPorPagina(this.value)"
                        style="padding:6px 10px;border:1px solid #e0e0e0;border-radius:8px;font-size:13px;">
                    @foreach ([10, 25, 50] as $cantidad)
                        <option value="{{ $cantidad }}" @selected($porPagina === $cantidad)>{{ $cantidad }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-wrapper">
            @if ($denuncias->isEmpty())
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <p>No se encontraron denuncias con los filtros aplicados.</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Código interno</th>
                            @if ($esSuperadmin)
                                <th>Empresa</th>
                            @endif
                            <th>Sucursal</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Asignado</th>
                            <th>Fecha ingreso</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($denuncias as $denuncia)
                            @php
                                // Los filtros actuales viajan a la vista de detalle
                                // para poder volver al mismo listado.
                                $volver = request()->getQueryString();
                                $urlDetalle = route('admin.denuncias.show', $denuncia)
                                    . ($volver ? '?' . http_build_query(['volver' => $volver]) : '');

                                [$fondo, $texto] = $coloresPrioridad[$denuncia->priority->value] ?? ['#eee', '#333'];
                            @endphp

                            <tr class="fila-link tabla-denuncias"
                                onclick="window.location='{{ $urlDetalle }}'">
                                <td><strong>{{ $denuncia->internal_code }}</strong></td>
                                @if ($esSuperadmin)
                                    <td>{{ $denuncia->empresa->name }}</td>
                                @endif
                                <td>{{ $denuncia->sucursal?->name ?? '—' }}</td>
                                <td>{{ $denuncia->categoria?->name_es ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-{{ $denuncia->status->value }}">
                                        {{ $denuncia->status->etiqueta() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background:{{ $fondo }};color:{{ $texto }};">
                                        {{ $denuncia->priority->etiqueta() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($denuncia->tiene_asignado)
                                        <span class="badge-activo">Asignado</span>
                                    @else
                                        <span style="color:#aaa">Sin asignar</span>
                                    @endif
                                </td>
                                <td>{{ $denuncia->created_at->format('d/m/Y') }}</td>
                                <td class="col-accion-ver">
                                    <a href="{{ $urlDetalle }}" class="btn btn-primary btn-sm">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if ($denuncias->hasPages())
            <div class="pagination">
                {{-- Paginación manual: el paginador de Laravel trae su propio
                     markup con clases de Tailwind, que este CSS no tiene. --}}
                @if ($denuncias->currentPage() > 1)
                    <a href="{{ $denuncias->previousPageUrl() }}">‹</a>
                @endif

                @foreach (range(
                    max(1, $denuncias->currentPage() - 2),
                    min($denuncias->lastPage(), $denuncias->currentPage() + 2)
                ) as $numero)
                    @if ($numero === $denuncias->currentPage())
                        <span class="current">{{ $numero }}</span>
                    @else
                        <a href="{{ $denuncias->url($numero) }}">{{ $numero }}</a>
                    @endif
                @endforeach

                @if ($denuncias->currentPage() < $denuncias->lastPage())
                    <a href="{{ $denuncias->nextPageUrl() }}">›</a>
                @endif
            </div>
        @endif
    </div>

    <script>
        document.getElementById('filtro-fecha').addEventListener('change', function () {
            document.getElementById('rango-personalizado').style.display =
                this.value === 'personalizado' ? 'flex' : 'none';
        });

        function cambiarPorPagina(valor) {
            const params = new URLSearchParams(window.location.search);
            params.set('per_page', valor);
            params.delete('page');
            window.location.href = '?' + params.toString();
        }

        // Al elegir empresa se recarga para traer sus sucursales.
        function actualizarSucursales() {
            const empresa = document.getElementById('input-empresa')?.value?.trim();
            const sucursal = document.getElementById('input-sucursal');
            if (!sucursal) return;

            sucursal.value = '';

            if (empresa) {
                sucursal.disabled = false;
                document.querySelector('form[method="GET"]').submit();
            } else {
                sucursal.disabled = true;
            }
        }
    </script>

</x-admin.layout>