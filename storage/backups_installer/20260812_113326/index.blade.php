<x-admin.layout titulo="Onboarding">

    <style>
        .ob-badge {
            display:inline-block; padding:3px 10px; border-radius:999px;
            font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
        }
        .ob-enviado     { background:#e8f0fe; color:#1a5fb4; }
        .ob-en_curso    { background:#fff3cd; color:#856404; }
        .ob-por_revisar { background:var(--gold); color:var(--dark); }
        .ob-confirmado  { background:#d4edda; color:#155724; }
        .ob-vencido,
        .ob-revocado    { background:#f0f0f0; color:#777; }

        .ob-link {
            display:flex; gap:8px; align-items:center; margin-top:12px;
            padding:12px 14px; background:#fffbe6; border:1px solid var(--gold);
            border-radius:10px; flex-wrap:wrap;
        }
        .ob-link input {
            flex:1; min-width:240px; padding:8px 12px; border:1px solid var(--border);
            border-radius:8px; font-size:12px; font-family:monospace; background:#fff;
        }
        .revision-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
        .campo { display:flex; flex-direction:column; gap:5px; }
        .campo label { font-size:12px; font-weight:700; color:var(--text-light); text-transform:uppercase; letter-spacing:.04em; }
        .campo input {
            padding:9px 12px; border:1px solid var(--border); border-radius:8px;
            font-size:13px; font-family:inherit; outline:none;
        }
        .campo input:focus { border-color:var(--gold); }
        @media (max-width:768px) { .revision-grid { grid-template-columns:1fr; } }
    </style>

    {{-- El token en claro se muestra UNA sola vez, al generarlo. --}}
    @if (session('token_nuevo'))
        @php $url = url('/onboarding/' . session('token_nuevo')); @endphp

        <div class="card" style="margin-bottom:20px;border:2px solid var(--gold);">
            <div class="card-header"><span class="card-title">Link generado</span></div>
            <div style="padding:16px 20px;">
                <p style="font-size:13px;color:var(--text-light);margin-bottom:4px;">
                    Copiálo ahora y envialo a la empresa. Por seguridad no vuelve a mostrarse.
                </p>
                <div class="ob-link">
                    <input type="text" id="url-onboarding" value="{{ $url }}" readonly onclick="this.select()">
                    <button type="button" class="btn btn-primary btn-sm" onclick="copiarLink(this)">Copiar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Revisión de un alta ── --}}
    @if ($revisar && $revisar->empresa)
        @php $borrador = $revisar->empresa; @endphp

        <div class="card" style="margin-bottom:20px;border:2px solid var(--gold);">
            <div class="card-header">
                <span class="card-title">Revisar alta — {{ $borrador->name }}</span>
                <a href="{{ route('admin.onboarding.index') }}" class="btn btn-secondary btn-sm">Cerrar</a>
            </div>

            <form method="POST" action="{{ route('admin.onboarding.confirmar', $revisar) }}"
                  style="padding:20px;">
                @csrf

                <p style="font-size:13px;color:var(--text-light);margin-bottom:18px;">
                    Podés corregir los datos antes de confirmar. Al confirmar se crean la
                    empresa, sus sucursales y sus usuarios, y el borrador se elimina.
                </p>

                <div class="revision-grid" style="margin-bottom:20px;">
                    <div class="campo">
                        <label for="name">Nombre</label>
                        <input type="text" id="name" name="name" value="{{ $borrador->name }}">
                    </div>
                    <div class="campo">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ $borrador->email }}">
                    </div>
                    <div class="campo">
                        <label for="slug">Slug del canal</label>
                        <input type="text" id="slug" name="slug" value="{{ $borrador->slug }}"
                               pattern="[a-z0-9\-]+">
                        <span style="font-size:11px;color:var(--text-light);">
                            Última oportunidad de cambiarlo: después queda fijo.
                        </span>
                    </div>
                    <div class="campo">
                        <label for="timezone">Zona horaria</label>
                        <input type="text" id="timezone" name="timezone" value="{{ $borrador->timezone }}">
                    </div>
                    <div class="campo">
                        <label for="complaints_retention_days">Retención denuncias (días)</label>
                        <input type="number" id="complaints_retention_days" name="complaints_retention_days"
                               value="{{ $borrador->retention_complaints_days }}" min="1">
                    </div>
                    <div class="campo">
                        <label for="files_retention_days">Retención archivos (días)</label>
                        <input type="number" id="files_retention_days" name="files_retention_days"
                               value="{{ $borrador->retention_files_days }}" min="1">
                    </div>
                    <div class="campo">
                        <label for="logs_retention_days">Retención logs (días)</label>
                        <input type="number" id="logs_retention_days" name="logs_retention_days"
                               value="{{ $borrador->retention_logs_days }}" min="1">
                    </div>
                </div>

                <h4 style="font-size:13px;font-weight:700;margin-bottom:10px;">
                    Sucursales ({{ $borrador->sucursales->count() }})
                </h4>
                <div class="table-wrapper" style="margin-bottom:20px;">
                    <table>
                        <thead>
                            <tr><th>Nombre</th><th>Dirección</th><th>Sede central</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($borrador->sucursales as $sucursal)
                                <tr>
                                    <td>{{ $sucursal->name }}</td>
                                    <td>{{ $sucursal->address ?? '—' }}</td>
                                    <td>{{ $sucursal->is_headquarter ? '★' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h4 style="font-size:13px;font-weight:700;margin-bottom:10px;">
                    Usuarios ({{ $borrador->usuarios->count() }})
                </h4>
                <div class="table-wrapper" style="margin-bottom:20px;">
                    <table>
                        <thead>
                            <tr><th>Nombre</th><th>Email</th><th>Rol</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($borrador->usuarios as $usuarioBorrador)
                                <tr>
                                    <td>{{ $usuarioBorrador->first_name }} {{ $usuarioBorrador->last_name }}</td>
                                    <td>{{ $usuarioBorrador->email }}</td>
                                    <td>
                                        {{ \App\Enums\RolUsuario::tryFrom($usuarioBorrador->role)?->etiqueta()
                                            ?? $usuarioBorrador->role }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="padding:12px 14px;background:#f0f7f0;border:1px solid #c3e6cb;border-radius:8px;font-size:12px;color:#155724;margin-bottom:16px;">
                    Los usuarios entran con la contraseña que cargaron en el formulario, y el
                    sistema les va a pedir cambiarla en el primer ingreso.
                </div>

                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('¿Confirmar el alta de {{ $borrador->name }}?');">
                    Confirmar alta
                </button>
            </form>
        </div>
    @endif

    {{-- ── Listado de links ── --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Links de invitación</span>
            <form method="POST" action="{{ route('admin.onboarding.generar') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">+ Generar link</button>
            </form>
        </div>

        <div class="table-wrapper">
            @if ($tokens->isEmpty())
                <div class="empty-state">
                    <div class="icon">🔗</div>
                    <p>No hay links generados todavía.</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Generado</th>
                            <th>Por</th>
                            <th>Vence</th>
                            <th>Situación</th>
                            <th>Empresa</th>
                            <th>Contenido</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $etiquetas = [
                                'enviado' => 'Enviado',
                                'en_curso' => 'Completando',
                                'por_revisar' => 'Por revisar',
                                'confirmado' => 'Alta confirmada',
                                'vencido' => 'Vencido',
                                'revocado' => 'Revocado',
                            ];
                        @endphp

                        @foreach ($tokens as $token)
                            <tr>
                                <td>{{ $token->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $token->creadoPor?->nombreCompleto() ?? '—' }}</td>
                                <td>{{ $token->expires_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="ob-badge ob-{{ $token->situacion }}">
                                        {{ $etiquetas[$token->situacion] }}
                                    </span>
                                </td>
                                <td>{{ $token->empresa?->name ?? '—' }}</td>
                                <td style="font-size:12px;color:var(--text-light);">
                                    @if ($token->empresa)
                                        {{ $token->sucursales_count }} suc. · {{ $token->usuarios_count }} usr.
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        @if ($token->situacion === 'por_revisar')
                                            <a href="{{ route('admin.onboarding.index', ['revisar' => $token->id]) }}"
                                               class="btn btn-primary btn-sm">Revisar</a>
                                        @endif

                                        @if (in_array($token->situacion, ['enviado', 'en_curso'], true))
                                            <form method="POST"
                                                  action="{{ route('admin.onboarding.revocar', $token) }}"
                                                  onsubmit="return confirm('¿Revocar este link? Deja de funcionar de inmediato.');">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary btn-sm">Revocar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <script>
        function copiarLink(boton) {
            const campo = document.getElementById('url-onboarding');
            campo.select();
            navigator.clipboard.writeText(campo.value).then(() => {
                const texto = boton.textContent;
                boton.textContent = '✓ Copiado';
                setTimeout(() => { boton.textContent = texto; }, 1800);
            });
        }
    </script>

</x-admin.layout>
