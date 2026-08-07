<x-admin.layout titulo="Inicio">

    @if ($widgets)
        <div style="margin-bottom:28px;">
            <h3 style="font-size:15px;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.06em;margin-bottom:16px;">
                Vista global de la plataforma
            </h3>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:16px;">

                <a href="{{ route('admin.administracion.index', ['tab' => 'empresas', 'status' => 'active']) }}"
                   class="stat-card" style="text-decoration:none;">
                    <span class="stat-label">Empresas activas</span>
                    <span class="stat-value" style="color:var(--gold-dark);">{{ $widgets['empresas_activas'] }}</span>
                    <span class="stat-sub">
                        @if ($widgets['empresas_suspendidas'])
                            <span style="color:#856404;">
                                ⏸ {{ $widgets['empresas_suspendidas'] }}
                                {{ \Illuminate\Support\Str::plural('suspendida', $widgets['empresas_suspendidas']) }}
                            </span>
                        @endif
                        @if ($widgets['empresas_inactivas'])
                            <span style="color:#b00020;">
                                🚫 {{ $widgets['empresas_inactivas'] }}
                                {{ \Illuminate\Support\Str::plural('inactiva', $widgets['empresas_inactivas']) }}
                            </span>
                        @endif
                        @unless ($widgets['empresas_suspendidas'] + $widgets['empresas_inactivas'])
                            Todas operativas
                        @endunless
                    </span>
                </a>

                <a href="{{ route('admin.facturacion.index', ['status' => 'overdue']) }}"
                   class="stat-card" style="text-decoration:none;">
                    <span class="stat-label">Facturas vencidas</span>
                    <span class="stat-value" style="color:{{ $widgets['facturas_vencidas'] ? '#b00020' : '#28a745' }};">
                        {{ $widgets['facturas_vencidas'] }}
                    </span>
                    <span class="stat-sub">
                        {{ $widgets['facturas_vencidas'] ? '⚠ Requieren atención' : '✓ Sin vencidas' }}
                    </span>
                </a>

                <a href="{{ route('admin.facturacion.index', ['status' => 'pending', 'vence_pronto' => 1]) }}"
                   class="stat-card" style="text-decoration:none;">
                    <span class="stat-label">Próximas a vencer</span>
                    <span class="stat-value" style="color:{{ $widgets['facturas_proximas'] ? '#856404' : '#28a745' }};">
                        {{ $widgets['facturas_proximas'] }}
                    </span>
                    <span class="stat-sub">Vencen en los próximos 7 días</span>
                </a>

            </div>

            @if ($widgets['empresas_sin_actividad']->isNotEmpty())
                <div class="card" style="margin-bottom:0;">
                    <div class="card-header">
                        <span class="card-title">
                            🔇 Empresas sin denuncias en los últimos 30 días
                            <span style="font-size:12px;font-weight:400;color:var(--text-light);margin-left:8px;">
                                ({{ $widgets['empresas_sin_actividad']->count() }}
                                {{ \Illuminate\Support\Str::plural('empresa', $widgets['empresas_sin_actividad']->count()) }})
                            </span>
                        </span>
                    </div>
                    <div style="padding:0 24px 20px;display:flex;flex-wrap:wrap;gap:8px;">
                        @foreach ($widgets['empresas_sin_actividad'] as $empresa)
                            <a href="{{ route('admin.denuncias.index', ['empresa' => $empresa->name]) }}"
                               style="display:inline-block;padding:6px 14px;background:#f5f5f5;border:1px solid var(--border);border-radius:999px;font-size:13px;color:var(--dark);">
                                {{ $empresa->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <div style="padding:12px 16px;background:#d4edda;border:1px solid #c3e6cb;border-radius:10px;font-size:13px;color:#155724;font-weight:600;">
                    ✓ Todas las empresas activas tuvieron actividad en los últimos 30 días.
                </div>
            @endif
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin-bottom:24px;">
    @endif

    <div class="stats-grid">

        <div class="stat-card">
            <span class="stat-label">Total denuncias</span>
            <span class="stat-value">{{ $total }}</span>
            <span class="stat-sub">Desde el inicio</span>
        </div>

        <div class="stat-card">
            <span class="stat-label">Nuevas sin asignar</span>
            <span class="stat-value" style="color:#BFAE76">{{ $sinAsignar }}</span>
            <span class="stat-sub">Requieren atención</span>
        </div>

        <div class="stat-card">
            <span class="stat-label">En progreso</span>
            <span class="stat-value">{{ $enProgreso }}</span>
            <span class="stat-sub">Siendo investigadas</span>
        </div>

        <div class="stat-card">
            <span class="stat-label">Resueltas</span>
            <span class="stat-value" style="color:#28a745">{{ $resueltas }}</span>
            <span class="stat-sub">Cerradas con resolución</span>
        </div>

        @if ($misAsignadas !== null)
            <div class="stat-card">
                <span class="stat-label">Mis asignadas</span>
                <span class="stat-value">{{ $misAsignadas }}</span>
                <span class="stat-sub">Activas en mi bandeja</span>
            </div>
        @endif

    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                {{ $esOperador ? 'Mis denuncias asignadas' : 'Denuncias recientes' }}
            </span>
            <a href="{{ route('admin.denuncias.index') }}" class="btn btn-secondary btn-sm">Ver todas →</a>
        </div>

        <div class="table-wrapper">
            @if ($recientes->isEmpty())
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <p>No hay denuncias registradas todavía.</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Empresa</th>
                            <th>Categoría</th>
                            <th>Fecha hecho</th>
                            <th>Estado</th>
                            <th>Asignado a</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recientes as $denuncia)
                            <tr class="fila-link tabla-denuncias"
                                onclick="window.location='{{ route('admin.denuncias.show', $denuncia) }}'">
                                <td><strong>{{ $denuncia->internal_code }}</strong></td>
                                <td>{{ $denuncia->empresa->name }}</td>
                                <td>{{ $denuncia->categoria?->name_es ?? '—' }}</td>
                                <td>{{ $denuncia->incident_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-{{ $denuncia->status->value }}">
                                        {{ $denuncia->status->etiqueta() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($denuncia->tiene_asignado)
                                        <span class="badge-activo">Asignado</span>
                                    @else
                                        <span style="color:#aaa">Sin asignar</span>
                                    @endif
                                </td>
                                <td class="col-accion-ver">
                                    <a href="{{ route('admin.denuncias.show', $denuncia) }}"
                                       class="btn btn-primary btn-sm">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</x-admin.layout>
