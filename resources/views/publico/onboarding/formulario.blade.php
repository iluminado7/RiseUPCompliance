<x-publico.onboarding.layout titulo="Alta de empresa">

    @php
        $etiquetas = [1 => 'Empresa', 2 => 'Sucursales', 3 => 'Usuarios', 4 => 'Revisión'];
        $fiscales = $borrador?->datosFiscales;
    @endphp

    <div class="ob-pasos">
        @foreach ($etiquetas as $numero => $etiqueta)
            <div class="ob-paso {{ $paso === $numero ? 'activo' : ($paso > $numero ? 'hecho' : '') }}">
                {{ $numero }}. {{ $etiqueta }}
            </div>
        @endforeach
    </div>

    @if ($errors->any())
        <div class="ob-error">
            @foreach ($errors->all() as $mensaje)
                <div>{{ $mensaje }}</div>
            @endforeach
        </div>
    @endif

    {{-- ══ PASO 1 ══ --}}
    @if ($paso === 1)
        <form method="POST" action="{{ route('onboarding.paso1', $token->token) }}">
            @csrf

            <div class="ob-card">
                <h2>Datos de la empresa</h2>
                <p class="ayuda">
                    La dirección del canal de denuncias se genera a partir del nombre.
                </p>

                <div class="ob-grid">
                    <div class="campo">
                        <label for="nombre">Nombre de la empresa *</label>
                        <input type="text" id="nombre" name="nombre" maxlength="200" required
                               value="{{ old('nombre', $borrador?->name) }}">
                    </div>

                    <div class="campo">
                        <label for="email">Email de contacto *</label>
                        <input type="email" id="email" name="email" maxlength="255" required
                               value="{{ old('email', $borrador?->email) }}">
                    </div>
                </div>
            </div>

            <div class="ob-card">
                <h2>Sede central</h2>
                <p class="ayuda">Después vas a poder agregar más sucursales.</p>

                <div class="ob-grid">
                    <div class="campo">
                        <label for="sede_nombre">Nombre *</label>
                        <input type="text" id="sede_nombre" name="sede_nombre" maxlength="200" required
                               value="{{ old('sede_nombre', $borrador?->sucursales->firstWhere('is_headquarter', true)?->name ?? 'Casa central') }}">
                    </div>

                    <div class="campo">
                        <label for="sede_direccion">Dirección</label>
                        <input type="text" id="sede_direccion" name="sede_direccion" maxlength="300"
                               value="{{ old('sede_direccion', $borrador?->sucursales->firstWhere('is_headquarter', true)?->address) }}">
                    </div>
                </div>
            </div>

            <div class="ob-card">
                <h2>Datos de facturación</h2>
                <p class="ayuda">Opcionales. Se pueden completar más adelante.</p>

                <div class="ob-grid">
                    <div class="campo">
                        <label for="tax_id">CUIT</label>
                        <input type="text" id="tax_id" name="tax_id" maxlength="13"
                               placeholder="30-12345678-9"
                               value="{{ old('tax_id', $fiscales?->tax_id) }}">
                    </div>

                    <div class="campo">
                        <label for="legal_name">Razón social</label>
                        <input type="text" id="legal_name" name="legal_name" maxlength="300"
                               value="{{ old('legal_name', $fiscales?->legal_name) }}">
                    </div>

                    <div class="campo">
                        <label for="vat_status">Condición IVA</label>
                        <select id="vat_status" name="vat_status">
                            <option value="">— Sin definir —</option>
                            @foreach ([
                                'RI' => 'Responsable Inscripto',
                                'Monotax' => 'Monotributista',
                                'Exempt' => 'Exento',
                                'FinalConsumer' => 'Consumidor Final',
                            ] as $codigo => $etiqueta)
                                <option value="{{ $codigo }}"
                                    @selected(old('vat_status', $fiscales?->vat_status) === $codigo)>
                                    {{ $etiqueta }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="campo">
                        <label for="billing_emails">Emails de facturación</label>
                        <input type="text" id="billing_emails" name="billing_emails" maxlength="1000"
                               placeholder="admin@empresa.com, contable@empresa.com"
                               value="{{ old('billing_emails', $fiscales?->billing_emails ? implode(', ', $fiscales->billing_emails) : '') }}">
                        <span class="nota">Separados por coma.</span>
                    </div>

                    <div class="campo ancho">
                        <label for="fiscal_address">Domicilio fiscal</label>
                        <input type="text" id="fiscal_address" name="fiscal_address" maxlength="1000"
                               value="{{ old('fiscal_address', $fiscales?->fiscal_address) }}">
                    </div>
                </div>
            </div>

            <button type="submit" class="ob-btn ob-btn-principal">Continuar</button>
        </form>

    {{-- ══ PASO 2 ══ --}}
    @elseif ($paso === 2)
        @php $adicionales = $borrador->sucursales->where('is_headquarter', false)->values(); @endphp

        <form method="POST" action="{{ route('onboarding.paso2', $token->token) }}">
            @csrf

            <div class="ob-card">
                <h2>Sucursales adicionales</h2>
                <p class="ayuda">
                    Si {{ $borrador->name }} tiene una sola sede, seguí sin agregar ninguna.
                </p>

                <div id="lista-sucursales">
                    @forelse ($adicionales as $i => $sucursal)
                        <div class="ob-fila">
                            <div class="campo">
                                <label>Nombre</label>
                                <input type="text" name="sucursales[{{ $i }}][nombre]"
                                       maxlength="200" value="{{ $sucursal->name }}">
                            </div>
                            <div class="campo">
                                <label>Dirección</label>
                                <input type="text" name="sucursales[{{ $i }}][direccion]"
                                       maxlength="300" value="{{ $sucursal->address }}">
                            </div>
                            <button type="button" class="ob-quitar" onclick="this.parentElement.remove()">×</button>
                        </div>
                    @empty
                        <div class="ob-fila">
                            <div class="campo">
                                <label>Nombre</label>
                                <input type="text" name="sucursales[0][nombre]" maxlength="200">
                            </div>
                            <div class="campo">
                                <label>Dirección</label>
                                <input type="text" name="sucursales[0][direccion]" maxlength="300">
                            </div>
                            <button type="button" class="ob-quitar" onclick="this.parentElement.remove()">×</button>
                        </div>
                    @endforelse
                </div>

                <button type="button" class="ob-btn ob-btn-secundario" onclick="agregarSucursal()"
                        style="margin-top:8px;">
                    + Agregar otra
                </button>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="ob-btn ob-btn-principal">Continuar</button>
            </div>
        </form>

        <form method="POST" action="{{ route('onboarding.volver', $token->token) }}" style="margin-top:10px;">
            @csrf
            <input type="hidden" name="paso" value="1">
            <button type="submit" class="ob-btn ob-btn-secundario">← Volver a los datos de la empresa</button>
        </form>

        <script>
            let indiceSucursal = {{ max($adicionales->count(), 1) }};

            function agregarSucursal() {
                const lista = document.getElementById('lista-sucursales');
                const fila = document.createElement('div');
                fila.className = 'ob-fila';
                fila.innerHTML = `
                    <div class="campo">
                        <label>Nombre</label>
                        <input type="text" name="sucursales[${indiceSucursal}][nombre]" maxlength="200">
                    </div>
                    <div class="campo">
                        <label>Dirección</label>
                        <input type="text" name="sucursales[${indiceSucursal}][direccion]" maxlength="300">
                    </div>
                    <button type="button" class="ob-quitar" onclick="this.parentElement.remove()">×</button>`;
                lista.appendChild(fila);
                indiceSucursal++;
            }
        </script>

    {{-- ══ PASO 3 ══ --}}
    @elseif ($paso === 3)
        <form method="POST" action="{{ route('onboarding.paso3', $token->token) }}">
            @csrf

            <div class="ob-card">
                <h2>Usuarios del panel</h2>
                <p class="ayuda">
                    Al menos un administrador principal. Las contraseñas se piden cambiar
                    en el primer ingreso.
                </p>

                <div id="lista-usuarios"></div>

                <button type="button" class="ob-btn ob-btn-secundario" onclick="agregarUsuario()"
                        style="margin-top:8px;">
                    + Agregar otro usuario
                </button>
            </div>

            <button type="submit" class="ob-btn ob-btn-principal">Continuar</button>
        </form>

        <form method="POST" action="{{ route('onboarding.volver', $token->token) }}" style="margin-top:10px;">
            @csrf
            <input type="hidden" name="paso" value="2">
            <button type="submit" class="ob-btn ob-btn-secundario">← Volver a las sucursales</button>
        </form>

        <script>
            let indiceUsuario = 0;

            function agregarUsuario() {
                const lista = document.getElementById('lista-usuarios');
                const i = indiceUsuario;
                const primero = i === 0;

                const bloque = document.createElement('div');
                bloque.style.cssText = 'border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:12px;position:relative;';
                bloque.innerHTML = `
                    <div class="ob-grid">
                        <div class="campo">
                            <label>Nombre *</label>
                            <input type="text" name="usuarios[${i}][nombre]" maxlength="100" required>
                        </div>
                        <div class="campo">
                            <label>Apellido *</label>
                            <input type="text" name="usuarios[${i}][apellido]" maxlength="100" required>
                        </div>
                        <div class="campo">
                            <label>Email *</label>
                            <input type="email" name="usuarios[${i}][email]" maxlength="255" required>
                        </div>
                        <div class="campo">
                            <label>Rol *</label>
                            <select name="usuarios[${i}][rol]" required>
                                <option value="admin_principal" ${primero ? 'selected' : ''}>Administrador principal</option>
                                <option value="gestor" ${primero ? '' : 'selected'}>Gestor</option>
                            </select>
                        </div>
                        <div class="campo">
                            <label>Contraseña *</label>
                            <input type="password" name="usuarios[${i}][password]" required>
                            <span class="nota">Mínimo 12 caracteres, con letras, números y símbolos.</span>
                        </div>
                        <div class="campo">
                            <label>Repetir contraseña *</label>
                            <input type="password" name="usuarios[${i}][password_confirmation]" required>
                        </div>
                    </div>`;

                if (!primero) {
                    const quitar = document.createElement('button');
                    quitar.type = 'button';
                    quitar.className = 'ob-quitar';
                    quitar.textContent = '× Quitar';
                    quitar.style.cssText = 'position:absolute;top:10px;right:10px;';
                    quitar.onclick = () => bloque.remove();
                    bloque.appendChild(quitar);
                }

                lista.appendChild(bloque);
                indiceUsuario++;
            }

            agregarUsuario();
        </script>

    {{-- ══ PASO 4: REVISIÓN ══ --}}
    @else
        <div class="ob-card">
            <h2>Revisá antes de enviar</h2>
            <p class="ayuda">
                Una vez enviado, el enlace deja de funcionar y los datos pasan a revisión.
            </p>

            <div style="font-size:14px;line-height:1.9;">
                <strong>{{ $borrador->name }}</strong><br>
                {{ $borrador->email }}<br>
                <span style="color:var(--text-light);font-size:13px;">
                    Canal público: /{{ $borrador->slug }}
                </span>
            </div>
        </div>

        <div class="ob-card">
            <h2>Sucursales ({{ $borrador->sucursales->count() }})</h2>
            <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.9;">
                @foreach ($borrador->sucursales as $sucursal)
                    <li>
                        {{ $sucursal->name }}
                        @if ($sucursal->is_headquarter)
                            <span style="color:var(--gold-dark);font-size:12px;">★ sede central</span>
                        @endif
                        @if ($sucursal->address)
                            <span style="color:var(--text-light);font-size:12px;">— {{ $sucursal->address }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="ob-card">
            <h2>Usuarios ({{ $borrador->usuarios->count() }})</h2>
            <ul style="margin:0;padding-left:18px;font-size:14px;line-height:1.9;">
                @foreach ($borrador->usuarios as $usuarioBorrador)
                    <li>
                        {{ $usuarioBorrador->first_name }} {{ $usuarioBorrador->last_name }}
                        — {{ $usuarioBorrador->email }}
                        <span style="color:var(--text-light);font-size:12px;">
                            ({{ $usuarioBorrador->role === 'admin_principal' ? 'Administrador principal' : 'Gestor' }})
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <form method="POST" action="{{ route('onboarding.confirmar', $token->token) }}">
                @csrf
                <button type="submit" class="ob-btn ob-btn-principal"
                        onclick="return confirm('¿Enviar el formulario? Después no vas a poder modificarlo.');">
                    Enviar formulario
                </button>
            </form>

            <form method="POST" action="{{ route('onboarding.volver', $token->token) }}">
                @csrf
                <input type="hidden" name="paso" value="3">
                <button type="submit" class="ob-btn ob-btn-secundario">← Corregir usuarios</button>
            </form>
        </div>
    @endif

</x-publico.onboarding.layout>
