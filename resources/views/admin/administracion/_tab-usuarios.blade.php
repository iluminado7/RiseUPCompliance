<div class="card">
    <div class="card-header">
        <span class="card-title">
            {{ $usuarios->count() }} {{ \Illuminate\Support\Str::plural('usuario', $usuarios->count()) }}
        </span>
        <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary btn-sm">+ Nuevo usuario</a>
    </div>

    <div class="table-wrapper">
        @if ($usuarios->isEmpty())
            <div class="empty-state">
                <div class="icon">👥</div>
                <p>No hay usuarios registrados.</p>
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        @if ($esSuperadmin)
                            <th>Empresa</th>
                        @endif
                        <th>Sucursales</th>
                        <th>2FA</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        @php
                            [$claseEstado, $etiquetaEstado] = match ($usuario->status->value) {
                                'active' => ['badge-activo', 'ACTIVO'],
                                'suspended' => ['badge-suspendido', 'SUSPENDIDO'],
                                default => ['badge-inactivo', 'INACTIVO'],
                            };
                        @endphp

                        <tr>
                            <td><strong>{{ $usuario->nombreCompleto() }}</strong></td>
                            <td>{{ $usuario->email }}</td>
                            <td>{{ $usuario->nombreRol()?->etiqueta() ?? '—' }}</td>
                            @if ($esSuperadmin)
                                <td>
                                    {{ $usuario->empresa?->name ?? '' }}
                                    @unless ($usuario->empresa)
                                        <span style="color:var(--gold-dark);font-weight:600;">Plataforma</span>
                                    @endunless
                                </td>
                            @endif
                            <td style="font-size:12px;color:var(--text-light);">
                                {{ $usuario->sucursales->pluck('name')->join(', ') ?: '—' }}
                            </td>
                            <td>{{ $usuario->two_factor_enabled ? '🔐' : '—' }}</td>
                            <td><span class="{{ $claseEstado }}">{{ $etiquetaEstado }}</span></td>
                            <td class="col-accion-ver">
                                @can('update', $usuario)
                                    <a href="{{ route('admin.usuarios.edit', $usuario) }}"
                                       class="btn btn-primary btn-sm">Editar</a>
                                @else
                                    <span style="font-size:11px;color:#ccc;" title="No podés editar este usuario">—</span>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="card" style="margin-top:16px;">
    <div style="padding:14px 20px;font-size:12px;color:var(--text-light);line-height:1.6;">
        Tu propio usuario no aparece como editable: cambiarse el rol o el estado
        a sí mismo es escalada de privilegios o autobloqueo. El perfil propio se
        edita desde <a href="{{ route('admin.perfil') }}">Mi perfil</a>.
    </div>
</div>
