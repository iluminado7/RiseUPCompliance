<x-admin.layout titulo="Mi perfil">

    @php
        $urlTab = fn (string $t) => route('admin.perfil', ['tab' => $t]);
    @endphp

    <style>
        .pf-tabs { display:flex; border-bottom:2px solid var(--border); margin-bottom:20px; flex-wrap:wrap; }
        .pf-tab {
            padding:10px 18px; font-size:13px; font-weight:600; color:var(--text-light);
            border-bottom:2px solid transparent; margin-bottom:-2px; text-decoration:none;
            white-space:nowrap; transition:color .15s;
        }
        .pf-tab:hover { color:var(--dark); }
        .pf-tab.active { color:var(--gold-dark); border-bottom-color:var(--gold); }

        .pf-cabecera {
            background:var(--white); border-radius:var(--radius); padding:24px;
            box-shadow:var(--shadow); margin-bottom:20px;
            display:flex; gap:20px; align-items:center; flex-wrap:wrap;
        }
        .pf-avatar {
            width:64px; height:64px; border-radius:50%; background:var(--gold);
            display:flex; align-items:center; justify-content:center;
            font-size:24px; font-weight:700; color:var(--dark); flex-shrink:0;
        }
        .pf-datos strong { display:block; font-size:17px; color:var(--dark); }
        .pf-datos span { font-size:13px; color:var(--text-light); }

        .pf-form { background:var(--white); border-radius:var(--radius); padding:24px; box-shadow:var(--shadow); }
        .pf-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-bottom:18px; }
        .pf-grid .ancho { grid-column:1 / -1; }
        .campo { display:flex; flex-direction:column; gap:5px; }
        .campo label { font-size:12px; font-weight:700; color:var(--text-light); text-transform:uppercase; letter-spacing:.04em; }
        .campo input, .campo select {
            padding:9px 12px; border:1px solid var(--border); border-radius:8px;
            font-size:13px; font-family:inherit; outline:none;
        }
        .campo input:focus, .campo select:focus { border-color:var(--gold); }
        .campo input[readonly] { background:#f5f5f5; color:var(--text-light); }
        .campo .nota { font-size:11px; color:var(--text-light); }

        .pf-estado {
            display:flex; align-items:center; gap:14px; padding:16px 20px;
            border-radius:10px; margin-bottom:20px;
        }
        .pf-estado.activo { background:#d4edda; border:1px solid #c3e6cb; }
        .pf-estado.inactivo { background:#fff3cd; border:1px solid #ffeeba; }
        .pf-estado .icono { font-size:24px; }
        .pf-estado strong { display:block; font-size:14px; color:var(--dark); }
        .pf-estado span { font-size:12px; color:#666; }

        @media (max-width:768px) { .pf-grid { grid-template-columns:1fr; } }
    </style>

    <div class="pf-cabecera">
        <div class="pf-avatar">
            {{ mb_strtoupper(mb_substr($usuario->first_name, 0, 1) . mb_substr($usuario->last_name, 0, 1)) }}
        </div>
        <div class="pf-datos">
            <strong>{{ $usuario->nombreCompleto() }}</strong>
            <span>
                {{ $usuario->nombreRol()?->etiqueta() }}
                @if ($usuario->empresa)
                    · {{ $usuario->empresa->name }}
                @else
                    · Plataforma
                @endif
                · Desde {{ $usuario->created_at->format('m/Y') }}
            </span>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-error">
            @foreach ($errors->all() as $mensaje)
                <div>{{ $mensaje }}</div>
            @endforeach
        </div>
    @endif

    <div class="pf-tabs">
        <a href="{{ $urlTab('datos') }}" class="pf-tab {{ $tab === 'datos' ? 'active' : '' }}">Datos personales</a>
        <a href="{{ $urlTab('password') }}" class="pf-tab {{ $tab === 'password' ? 'active' : '' }}">Contraseña</a>
        <a href="{{ $urlTab('2fa') }}" class="pf-tab {{ $tab === '2fa' ? 'active' : '' }}">Verificación en dos pasos</a>
        <a href="{{ $urlTab('preferencias') }}" class="pf-tab {{ $tab === 'preferencias' ? 'active' : '' }}">Preferencias</a>
    </div>

    {{-- ══ DATOS PERSONALES ══ --}}
    @if ($tab === 'datos')
        <div class="pf-form">
            <form method="POST" action="{{ route('admin.perfil.datos') }}">
                @csrf
                @method('PUT')

                <div class="pf-grid">
                    <div class="campo">
                        <label for="first_name">Nombre *</label>
                        <input type="text" id="first_name" name="first_name" maxlength="100" required
                               value="{{ old('first_name', $usuario->first_name) }}">
                    </div>

                    <div class="campo">
                        <label for="last_name">Apellido *</label>
                        <input type="text" id="last_name" name="last_name" maxlength="100" required
                               value="{{ old('last_name', $usuario->last_name) }}">
                    </div>

                    <div class="campo">
                        <label for="phone">Teléfono</label>
                        <input type="text" id="phone" name="phone" maxlength="40"
                               value="{{ old('phone', $telefono) }}">
                        <span class="nota">Se guarda cifrado.</span>
                    </div>

                    <div class="campo">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="{{ $usuario->email }}" readonly>
                        <span class="nota">
                            El email identifica la cuenta. Para cambiarlo, pedíselo a un administrador.
                        </span>
                    </div>

                    @if ($usuario->sucursales->isNotEmpty())
                        <div class="campo ancho">
                            <label>Sucursales asignadas</label>
                            <input type="text" value="{{ $usuario->sucursales->pluck('name')->join(', ') }}" readonly>
                        </div>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </form>
        </div>

    {{-- ══ CONTRASEÑA ══ --}}
    @elseif ($tab === 'password')
        <div class="pf-form">
            <form method="POST" action="{{ route('admin.perfil.password') }}" autocomplete="off">
                @csrf
                @method('PUT')

                <div class="pf-grid">
                    <div class="campo ancho">
                        <label for="password_actual">Contraseña actual *</label>
                        <input type="password" id="password_actual" name="password_actual" required>
                    </div>

                    <div class="campo">
                        <label for="password">Nueva contraseña *</label>
                        <input type="password" id="password" name="password" required>
                        <span class="nota">Mínimo 12 caracteres, con letras, números y símbolos.</span>
                    </div>

                    <div class="campo">
                        <label for="password_confirmation">Repetir nueva *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>

                @if ($usuario->password_changed_at)
                    <p style="font-size:12px;color:var(--text-light);margin-bottom:16px;">
                        Última vez que la cambiaste: {{ $usuario->password_changed_at->format('d/m/Y H:i') }}
                    </p>
                @endif

                <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
            </form>
        </div>

    {{-- ══ 2FA ══ --}}
    @elseif ($tab === '2fa')
        <div class="pf-form">
            @if ($usuario->two_factor_enabled)
                <div class="pf-estado activo">
                    <div class="icono">🔐</div>
                    <div>
                        <strong>Verificación en dos pasos activada</strong>
                        <span>Al iniciar sesión se te pide un código de tu aplicación de autenticación.</span>
                    </div>
                </div>

                <p style="font-size:13px;color:var(--text-light);line-height:1.6;margin-bottom:20px;">
                    Desactivarla deja la cuenta protegida solo por la contraseña. Si perdiste
                    acceso a la aplicación, desactivala acá y volvé a configurarla con el
                    dispositivo nuevo.
                </p>

                <form method="POST" action="{{ route('admin.perfil.2fa.desactivar') }}" autocomplete="off"
                      onsubmit="return confirm('¿Desactivar la verificación en dos pasos?');">
                    @csrf
                    @method('DELETE')

                    <div class="pf-grid">
                        <div class="campo">
                            <label for="password_actual">Confirmá con tu contraseña *</label>
                            <input type="password" id="password_actual" name="password_actual" required>
                            <span class="nota">
                                Se pide para que una sesión abierta y desatendida no alcance
                                para quitarle el segundo factor a tu cuenta.
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-secondary">Desactivar</button>
                </form>
            @else
                <div class="pf-estado inactivo">
                    <div class="icono">⚠️</div>
                    <div>
                        <strong>Verificación en dos pasos desactivada</strong>
                        <span>Tu cuenta está protegida solo por la contraseña.</span>
                    </div>
                </div>

                <p style="font-size:13px;color:var(--text-light);line-height:1.6;margin-bottom:20px;">
                    Con el segundo factor activo, alguien que consiga tu contraseña todavía
                    necesita tu teléfono para entrar. Funciona con Google Authenticator,
                    Authy o cualquier aplicación TOTP.
                </p>

                <a href="{{ route('admin.2fa.alta') }}" class="btn btn-primary">
                    Activar verificación en dos pasos
                </a>
            @endif
        </div>

    {{-- ══ PREFERENCIAS ══ --}}
    @else
        <div class="pf-form">
            <form method="POST" action="{{ route('admin.perfil.preferencias') }}">
                @csrf
                @method('PUT')

                <div class="pf-grid">
                    <div class="campo">
                        <label for="preferred_language">Idioma</label>
                        <select id="preferred_language" name="preferred_language" required>
                            @foreach (['es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués'] as $codigo => $nombre)
                                <option value="{{ $codigo }}"
                                    @selected(old('preferred_language', $usuario->preferred_language ?? 'es') === $codigo)>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="campo">
                        <label for="timezone">Zona horaria</label>
                        <select id="timezone" name="timezone" required>
                            @foreach ($zonas as $zona => $etiqueta)
                                <option value="{{ $zona }}"
                                    @selected(old('timezone', $usuario->timezone ?? 'America/Argentina/Buenos_Aires') === $zona)>
                                    {{ $etiqueta }}
                                </option>
                            @endforeach
                        </select>
                        <span class="nota">Las fechas del panel se muestran en esta zona.</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Guardar preferencias</button>
            </form>
        </div>
    @endif

</x-admin.layout>
