<x-admin.layout titulo="Catálogo">

    @php
        $urlTab = fn (string $t) => route('admin.catalogo.index', array_filter([
            'tab' => $t,
            'q' => $busqueda ?: null,
        ]));

        $pestanas = [
            'categorias' => 'Categorías de denuncia',
            'relaciones' => 'Relaciones del denunciante',
            'areas' => 'Áreas / Sectores',
            'cargos' => 'Cargos / Posiciones',
        ];
    @endphp

    <style>
        .cat-tabs { display:flex; border-bottom:2px solid var(--border); margin-bottom:20px; flex-wrap:wrap; }
        .cat-tab {
            padding:10px 18px; font-size:13px; font-weight:600; color:var(--text-light);
            border-bottom:2px solid transparent; margin-bottom:-2px; text-decoration:none;
            white-space:nowrap; transition:color .15s;
        }
        .cat-tab:hover { color:var(--dark); }
        .cat-tab.active { color:var(--gold-dark); border-bottom-color:var(--gold); }

        .cat-form { background:#fafafa; border-top:1px solid var(--border); padding:18px 20px; }
        .cat-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:14px; }
        .cat-grid .ancho { grid-column:1 / -1; }
        .campo { display:flex; flex-direction:column; gap:5px; }
        .campo label { font-size:12px; font-weight:700; color:var(--text-light); text-transform:uppercase; letter-spacing:.04em; }
        .campo input, .campo textarea {
            padding:9px 12px; border:1px solid var(--border); border-radius:8px;
            font-size:13px; font-family:inherit; outline:none;
        }
        .campo input:focus, .campo textarea:focus { border-color:var(--gold); }
        .campo input[readonly] { background:#f0f0f0; color:var(--text-light); }
        .campo .nota { font-size:11px; color:var(--text-light); }
        .check { display:flex; align-items:center; gap:9px; font-size:13px; cursor:pointer; }
        .check input { width:16px; height:16px; accent-color:var(--gold); }
        .fila-edicion { display:none; background:#fffbe6; }
        /* Mismo aspecto que los filtros del resto del panel. La clase
           .filters input de admin.css solo aplica dentro de .filters, y
           estos campos estan fuera de ese contenedor. */
        .campo-panel {
            padding: 9px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            color: var(--text);
            background: var(--white);
            outline: none;
            transition: border-color .15s;
        }
        .campo-panel:focus { border-color: var(--gold); }
        .campo-panel::placeholder { color: #bbb; }

        @media (max-width:900px) { .cat-grid { grid-template-columns:1fr; } }
    </style>

    <div class="card" style="margin-bottom:20px;">
        <div style="padding:16px 20px;font-size:13px;color:var(--text-light);line-height:1.6;">
            El catálogo es la biblioteca del sistema: acá se crean las opciones y desde
            <a href="{{ route('admin.configuracion.index') }}">Configuración del Canal</a>
            se elige cuáles aplican a cada empresa.
            <strong>Todo cambio impacta a nivel global.</strong>
        </div>

        <form method="GET" action="{{ route('admin.catalogo.index') }}"
              style="padding:0 20px 16px;display:flex;gap:10px;flex-wrap:wrap;">
            <input type="hidden" name="tab" value="{{ $tab }}">
            {{-- Las sugerencias muestran nombre y código porque el campo
                 busca por los dos, y ver el código ayuda a distinguir ítems
                 de nombre parecido. --}}
            <x-autocompletado
                nombre="q"
                :valor="$busqueda"
                :opciones="$sugerencias"
                placeholder="Buscar por nombre o código..." />
            <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
            <a href="{{ route('admin.catalogo.index', ['tab' => $tab]) }}"
               class="btn btn-secondary btn-sm">Limpiar</a>
        </form>
    </div>

    <div class="cat-tabs">
        @foreach ($pestanas as $clave => $nombre)
            <a href="{{ $urlTab($clave) }}" class="cat-tab {{ $tab === $clave ? 'active' : '' }}">
                {{ $nombre }}
            </a>
        @endforeach
    </div>

    {{-- ── Alta ── --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">Nueva {{ $etiqueta }}</span>
        </div>

        <form method="POST" action="{{ route('admin.catalogo.crear', $tab) }}" class="cat-form">
            @csrf

            <div class="cat-grid">
                <div class="campo">
                    <label for="code">Código único *</label>
                    <input type="text" id="code" name="code" maxlength="50" required
                           pattern="[a-zA-Z0-9_\-]+" value="{{ old('code') }}"
                           autocomplete="off">
                    <span class="nota">Letras, números, guiones. No se puede cambiar después.</span>
                </div>

                <div class="campo">
                    <label for="name_es">Nombre (ES) *</label>
                    <input type="text" id="name_es" name="name_es" maxlength="150" required
                           value="{{ old('name_es') }}" autocomplete="off">
                </div>

                @if ($tieneOrden)
                    <div class="campo">
                        <label for="display_order">Orden</label>
                        <input type="number" id="display_order" name="display_order" min="0"
                               value="{{ old('display_order', 0) }}">
                    </div>
                @endif

                <div class="campo ancho">
                    <label for="description_es">Descripción</label>
                    <textarea id="description_es" name="description_es" rows="2"
                              maxlength="2000">{{ old('description_es') }}</textarea>
                </div>
            </div>

            <label class="check" style="margin-bottom:14px;">
                <input type="checkbox" name="is_active" value="1" checked>
                Ítem activo
            </label>

            <button type="submit" class="btn btn-primary">Crear {{ $etiqueta }}</button>
        </form>
    </div>

    {{-- ── Listado ── --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                {{ $items->count() }} {{ \Illuminate\Support\Str::plural('ítem', $items->count()) }}
            </span>
        </div>

        <div class="table-wrapper">
            @if ($items->isEmpty())
                <div class="empty-state">
                    <div class="icon">📚</div>
                    <p>Sin resultados.</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            @if ($tab === 'categorias')
                                <th>Preguntas</th>
                            @endif
                            @if ($tieneOrden)
                                <th>Orden</th>
                            @endif
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td><code style="font-size:12px;">{{ $item->code }}</code></td>
                                <td><strong>{{ $item->name_es }}</strong></td>
                                <td style="font-size:12px;color:var(--text-light);max-width:320px;">
                                    {{ \Illuminate\Support\Str::limit($item->description_es, 90) ?: '—' }}
                                </td>
                                @if ($tab === 'categorias')
                                    <td>{{ $item->preguntas_count }}</td>
                                @endif
                                @if ($tieneOrden)
                                    <td>{{ $item->display_order }}</td>
                                @endif
                                <td>
                                    @if ($item->is_active)
                                        <span class="badge-activo">ACTIVA</span>
                                    @else
                                        <span class="badge-inactivo">INACTIVA</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                                onclick="alternarEdicion({{ $item->id }})">Editar</button>
                                        @if ($tab === 'categorias')
                                            <a href="{{ route('admin.catalogo.preguntas', $item) }}"
                                               class="btn btn-primary btn-sm">Preguntas</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <tr class="fila-edicion" id="edicion-{{ $item->id }}">
                                <td colspan="{{ 4 + ($tab === 'categorias' ? 1 : 0) + ($tieneOrden ? 1 : 0) + 1 }}">
                                    <form method="POST"
                                          action="{{ route('admin.catalogo.actualizar', [$tab, $item->id]) }}"
                                          style="padding:8px 0;">
                                        @csrf
                                        @method('PUT')

                                        <div class="cat-grid">
                                            <div class="campo">
                                                <label>Código único</label>
                                                <input type="text" value="{{ $item->code }}" readonly>
                                                <span class="nota">
                                                    Permanente: cambiarlo rompería la trazabilidad de las
                                                    denuncias ya vinculadas.
                                                </span>
                                            </div>

                                            <div class="campo">
                                                <label for="name_es_{{ $item->id }}">Nombre (ES) *</label>
                                                <input type="text" id="name_es_{{ $item->id }}" name="name_es"
                                                       maxlength="150" required value="{{ $item->name_es }}">
                                            </div>

                                            @if ($tieneOrden)
                                                <div class="campo">
                                                    <label for="display_order_{{ $item->id }}">Orden</label>
                                                    <input type="number" id="display_order_{{ $item->id }}"
                                                           name="display_order" min="0"
                                                           value="{{ $item->display_order }}">
                                                </div>
                                            @endif

                                            <div class="campo ancho">
                                                <label for="description_es_{{ $item->id }}">Descripción</label>
                                                <textarea id="description_es_{{ $item->id }}" name="description_es"
                                                          rows="2" maxlength="2000">{{ $item->description_es }}</textarea>
                                            </div>
                                        </div>

                                        <label class="check" style="margin-bottom:12px;">
                                            <input type="checkbox" name="is_active" value="1"
                                                   @checked($item->is_active)>
                                            Ítem activo
                                        </label>

                                        <div style="display:flex;gap:8px;">
                                            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                    onclick="alternarEdicion({{ $item->id }})">Cancelar</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <script>
        function alternarEdicion(id) {
            const fila = document.getElementById('edicion-' + id);
            fila.style.display = fila.style.display === 'table-row' ? 'none' : 'table-row';
        }
    </script>

</x-admin.layout>
