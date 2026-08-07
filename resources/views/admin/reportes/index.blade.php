<x-admin.layout titulo="Reportes">

    @php
        $urlTab = fn (string $t) => route('admin.reportes.index', array_merge($filtros, ['tab' => $t]));

        // Enlace al listado con el mismo periodo, para poder bajar del
        // numero al detalle sin perder el contexto.
        $urlListado = function (array $extra = []) use ($filtros) {
            $p = array_intersect_key($filtros, array_flip(['fecha', 'desde', 'hasta', 'empresa', 'sucursal']));

            return route('admin.denuncias.index', array_merge($p, $extra));
        };
    @endphp

    <style>
        .tabs { display:flex; border-bottom:2px solid var(--border); margin-bottom:24px; flex-wrap:wrap; }
        .tab-item {
            padding:10px 18px; font-size:13px; font-weight:600; color:var(--text-light);
            border-bottom:2px solid transparent; margin-bottom:-2px; text-decoration:none;
            transition:color .15s; white-space:nowrap;
        }
        .tab-item:hover { color:var(--dark); }
        .tab-item.active { color:var(--gold-dark); border-bottom-color:var(--gold); }

        .resumen-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:16px; }
        .resumen-grid-2 { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
        .reporte-card {
            background:var(--white); border-radius:var(--radius); padding:24px;
            box-shadow:var(--shadow); border:2px solid transparent;
        }
        .reporte-card .label { font-size:13px; color:var(--text-light); margin-bottom:8px; }
        .reporte-card .valor { font-size:38px; font-weight:700; color:var(--dark); line-height:1; }
        .reporte-card .alerta { font-size:12px; color:#b00020; font-weight:600; margin-top:6px; }
        .reporte-card .nota { font-size:12px; color:var(--text-light); margin-top:6px; }
        a.reporte-card { display:block; text-decoration:none; }
        a.reporte-card:hover { border-color:var(--gold); }

        .barra-fondo { background:#f0f0f0; border-radius:999px; height:8px; overflow:hidden; min-width:80px; }
        .barra-relleno { background:var(--gold); height:100%; border-radius:999px; }

        @media (max-width:768px) {
            .resumen-grid, .resumen-grid-2 { grid-template-columns:1fr; }
            .tab-item { padding:8px 12px; font-size:12px; }
        }
    </style>

    {{-- FILTROS --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><span class="card-title">Período y alcance</span></div>

        <form method="GET" action="{{ route('admin.reportes.index') }}" style="padding:16px 20px;">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="filters" style="margin-bottom:12px;">
                @if ($esSuperadmin)
                    <input type="text" name="empresa" list="dl-empresas" autocomplete="off"
                           placeholder="Todas las empresas" value="{{ $filtros['empresa'] ?? '' }}">
                    <datalist id="dl-empresas">
                        @foreach ($empresas as $nombre)
                            <option value="{{ $nombre }}">
                        @endforeach
                    </datalist>
                @endif

                <input type="text" name="sucursal" list="dl-sucursales" autocomplete="off"
                       placeholder="Todas las sucursales" value="{{ $filtros['sucursal'] ?? '' }}">
                <datalist id="dl-sucursales">
                    @foreach ($sucursales as $nombre)
                        <option value="{{ $nombre }}">
                    @endforeach
                </datalist>

                <select name="fecha" id="filtro-fecha">
                    <option value="hoy" @selected(($filtros['fecha'] ?? '') === 'hoy')>Hoy</option>
                    <option value="semana" @selected(($filtros['fecha'] ?? '') === 'semana')>Última semana</option>
                    <option value="mes" @selected(($filtros['fecha'] ?? '') === 'mes')>Último mes</option>
                    <option value="personalizado" @selected(($filtros['fecha'] ?? '') === 'personalizado')>Personalizado</option>
                </select>
            </div>

            <div id="rango-personalizado"
                 style="display:{{ ($filtros['fecha'] ?? '') === 'personalizado' ? 'flex' : 'none' }};gap:10px;margin-bottom:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <label style="font-size:13px;font-weight:600;">Desde</label>
                    <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}">
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <label style="font-size:13px;font-weight:600;">Hasta</label>
                    <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}">
                </div>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Aplicar</button>
                <a href="{{ route('admin.reportes.index') }}" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="tabs">
        <a href="{{ $urlTab('1') }}" class="tab-item {{ $tab === '1' ? 'active' : '' }}">1. Resumen general</a>
        <a href="{{ $urlTab('2') }}" class="tab-item {{ $tab === '2' ? 'active' : '' }}">2. Por categoría</a>
        <a href="{{ $urlTab('3') }}" class="tab-item {{ $tab === '3' ? 'active' : '' }}">3. Por sucursal</a>
        <a href="{{ $urlTab('4') }}" class="tab-item {{ $tab === '4' ? 'active' : '' }}">4. Tiempos de respuesta</a>
        <a href="{{ $urlTab('5') }}" class="tab-item {{ $tab === '5' ? 'active' : '' }}">5. Desempeño del equipo</a>
        <a href="{{ $urlTab('6') }}" class="tab-item {{ $tab === '6' ? 'active' : '' }}">6. Tendencias</a>
    </div>

    {{-- ══ TAB 1: RESUMEN ══ --}}
    @if ($tab === '1')

        <div class="resumen-grid">
            <a href="{{ $urlListado() }}" class="reporte-card">
                <div class="label">Total del período</div>
                <div class="valor">{{ $total }}</div>
            </a>

            <a href="{{ $urlListado(['estado' => 'in_progress']) }}" class="reporte-card">
                <div class="label">En progreso</div>
                <div class="valor">{{ $enProgreso }}</div>
            </a>

            <a href="{{ $urlListado(['estado' => 'resolved']) }}" class="reporte-card">
                <div class="label">Resueltas</div>
                <div class="valor" style="color:#28a745;">{{ $resueltas }}</div>
                <div class="nota">Tasa de resolución: {{ $tasaResolucion }}%</div>
            </a>
        </div>

        <div class="resumen-grid" style="margin-bottom:16px;">
            <a href="{{ $urlListado(['estado' => 'new']) }}" class="reporte-card">
                <div class="label">Nuevas</div>
                <div class="valor">{{ $nuevas }}</div>
            </a>

            <a href="{{ $urlListado(['estado' => 'seen']) }}" class="reporte-card">
                <div class="label">Vistas</div>
                <div class="valor">{{ $vistas }}</div>
            </a>

            <a href="{{ $urlListado(['estado' => 'closed']) }}" class="reporte-card">
                <div class="label">Cerradas</div>
                <div class="valor">{{ $cerradas }}</div>
            </a>
        </div>

        <div class="resumen-grid-2">
            <a href="{{ $urlListado(['estado' => 'new']) }}" class="reporte-card">
                <div class="label">Plazo incumplido</div>
                <div class="valor" style="color:{{ $plazoIncumplido ? '#b00020' : '#28a745' }};">
                    {{ $plazoIncumplido }}
                </div>
                @if ($plazoIncumplido)
                    <div class="alerta">
                        {{ $plazoIncumplidoPct }}% del período · más de {{ $horasPlazo }} h sin abrirse
                    </div>
                @else
                    <div class="nota">Todas fueron abiertas dentro de las {{ $horasPlazo }} h</div>
                @endif
            </a>

            <div class="reporte-card">
                <div class="label">Casos finalizados</div>
                <div class="valor">{{ $finalizadas }}</div>
                <div class="nota">Resueltas + cerradas en el período</div>
            </div>
        </div>

    {{-- ══ TAB 2: CATEGORÍAS ══ --}}
    @elseif ($tab === '2')

        <div class="card">
            <div class="table-wrapper">
                @if ($porCategoria->isEmpty())
                    <div class="empty-state">
                        <div class="icon">📊</div>
                        <p>Sin datos para el período seleccionado.</p>
                    </div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Volumen</th>
                                <th>% del total</th>
                                <th style="width:30%;">Peso</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($porCategoria as $fila)
                                <tr>
                                    <td>{{ $fila['nombre'] }}</td>
                                    <td><strong>{{ $fila['volumen'] }}</strong></td>
                                    <td>{{ $fila['porcentaje'] }}%</td>
                                    <td>
                                        {{-- Barra proporcional en lugar de la columna
                                             "Evolución" del original, que mostraba flechas
                                             de tendencia calculadas desde el mismo
                                             porcentaje: no comparaba con ningún período
                                             anterior, así que la flecha no significaba nada. --}}
                                        <div class="barra-fondo">
                                            <div class="barra-relleno" style="width:{{ $fila['porcentaje'] }}%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

    {{-- ══ TAB 3: SUCURSALES ══ --}}
    @elseif ($tab === '3')

        <div class="card">
            <div class="table-wrapper">
                @if ($porSucursal->isEmpty())
                    <div class="empty-state">
                        <div class="icon">📊</div>
                        <p>Sin datos para el período seleccionado.</p>
                    </div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th>Casos</th>
                                <th>% del total</th>
                                <th style="width:30%;">Peso</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($porSucursal as $fila)
                                <tr>
                                    <td>{{ $fila['nombre'] }}</td>
                                    <td><strong>{{ $fila['casos'] }}</strong></td>
                                    <td>{{ $fila['porcentaje'] }}%</td>
                                    <td>
                                        <div class="barra-fondo">
                                            <div class="barra-relleno" style="width:{{ $fila['porcentaje'] }}%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

    {{-- ══ TAB 4: TIEMPOS ══ --}}
    @elseif ($tab === '4')

        <div class="resumen-grid">
            <div class="reporte-card">
                <div class="label">Tiempo medio de resolución</div>
                <div class="valor">
                    @if ($promedioDias !== null)
                        {{ $promedioDias }}<span style="font-size:18px;"> días</span>
                    @else
                        —
                    @endif
                </div>
                <div class="nota">
                    @if ($conResolucion)
                        Sobre {{ $conResolucion }} {{ \Illuminate\Support\Str::plural('caso', $conResolucion) }}
                        con fecha de resolución
                    @else
                        Todavía no hay casos resueltos en el período
                    @endif
                </div>
            </div>

            <div class="reporte-card">
                <div class="label">Tiempo hasta la primera lectura</div>
                <div class="valor">
                    @if ($primeraLecturaHoras !== null)
                        {{ $primeraLecturaHoras }}<span style="font-size:18px;"> h</span>
                    @else
                        —
                    @endif
                </div>
                <div class="nota">Desde el ingreso hasta que alguien abre el caso</div>
            </div>

            <div class="reporte-card">
                <div class="label">Plazo incumplido</div>
                <div class="valor" style="color:{{ $plazoIncumplido ? '#b00020' : '#28a745' }};">
                    {{ $plazoIncumplidoPct }}%
                </div>
                <div class="nota">
                    {{ $plazoIncumplido }} {{ \Illuminate\Support\Str::plural('caso', $plazoIncumplido) }}
                    con más de {{ $horasPlazo }} h sin abrirse
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:20px;">
            <div style="padding:16px 20px;font-size:13px;color:var(--text-light);line-height:1.6;">
                El tiempo de resolución se calcula desde el ingreso hasta la fecha en que
                el caso pasó a <strong>Resuelta</strong>, no hasta la última modificación.
                Un caso resuelto rápido que después recibe un cambio de prioridad
                sigue contando como resuelto rápido.
            </div>
        </div>

    {{-- ══ TAB 5: EQUIPO ══ --}}
    @elseif ($tab === '5')

        @php
            $internos = $equipo->where('esExterno', false);
            $externos = $equipo->where('esExterno', true);
        @endphp

        @foreach ([['Equipo interno', $internos], ['Investigadores externos', $externos]] as [$titulo, $grupo])
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><span class="card-title">{{ $titulo }}</span></div>

                <div class="table-wrapper">
                    @if ($grupo->isEmpty())
                        <div class="empty-state">
                            <div class="icon">👥</div>
                            <p>Sin casos finalizados en el período.</p>
                        </div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th>Analista</th>
                                    <th>Rol</th>
                                    <th>Casos finalizados</th>
                                    <th>Días promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grupo as $fila)
                                    <tr>
                                        <td>{{ $fila['nombre'] }}</td>
                                        <td>{{ $fila['rol'] }}</td>
                                        <td><strong>{{ $fila['casos'] }}</strong></td>
                                        <td>{{ $fila['diasPromedio'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="card">
            <div style="padding:16px 20px;font-size:13px;color:var(--text-light);line-height:1.6;">
                Un caso con dos analistas asignados suma para los dos: ambos
                trabajaron en él. El reporte anterior contaba solo un asignado por
                caso, así que el segundo no aparecía en ningún lado.
            </div>
        </div>

    {{-- ══ TAB 6: TENDENCIAS ══ --}}
    @elseif ($tab === '6')

        <div class="card">
            <div class="card-header">
                <span class="card-title">Últimos 12 meses</span>
                <span style="font-size:12px;color:var(--text-light);">
                    No usa el filtro de período
                </span>
            </div>

            <div class="table-wrapper">
                @if ($tendencias->isEmpty())
                    <div class="empty-state">
                        <div class="icon">📈</div>
                        <p>Sin datos históricos todavía.</p>
                    </div>
                @else
                    @php $maximo = max($tendencias->max('ingresadas'), 1); @endphp

                    <table>
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th>Ingresadas</th>
                                <th>Finalizadas</th>
                                <th style="width:35%;">Volumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tendencias as $fila)
                                <tr>
                                    <td style="text-transform:capitalize;">{{ $fila['etiqueta'] }}</td>
                                    <td><strong>{{ $fila['ingresadas'] }}</strong></td>
                                    <td>{{ $fila['finalizadas'] }}</td>
                                    <td>
                                        <div class="barra-fondo">
                                            <div class="barra-relleno"
                                                 style="width:{{ round($fila['ingresadas'] / $maximo * 100) }}%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

    @endif

    <script>
        document.getElementById('filtro-fecha').addEventListener('change', function () {
            document.getElementById('rango-personalizado').style.display =
                this.value === 'personalizado' ? 'flex' : 'none';
        });
    </script>

</x-admin.layout>
