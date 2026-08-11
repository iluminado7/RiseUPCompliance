<x-admin.layout :titulo="$titulo">

    @php
        $esAlta = $empresa === null;
        $fiscales = $empresa?->datosFiscales;

        $accion = $esAlta
            ? route('admin.empresas.store')
            : route('admin.empresas.update', $empresa);

        $valor = fn (string $campo, $porDefecto = null) => old($campo, $empresa?->{$campo} ?? $porDefecto);
        $fiscal = fn (string $campo, $porDefecto = null) => old($campo, $fiscales?->{$campo} ?? $porDefecto);

        // El estado se resuelve aparte: old() devuelve un string y el modelo
        // devuelve un enum, asi que ->value solo existe en uno de los dos casos.
        $estadoActual = old('status', $empresa?->status?->value);
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
        .campo input, .campo select, .campo textarea {
            padding:9px 12px; border:1px solid var(--border); border-radius:8px;
            font-size:13px; font-family:inherit; outline:none; background:var(--white);
        }
        .campo input:focus, .campo select:focus, .campo textarea:focus { border-color:var(--gold); }
        .campo .nota { font-size:11px; color:var(--text-light); }
        .campo input[readonly] { background:#f5f5f5; color:var(--text-light); cursor:not-allowed; }
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

        {{-- ── Datos de la empresa ── --}}
        <div class="form-seccion">
            <h3>Datos de la empresa</h3>
            <p class="ayuda">Identificación y configuración general del canal.</p>

            <div class="form-grid">
                <div class="campo">
                    <label for="name">Nombre *</label>
                    <input type="text" id="name" name="name" maxlength="200" required
                           value="{{ $valor('name') }}">
                </div>

                <div class="campo">
                    <label for="email">Email de contacto *</label>
                    <input type="email" id="email" name="email" maxlength="255" required
                           value="{{ $valor('email') }}">
                </div>

                <div class="campo">
                    <label for="slug">Slug del canal público *</label>
                    @if ($esAlta)
                        <input type="text" id="slug" name="slug" maxlength="100" required
                               pattern="[a-z0-9\-]+" value="{{ old('slug') }}"
                               placeholder="mi-empresa">
                        <span class="nota">
                            Solo minúsculas, números y guiones. Define la URL del canal
                            y <strong>no se puede cambiar después</strong>.
                        </span>
                    @else
                        <input type="text" id="slug" value="{{ $empresa->slug }}" readonly>
                        <span class="nota">
                            No editable: es la URL que la empresa ya repartió entre su gente.
                        </span>
                    @endif
                </div>

                @unless ($esAlta)
                    <div class="campo">
                        <label for="status">Estado *</label>
                        <select id="status" name="status" required>
                            <option value="active" @selected($estadoActual === 'active')>Activa</option>
                            <option value="suspended" @selected($estadoActual === 'suspended')>Suspendida</option>
                            <option value="deactivated" @selected($estadoActual === 'deactivated')>Desactivada</option>
                        </select>
                    </div>
                @endunless

                <div class="campo">
                    <label for="default_language">Idioma *</label>
                    <select id="default_language" name="default_language" required>
                        @foreach (['es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués'] as $codigo => $nombre)
                            <option value="{{ $codigo }}" @selected($valor('default_language', 'es') === $codigo)>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="campo">
                    <label for="timezone">Zona horaria *</label>
                    <select id="timezone" name="timezone" required>
                        @foreach ([
                            'America/Argentina/Buenos_Aires' => 'Buenos Aires (UTC-3)',
                            'America/Santiago' => 'Santiago (UTC-3/-4)',
                            'America/Montevideo' => 'Montevideo (UTC-3)',
                            'America/Sao_Paulo' => 'São Paulo (UTC-3)',
                            'America/Bogota' => 'Bogotá (UTC-5)',
                            'America/Mexico_City' => 'Ciudad de México (UTC-6)',
                            'Europe/Madrid' => 'Madrid (UTC+1/+2)',
                            'UTC' => 'UTC',
                        ] as $zona => $etiqueta)
                            <option value="{{ $zona }}"
                                @selected($valor('timezone', 'America/Argentina/Buenos_Aires') === $zona)>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                    <span class="nota">
                        Identificador IANA. El original guardaba "UTC-3", que no es una zona válida.
                    </span>
                </div>
            </div>
        </div>

        {{-- ── Retención ── --}}
        <div class="form-seccion">
            <h3>Retención de datos</h3>
            <p class="ayuda">
                Días que se conservan los datos antes de la purga automática.
                Al purgar una denuncia se destruye su clave de cifrado: los datos
                personales quedan irrecuperables y las filas permanecen, para que
                la traza de auditoría no muestre huecos.
            </p>

            <div class="form-grid">
                <div class="campo">
                    <label for="complaints_retention_days">Denuncias (días) *</label>
                    <input type="number" id="complaints_retention_days" name="complaints_retention_days"
                           min="1" max="32767" required
                           value="{{ $valor('complaints_retention_days', 3660) }}">
                </div>

                <div class="campo">
                    <label for="files_retention_days">Archivos adjuntos (días) *</label>
                    <input type="number" id="files_retention_days" name="files_retention_days"
                           min="1" max="32767" required
                           value="{{ $valor('files_retention_days', 3660) }}">
                </div>

                <div class="campo">
                    <label for="logs_retention_days">Logs (días) *</label>
                    <input type="number" id="logs_retention_days" name="logs_retention_days"
                           min="1" max="32767" required
                           value="{{ $valor('logs_retention_days', 365) }}">
                </div>
            </div>
        </div>

        {{-- ── Sede central: solo al crear ── --}}
        @if ($esAlta)
            <div class="form-seccion">
                <h3>Sede central</h3>
                <p class="ayuda">
                    Toda empresa necesita al menos una sucursal. Después podés
                    agregar más desde la pestaña Sucursales.
                </p>

                <div class="form-grid">
                    <div class="campo">
                        <label for="sede_nombre">Nombre *</label>
                        <input type="text" id="sede_nombre" name="sede_nombre" maxlength="200" required
                               value="{{ old('sede_nombre', 'Casa central') }}">
                    </div>

                    <div class="campo">
                        <label for="sede_codigo">Código interno</label>
                        <input type="text" id="sede_codigo" name="sede_codigo" maxlength="50"
                               value="{{ old('sede_codigo') }}">
                    </div>

                    <div class="campo ancho">
                        <label for="sede_direccion">Dirección</label>
                        <input type="text" id="sede_direccion" name="sede_direccion" maxlength="1000"
                               value="{{ old('sede_direccion') }}">
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Datos fiscales ── --}}
        <div class="form-seccion">
            <h3>Datos fiscales y facturación</h3>
            <p class="ayuda">Todos los campos son opcionales. Se pueden completar más adelante.</p>

            <div class="form-grid">
                <div class="campo">
                    <label for="tax_id">CUIT</label>
                    <input type="text" id="tax_id" name="tax_id" maxlength="13"
                           value="{{ $fiscal('tax_id') }}" placeholder="30-12345678-9">
                    <span class="nota">Se valida el dígito verificador.</span>
                </div>

                <div class="campo">
                    <label for="legal_name">Razón social</label>
                    <input type="text" id="legal_name" name="legal_name" maxlength="300"
                           value="{{ $fiscal('legal_name') }}">
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
                            <option value="{{ $codigo }}" @selected($fiscal('vat_status') === $codigo)>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="campo">
                    <label for="preferred_payment">Forma de pago</label>
                    <select id="preferred_payment" name="preferred_payment">
                        @foreach ([
                            'bank_transfer' => 'Transferencia bancaria',
                            'debit' => 'Débito automático',
                            'check' => 'Cheque',
                        ] as $codigo => $etiqueta)
                            <option value="{{ $codigo }}"
                                @selected($fiscal('preferred_payment', 'bank_transfer') === $codigo)>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="campo ancho">
                    <label for="fiscal_address">Domicilio fiscal</label>
                    <input type="text" id="fiscal_address" name="fiscal_address" maxlength="1000"
                           value="{{ $fiscal('fiscal_address') }}">
                </div>

                <div class="campo ancho">
                    <label for="billing_emails">Emails de facturación</label>
                    <input type="text" id="billing_emails" name="billing_emails" maxlength="1000"
                           value="{{ old('billing_emails', $fiscales?->billing_emails ? implode(', ', $fiscales->billing_emails) : '') }}"
                           placeholder="admin@empresa.com, contable@empresa.com">
                    <span class="nota">Separados por coma.</span>
                </div>

                <div class="campo">
                    <label for="billing_day">Día de facturación</label>
                    <input type="number" id="billing_day" name="billing_day" min="1" max="31"
                           value="{{ $fiscal('billing_day', 1) }}">
                </div>

                <div class="campo">
                    <label for="uses_global_price">Precio</label>
                    <select id="uses_global_price" name="uses_global_price" onchange="alternarMonto(this.value)">
                        <option value="1" @selected($fiscal('uses_global_price', true))>Precio global</option>
                        <option value="0" @selected(! $fiscal('uses_global_price', true))>Monto personalizado</option>
                    </select>
                </div>

                <div class="campo" id="campo-monto"
                     style="display:{{ $fiscal('uses_global_price', true) ? 'none' : 'flex' }};">
                    <label for="custom_amount">Monto mensual</label>
                    <input type="number" id="custom_amount" name="custom_amount" step="0.01" min="0"
                           value="{{ $fiscal('custom_amount') }}">
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">
                {{ $esAlta ? 'Crear empresa' : 'Guardar cambios' }}
            </button>
            <a href="{{ route('admin.administracion.index', ['tab' => 'empresas']) }}"
               class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

    <script>
        function alternarMonto(valor) {
            document.getElementById('campo-monto').style.display = valor === '0' ? 'flex' : 'none';
        }
    </script>

</x-admin.layout>
