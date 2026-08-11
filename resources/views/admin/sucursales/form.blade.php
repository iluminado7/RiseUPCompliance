<x-admin.layout :titulo="$titulo">

    @php
        $esAlta = $sucursal === null;
        $accion = $esAlta
            ? route('admin.sucursales.store')
            : route('admin.sucursales.update', $sucursal);
        $valor = fn (string $campo, $porDefecto = null) => old($campo, $sucursal?->{$campo} ?? $porDefecto);
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
        .check { display:flex; align-items:center; gap:9px; font-size:13px; cursor:pointer; }
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
            <h3>Datos de la sucursal</h3>
            <p class="ayuda">El código interno es opcional pero debe ser único dentro de la empresa.</p>

            <div class="form-grid">
                @if ($esSuperadmin ?? true)
                    <div class="campo">
                        <label for="company_id">Empresa *</label>
                        @if ($esAlta)
                            <select id="company_id" name="company_id" required>
                                <option value="">— Elegir —</option>
                                @foreach ($empresas as $empresa)
                                    <option value="{{ $empresa->id }}"
                                        @selected((int) old('company_id') === $empresa->id)>
                                        {{ $empresa->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" value="{{ $sucursal->empresa->name }}" readonly
                                   style="background:#f5f5f5;color:var(--text-light);">
                            <span class="nota">
                                Una sucursal no cambia de empresa: sus denuncias quedarían huérfanas.
                            </span>
                        @endif
                    </div>
                @endif

                <div class="campo">
                    <label for="name">Nombre *</label>
                    <input type="text" id="name" name="name" maxlength="200" required
                           value="{{ $valor('name') }}">
                </div>

                <div class="campo">
                    <label for="internal_code">Código interno</label>
                    <input type="text" id="internal_code" name="internal_code" maxlength="50"
                           value="{{ $valor('internal_code') }}">
                </div>

                <div class="campo ancho">
                    <label for="address">Dirección</label>
                    <input type="text" id="address" name="address" maxlength="1000"
                           value="{{ $valor('address') }}">
                </div>

                <div class="campo">
                    <label for="default_language">Idioma</label>
                    <select id="default_language" name="default_language">
                        <option value="">— Hereda de la empresa —</option>
                        @foreach (['es' => 'Español', 'en' => 'Inglés', 'pt' => 'Portugués'] as $codigo => $nombre)
                            <option value="{{ $codigo }}" @selected($valor('default_language') === $codigo)>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="campo">
                    <label for="timezone">Zona horaria</label>
                    <select id="timezone" name="timezone">
                        <option value="">— Hereda de la empresa —</option>
                        @foreach ([
                            'America/Argentina/Buenos_Aires' => 'Buenos Aires (UTC-3)',
                            'America/Santiago' => 'Santiago',
                            'America/Montevideo' => 'Montevideo',
                            'America/Sao_Paulo' => 'São Paulo',
                            'America/Bogota' => 'Bogotá',
                            'America/Mexico_City' => 'Ciudad de México',
                            'Europe/Madrid' => 'Madrid',
                            'UTC' => 'UTC',
                        ] as $zona => $etiqueta)
                            <option value="{{ $zona }}" @selected($valor('timezone') === $zona)>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="campo ancho">
                    <label class="check">
                        <input type="hidden" name="is_headquarter" value="0">
                        <input type="checkbox" name="is_headquarter" value="1"
                               @checked($valor('is_headquarter', false))>
                        Es la sede central
                    </label>
                    <span class="nota">
                        Solo puede haber una por empresa. Marcarla desmarca la anterior.
                    </span>
                </div>

                @unless ($esAlta)
                    <div class="campo ancho">
                        <label class="check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   @checked($valor('is_active', true))>
                            Sucursal activa
                        </label>
                        <span class="nota">
                            Desactivarla la saca del formulario público, pero conserva sus denuncias.
                        </span>
                    </div>
                @endunless
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary">
                {{ $esAlta ? 'Crear sucursal' : 'Guardar cambios' }}
            </button>
            <a href="{{ route('admin.administracion.index', ['tab' => 'sucursales']) }}"
               class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

</x-admin.layout>
