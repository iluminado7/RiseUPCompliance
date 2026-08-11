<x-admin.layout :titulo="$titulo">

    @php
        $esAlta = $usuario === null;
        $accion = $esAlta
            ? route('admin.usuarios.store')
            : route('admin.usuarios.update', $usuario);

        $valor = fn (string $campo, $porDefecto = null) => old($campo, $usuario?->{$campo} ?? $porDefecto);
        $estadoActual = old('status', $usuario?->status?->value ?? 'active');
        $rolActual = (int) old('role_id', $usuario?->role_id ?? 0);
        $empresaActual = (int) old('company_id', $usuario?->company_id ?? auth()->user()->company_id ?? 0);
    @endphp

    <style>
        .form-seccion {
            background:var(--white); border-radius:var(--radius);
            padding:24px; box-shadow:var(--shadow); margin-bottom:20px;
        }
        .form-seccion h3 {
            font-size:14px; font-weight:700; color:var(--dark);
            margin-bottom:4px; text-transform:uppercase; letter-spacing:.04em;
        }
        .form-seccion .ayuda { font-size:12px; color:var(--text-light); margin-bottom:18px; }
        .form-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
        .campo { display:flex; flex-direction:column; gap:5px; }
        .campo.ancho { grid-column:1 / -1; }
        .campo label { font-size:12px; font-weight:700; color:var(--text-light); text-transform:uppercase; letter-spacing:.04em; }
        .campo input, .campo select {
            padding:9px 12px; border:1px solid var(--border); border-radius:8px;
            font-size:13px; font-family:inherit; outline:none;
        }
        .campo input:focus, .campo select:focus { border-color:var(--gold); }
        .campo .nota { font-size:11px; color:var(--text-light); }
        .check { display:flex; align-items:center; gap:9px; font-size:13px; cursor:pointer; padding:5px 0; }
        .check input { width:16px; height:16px; accent-color:var(--gold); }
        @media (max-width:768px) { .form-grid { grid-template-columns:1fr; } }
    </style>

    @if ($errors->any())
        <div class="alert alert-error">
            @foreach ($errors->all() as $mensaje)
                <div>{{ $mensaje }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $accion }}">
        @csrf
        @unless ($esAlta) @method('PUT') @endunless

        <div class="form-seccion">
            <h3>Datos personales</h3>

            <div class="form-grid">
                <div class="campo">
                    <label for="first_name">Nombre *</label>
                    <input type="text" id="first_name" name="first_name" maxlength="100" required
                           value="{{ $valor('first_name') }}">
                </div>

                <div class="campo">
                    <label for="last_name">Apellido *</label>
                    <input type="text" id="last_name" name="last_name" maxlength="100" required
                           value="{{ $valor('last_name') }}">
                </div>

                <div class="campo">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" maxlength="255" required
                           value="{{ $valor('email') }}">
                </div>

                <div class="campo">
                    <label for="phone">Teléfono</label>
                    <input type="text" id="phone" name="phone" maxlength="40"
                           value="{{ old('phone', $telefono) }}">
                    <span class="nota">Se guarda cifrado.</span>
                </div>
            </div>
        </div>

        <div class="form-seccion">
            <h3>Rol y alcance</h3>
            <p class="ayuda">
                Determina qué ve y qué puede hacer dentro del panel.
            </p>

            <div class="form-grid">
                <div class="campo">
                    <label for="role_id">Rol *</label>
                    <select id="role_id" name="role_id" required onchange="alternarSucursales()">
                        <option value="">— Elegir —</option>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->id }}" data-nombre="{{ $rol->name }}"
                                @selected($rolActual === $rol->id)>
                                {{ \App\Enums\RolUsuario::tryFrom($rol->name)?->etiqueta() ?? $rol->name }}
                            </option>
                        @endforeach
                    </select>
                    @unless ($esSuperadmin)
                        <span class="nota">Como admin principal solo podés crear gestores.</span>
                    @endunless
                </div>

                @if ($esSuperadmin)
                    <div class="campo">
                        <label for="company_id">Empresa</label>
                        <select id="company_id" name="company_id">
                            <option value="">— Sin empresa (plataforma) —</option>
                            @foreach ($empresas as $empresa)
                                <option value="{{ $empresa->id }}" @selected($empresaActual === $empresa->id)>
                                    {{ $empresa->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="nota">Sin empresa = usuario de plataforma (superadmin).</span>
                    </div>
                @endif

                @unless ($esAlta)
                    <div class="campo">
                        <label for="status">Estado *</label>
                        <select id="status" name="status" required>
                            <option value="active" @selected($estadoActual === 'active')>Activo</option>
                            <option value="inactive" @selected($estadoActual === 'inactive')>Inactivo</option>
                            <option value="suspended" @selected($estadoActual === 'suspended')>Suspendido</option>
                        </select>
                        <span class="nota">
                            Suspenderlo lo deja afuera del panel en el siguiente request,
                            aunque tenga la sesión abierta.
                        </span>
                    </div>
                @endunless
            </div>

            @if ($sucursales->isNotEmpty())
                <div id="bloque-sucursales" style="margin-top:18px;">
                    <label style="font-size:12px;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.04em;">
                        Sucursales asignadas
                    </label>
                    <p class="ayuda" style="margin-top:5px;">
                        El administrador principal recibe todas automáticamente, así que
                        esta selección no aplica a ese rol.
                    </p>

                    <div style="display:flex;flex-direction:column;gap:4px;max-height:220px;overflow-y:auto;">
                        @foreach ($sucursales as $sucursal)
                            <label class="check">
                                <input type="checkbox" name="branch_ids[]" value="{{ $sucursal->id }}"
                                       @checked(in_array($sucursal->id, old('branch_ids', $sucursalesAsignadas)))>
                                {{ $sucursal->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="form-seccion">
            <h3>{{ $esAlta ? 'Contraseña' : 'Cambiar contraseña' }}</h3>
            <p class="ayuda">
                Mínimo 12 caracteres, con letras, números y símbolos.
                @unless ($esAlta)
                    Dejalo vacío para no cambiarla.
                @endunless
            </p>

            <div class="form-grid">
                @if ($esAlta)
                    <div class="campo">
                        <label for="password">Contraseña *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="campo">
                        <label for="password_confirmation">Repetir *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required>
                    </div>
                @else
                    <div class="campo">
                        <label for="nueva_password">Nueva contraseña</label>
                        <input type="password" id="nueva_password" name="nueva_password">
                    </div>
                    <div class="campo">
                        <label for="nueva_password_confirmation">Repetir</label>
                        <input type="password" id="nueva_password_confirmation" name="nueva_password_confirmation">
                    </div>
                @endif
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">
                {{ $esAlta ? 'Crear usuario' : 'Guardar cambios' }}
            </button>
            <a href="{{ route('admin.administracion.index', ['tab' => 'usuarios']) }}"
               class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

    <script>
        // El admin principal recibe todas las sucursales, asi que la
        // seleccion manual no tiene efecto para ese rol.
        function alternarSucursales() {
            const select = document.getElementById('role_id');
            const bloque = document.getElementById('bloque-sucursales');
            if (!bloque || !select.value) return;

            const nombre = select.options[select.selectedIndex].dataset.nombre;
            bloque.style.opacity = nombre === 'admin_principal' ? '.4' : '1';
            bloque.querySelectorAll('input').forEach(i => {
                i.disabled = nombre === 'admin_principal';
            });
        }

        alternarSucursales();
    </script>

</x-admin.layout>
