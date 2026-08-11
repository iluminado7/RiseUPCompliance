<x-admin.layout titulo="Logs de actividad">

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">Filtros</span>
        </div>

        <form method="GET" action="{{ route('admin.logs.index') }}" style="padding:16px 20px;">
            <div class="filters" style="margin-bottom:12px;">

                <x-autocompletado
                    nombre="empresa"
                    :opciones="$empresas"
                    :valor="$filtros['empresa'] ?? ''"
                    placeholder="Empresa..." />

                <input type="text" name="usuario" placeholder="Usuario o email..."
                       value="{{ $filtros['usuario'] ?? '' }}">

                <input type="text" name="accion" placeholder="Acción (ej: user.login)"
                       value="{{ $filtros['accion'] ?? '' }}">

                <input type="text" name="denuncia" placeholder="Código de denuncia"
                       value="{{ $filtros['denuncia'] ?? '' }}">

                <select name="resultado">
                    <option value="">Todos los resultados</option>
                    <option value="success" @selected(($filtros['resultado'] ?? null) === 'success')>Éxito</option>
                    <option value="error" @selected(($filtros['resultado'] ?? null) === 'error')>Error</option>
                    <option value="blocked" @selected(($filtros['resultado'] ?? null) === 'blocked')>Bloqueado</option>
                </select>

                <select name="origen">
                    <option value="">Todos los orígenes</option>
                    <option value="web" @selected(($filtros['origen'] ?? null) === 'web')>Web</option>
                    <option value="api" @selected(($filtros['origen'] ?? null) === 'api')>API</option>
                    <option value="system" @selected(($filtros['origen'] ?? null) === 'system')>Sistema</option>
                    <option value="cron" @selected(($filtros['origen'] ?? null) === 'cron')>Cron</option>
                </select>

                <select name="cadena">
                    <option value="">Todas las cadenas</option>
                    <option value="plataforma" @selected(($filtros['cadena'] ?? null) === 'plataforma')>
                        Solo plataforma
                    </option>
                </select>

            </div>

            <div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap;">
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
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Verificacion de integridad --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">Integridad de la cadena</span>
        </div>
        <div style="padding:16px 20px;font-size:13px;color:var(--text-light);line-height:1.6;">
            <p style="margin-bottom:10px;">
                La verificación se corre por línea de comando, no desde acá. Recorre
                todos los registros recalculando su hash y comprobando el
                encadenamiento, lo que sobre un volumen grande no es una operación
                para resolver dentro de un request web.
            </p>
            <code style="display:block;background:#f5f5f5;padding:10px 14px;border-radius:8px;color:var(--dark);">
                php artisan auditoria:verificar --detalle
            </code>
            <p style="margin-top:10px;">
                Devuelve código de salida 0 si la cadena está íntegra, así que puede
                correrse desde el scheduler y alertar automáticamente.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                @if ($registros->total())
                    Mostrando {{ $registros->firstItem() }}–{{ $registros->lastItem() }}
                    de {{ $registros->total() }} registros
                @else
                    Sin registros
                @endif
            </span>
            <span style="font-size:12px;color:var(--text-light);">
                Append-only · nunca se modifican ni eliminan
            </span>
        </div>

        <div class="table-wrapper">
            @if ($registros->isEmpty())
                <div class="empty-state">
                    <div class="icon">📋</div>
                    <p>No hay registros con los filtros aplicados.</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cadena</th>
                            <th>#</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Denuncia</th>
                            <th>Resultado</th>
                            <th>Origen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($registros as $registro)
                            @php
                                $iconosOrigen = ['web' => '🌐', 'api' => '⚡', 'system' => '⚙️', 'cron' => '🕐'];
                                $claseResultado = match ($registro->result) {
                                    'success' => 'badge-activo',
                                    'error' => 'badge-inactivo',
                                    'blocked' => 'badge-suspendido',
                                    default => 'badge-inactivo',
                                };
                                $etiquetaResultado = match ($registro->result) {
                                    'success' => 'Éxito',
                                    'error' => 'Error',
                                    'blocked' => 'Bloqueado',
                                    default => $registro->result,
                                };
                                $detalle = $detalles[$registro->id] ?? null;
                            @endphp

                            <tr @if ($detalle) title="{{ $detalle }}" @endif>
                                <td style="white-space:nowrap;">
                                    {{ $registro->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td>
                                    @if ($registro->company_id)
                                        {{ $registro->empresa?->name ?? '—' }}
                                    @else
                                        <span style="color:var(--gold-dark);font-weight:600;">Plataforma</span>
                                    @endif
                                </td>
                                <td style="color:#aaa;font-variant-numeric:tabular-nums;">
                                    {{ $registro->company_sequence }}
                                </td>
                                <td>
                                    @if ($registro->usuario)
                                        {{ $registro->usuario->nombreCompleto() }}
                                    @else
                                        <span style="color:#aaa">Sistema</span>
                                    @endif
                                </td>
                                <td><code style="font-size:12px;">{{ $registro->action }}</code></td>
                                <td>
                                    @if ($registro->affected_complaint_id)
                                        <a href="{{ route('admin.denuncias.show', $registro->affected_complaint_id) }}">
                                            Ver caso
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><span class="{{ $claseResultado }}">{{ $etiquetaResultado }}</span></td>
                                <td style="white-space:nowrap;">
                                    {{ $iconosOrigen[$registro->origin] ?? '' }}
                                    {{ strtoupper($registro->origin) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if ($registros->hasPages())
            <div class="pagination">
                @if ($registros->currentPage() > 1)
                    <a href="{{ $registros->previousPageUrl() }}">‹</a>
                @endif

                @foreach (range(
                    max(1, $registros->currentPage() - 2),
                    min($registros->lastPage(), $registros->currentPage() + 2)
                ) as $numero)
                    @if ($numero === $registros->currentPage())
                        <span class="current">{{ $numero }}</span>
                    @else
                        <a href="{{ $registros->url($numero) }}">{{ $numero }}</a>
                    @endif
                @endforeach

                @if ($registros->currentPage() < $registros->lastPage())
                    <a href="{{ $registros->nextPageUrl() }}">›</a>
                @endif
            </div>
        @endif
    </div>

</x-admin.layout>
