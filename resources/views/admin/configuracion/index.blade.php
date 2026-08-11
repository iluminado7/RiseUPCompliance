<x-admin.layout titulo="Configuración del Canal">

    @php
        $urlTab = fn (string $t) => route('admin.configuracion.index', array_filter([
            'empresa_id' => $empresa?->id,
            'tab' => $t,
        ]));
    @endphp

    <style>
        .cfg-tabs { display:flex; border-bottom:2px solid var(--border); margin-bottom:20px; flex-wrap:wrap; }
        .cfg-tab {
            padding:10px 18px; font-size:13px; font-weight:600; color:var(--text-light);
            border-bottom:2px solid transparent; margin-bottom:-2px; text-decoration:none;
            white-space:nowrap; transition:color .15s;
        }
        .cfg-tab:hover { color:var(--dark); }
        .cfg-tab.active { color:var(--gold-dark); border-bottom-color:var(--gold); }

        .cfg-opciones {
            display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr));
            gap:8px; margin-bottom:18px;
        }
        .cfg-opcion {
            display:flex; align-items:flex-start; gap:10px; padding:10px 12px;
            border:1px solid var(--border); border-radius:8px; cursor:pointer;
            font-size:13px; transition:border-color .15s, background .15s;
        }
        .cfg-opcion:hover { border-color:var(--gold); }
        .cfg-opcion input { width:16px; height:16px; accent-color:var(--gold); margin-top:2px; flex-shrink:0; }
        .cfg-opcion .desc { display:block; font-size:11px; color:var(--text-light); margin-top:2px; }

        .cfg-canal {
            display:flex; gap:10px; align-items:center; flex-wrap:wrap;
            padding:12px 14px; background:#fffbe6; border:1px solid var(--gold);
            border-radius:10px; margin-top:12px;
        }
        .cfg-canal input {
            flex:1; min-width:220px; padding:8px 12px; border:1px solid var(--border);
            border-radius:8px; font-size:12px; font-family:monospace; background:#fff;
        }

        .pq-fila {
            display:flex; gap:10px; align-items:flex-start; margin-bottom:8px;
            padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:#fff;
        }
        .pq-fila textarea {
            flex:1; padding:8px 12px; border:1px solid var(--border); border-radius:8px;
            font-size:13px; font-family:inherit; resize:vertical;
        }
        .pq-origen {
            font-size:10px; font-weight:700; padding:2px 7px; border-radius:999px;
            white-space:nowrap; margin-top:8px;
        }
        .pq-catalogo { background:#e8f0fe; color:#1a5fb4; }
        .pq-propia { background:#fff3cd; color:#856404; }
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

    </style>

    {{-- ── Selector de empresa ── --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">Empresa a configurar</span>
        </div>

        <div style="padding:16px 20px;">
            <form method="GET" action="{{ route('admin.configuracion.index') }}"
                  style="display:flex;gap:10px;flex-wrap:wrap;">
                <input type="hidden" name="tab" value="{{ $tab }}">

                {{-- Autocompletado con id oculto: la elección no es un filtro
                     de texto sino la carga de la configuración de esa empresa,
                     así que hace falta el id. --}}
                <x-autocompletado
                    nombre="empresa_nombre"
                    nombre-oculto="empresa_id"
                    :valor="$empresa?->name"
                    :valor-oculto="$empresa?->id"
                    :opciones="$empresas->pluck('name')"
                    :valores="$empresas->pluck('id', 'name')"
                    :enviar-al-elegir="true"
                    placeholder="Buscar empresa..." />
            </form>

            @if ($empresa)
                @php
                    [$claseEstado, $etiquetaEstado] = match ($empresa->status->value) {
                        'active' => ['badge-activo', 'ACTIVA'],
                        'suspended' => ['badge-suspendido', 'SUSPENDIDA'],
                        default => ['badge-inactivo', 'DESACTIVADA'],
                    };
                @endphp

                <div style="margin-top:14px;display:flex;align-items:center;gap:10px;">
                    <span class="{{ $claseEstado }}">{{ $etiquetaEstado }}</span>
                    @unless ($empresa->canalPublicoAbierto())
                        <span style="font-size:12px;color:#856404;">
                            El canal público de esta empresa no recibe denuncias nuevas.
                        </span>
                    @endunless
                </div>

                <div class="cfg-canal">
                    <span style="font-size:12px;font-weight:600;">Link del canal:</span>
                    <input type="text" id="link-canal" value="{{ url('/' . $empresa->slug) }}"
                           readonly onclick="this.select()">
                    <button type="button" class="btn btn-primary btn-sm" onclick="copiarCanal(this)">Copiar</button>
                </div>
            @endif
        </div>
    </div>

    {{-- Sin empresa elegida no hay nada que configurar. Se usa if/else y
         no un return dentro de @php: cortar el render a mitad del slot de
         un componente deja el layout sin cerrar. --}}
    @if (! $empresa)
        <div class="card">
            <div class="empty-state">
                <div class="icon">🏢</div>
                <p>Elegí una empresa para configurar su canal.</p>
                <p style="font-size:12px;color:var(--text-light);margin-top:8px;">
                    Cada empresa tiene su configuración independiente.
                </p>
            </div>
        </div>
    @else

    <div class="cfg-tabs">
        <a href="{{ $urlTab('categorias') }}" class="cfg-tab {{ $tab === 'categorias' ? 'active' : '' }}">
            Categorías y relaciones
        </a>
        <a href="{{ $urlTab('areas') }}" class="cfg-tab {{ $tab === 'areas' ? 'active' : '' }}">
            Áreas y cargos
        </a>
        <a href="{{ $urlTab('cuestionario') }}" class="cfg-tab {{ $tab === 'cuestionario' ? 'active' : '' }}">
            Constructor de cuestionario
        </a>
        <a href="{{ $urlTab('legal') }}" class="cfg-tab {{ $tab === 'legal' ? 'active' : '' }}">
            Legal y notificaciones
        </a>
    </div>

    {{-- ══ CATEGORÍAS Y RELACIONES ══ --}}
    @if ($tab === 'categorias')
        <form method="POST" action="{{ route('admin.configuracion.categorias', $empresa) }}">
            @csrf

            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><span class="card-title">Categorías de denuncia</span></div>
                <div style="padding:16px 20px;">
                    <p style="font-size:12px;color:var(--text-light);margin-bottom:14px;">
                        Al habilitar una categoría que esta empresa no tenía, se importa
                        automáticamente el cuestionario del catálogo.
                    </p>

                    <div class="cfg-opciones">
                        @foreach ($categorias as $categoria)
                            <label class="cfg-opcion">
                                <input type="checkbox" name="categorias[]" value="{{ $categoria->id }}"
                                       @checked(in_array($categoria->id, $seleccionCategorias))>
                                <span>
                                    {{ $categoria->name_es }}
                                    @if ($categoria->description_es)
                                        <span class="desc">{{ $categoria->description_es }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><span class="card-title">Relaciones del denunciante</span></div>
                <div style="padding:16px 20px;">
                    <div class="cfg-opciones">
                        @foreach ($relaciones as $relacion)
                            <label class="cfg-opcion">
                                <input type="checkbox" name="relaciones[]" value="{{ $relacion->id }}"
                                       @checked(in_array($relacion->id, $seleccionRelaciones))>
                                <span>{{ $relacion->name_es }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </form>

    {{-- ══ ÁREAS Y CARGOS ══ --}}
    @elseif ($tab === 'areas')
        <form method="POST" action="{{ route('admin.configuracion.areas', $empresa) }}">
            @csrf

            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><span class="card-title">Áreas / Sectores</span></div>
                <div style="padding:16px 20px;">
                    <div class="cfg-opciones">
                        @foreach ($areas as $area)
                            <label class="cfg-opcion">
                                <input type="checkbox" name="areas[]" value="{{ $area->id }}"
                                       @checked(in_array($area->id, $seleccionAreas))>
                                <span>{{ $area->name_es }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><span class="card-title">Cargos / Posiciones</span></div>
                <div style="padding:16px 20px;">
                    <div class="cfg-opciones">
                        @foreach ($cargos as $cargo)
                            <label class="cfg-opcion">
                                <input type="checkbox" name="cargos[]" value="{{ $cargo->id }}"
                                       @checked(in_array($cargo->id, $seleccionCargos))>
                                <span>{{ $cargo->name_es }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </form>

    {{-- ══ CONSTRUCTOR DE CUESTIONARIO ══ --}}
    @elseif ($tab === 'cuestionario')
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><span class="card-title">Categoría a configurar</span></div>
            <div style="padding:16px 20px;">
                @if ($categoriasHabilitadas->isEmpty())
                    <p style="font-size:13px;color:var(--text-light);">
                        Esta empresa todavía no tiene categorías habilitadas.
                        Configuralas en la primera pestaña.
                    </p>
                @else
                    <form method="GET" action="{{ route('admin.configuracion.index') }}"
                          style="display:flex;gap:10px;flex-wrap:wrap;">
                        <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                        <input type="hidden" name="tab" value="cuestionario">

                        <x-autocompletado
                            nombre="categoria_nombre"
                            nombre-oculto="categoria_id"
                            :valor="$categoriaSeleccionada?->name_es"
                            :valor-oculto="$categoriaSeleccionada?->id"
                            :opciones="$categoriasHabilitadas->pluck('name_es')"
                            :valores="$categoriasHabilitadas->pluck('id', 'name_es')"
                            :enviar-al-elegir="true"
                            placeholder="Buscar categoría..." />
                    </form>

                    @if ($categoriaSeleccionada)
                        <div style="margin-top:12px;font-size:12px;color:var(--text-light);">
                            @if ($versionActual > 0)
                                Versión actual: <strong>v{{ $versionActual }}</strong> ·
                                {{ $preguntas->count() }}
                                {{ \Illuminate\Support\Str::plural('pregunta', $preguntas->count()) }}
                            @else
                                Sin versión configurada para esta empresa.
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        </div>

        @if ($categoriaSeleccionada)
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <span class="card-title">Preguntas</span>

                    <form method="POST" action="{{ route('admin.configuracion.importar', $empresa) }}"
                          onsubmit="return confirm('¿Importar el catálogo base? Reemplaza el cuestionario actual de esta empresa por una versión nueva. Las denuncias ya respondidas conservan sus preguntas.');">
                        @csrf
                        <input type="hidden" name="categoria_id" value="{{ $categoriaSeleccionada->id }}">
                        <button type="submit" class="btn btn-secondary btn-sm">Importar desde catálogo</button>
                    </form>
                </div>

                <form method="POST" action="{{ route('admin.configuracion.cuestionario', $empresa) }}"
                      style="padding:16px 20px;">
                    @csrf
                    <input type="hidden" name="categoria_id" value="{{ $categoriaSeleccionada->id }}">

                    <div id="lista-preguntas">
                        @foreach ($preguntas as $indice => $pregunta)
                            <div class="pq-fila">
                                <span class="pq-origen {{ $pregunta->catalog_question_id ? 'pq-catalogo' : 'pq-propia' }}">
                                    {{ $pregunta->catalog_question_id ? 'CATÁLOGO' : 'PROPIA' }}
                                </span>

                                <textarea name="preguntas[{{ $indice }}][texto]" rows="2"
                                          maxlength="2000" required>{{ $pregunta->question_text_es }}</textarea>

                                <input type="hidden" name="preguntas[{{ $indice }}][catalogo_id]"
                                       value="{{ $pregunta->catalog_question_id }}">

                                <button type="button" class="btn btn-secondary btn-sm"
                                        onclick="this.parentElement.remove()"
                                        style="margin-top:6px;">Quitar</button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-secondary btn-sm" onclick="agregarPregunta()"
                            style="margin:8px 0 16px;">
                        + Agregar pregunta propia
                    </button>

                    <div style="padding:12px 14px;background:#f5f5f5;border-radius:8px;font-size:12px;color:var(--text-light);line-height:1.6;margin-bottom:16px;">
                        Las marcadas como <strong>CATÁLOGO</strong> siguen recibiendo las correcciones
                        globales. Las <strong>PROPIAS</strong> son exclusivas de esta empresa.
                        Al guardar se crea una versión nueva; la anterior queda desactivada y las
                        denuncias ya respondidas conservan sus preguntas.
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar como nueva versión</button>
                </form>
            </div>

            <script>
                let indicePregunta = {{ $preguntas->count() }};

                function agregarPregunta() {
                    const lista = document.getElementById('lista-preguntas');
                    const fila = document.createElement('div');
                    fila.className = 'pq-fila';
                    fila.innerHTML = `
                        <span class="pq-origen pq-propia">PROPIA</span>
                        <textarea name="preguntas[${indicePregunta}][texto]" rows="2" maxlength="2000" required
                                  placeholder="¿Qué se le pregunta al denunciante?"></textarea>
                        <input type="hidden" name="preguntas[${indicePregunta}][catalogo_id]" value="">
                        <button type="button" class="btn btn-secondary btn-sm"
                                onclick="this.parentElement.remove()" style="margin-top:6px;">Quitar</button>`;
                    lista.appendChild(fila);
                    indicePregunta++;
                }
            </script>
        @endif

    {{-- ══ LEGAL Y NOTIFICACIONES ══ --}}
    @else
        <form method="POST" action="{{ route('admin.configuracion.legal', $empresa) }}">
            @csrf

            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><span class="card-title">Aviso de privacidad</span></div>

                <div style="padding:16px 20px;">
                    <p style="font-size:12px;color:var(--text-light);margin-bottom:12px;">
                        Es el texto que el denunciante acepta antes de enviar su denuncia.
                        Cada cambio se publica como una versión nueva, y cada denuncia queda
                        vinculada a la versión que aceptó.
                    </p>

                    <textarea name="aviso_privacidad" rows="10" maxlength="20000"
                              style="width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;">{{ old('aviso_privacidad', $avisoPrivacidad) }}</textarea>

                    @if ($versionesAviso->isNotEmpty())
                        <div style="margin-top:14px;">
                            <div style="font-size:11px;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">
                                Versiones publicadas
                            </div>
                            @foreach ($versionesAviso as $documento)
                                <div style="font-size:12px;color:var(--text-light);padding:4px 0;">
                                    <strong style="color:var(--dark);">{{ $documento->version }}</strong>
                                    · {{ $documento->published_at->format('d/m/Y H:i') }}
                                    @if ($documento->is_active)
                                        <span class="badge-activo" style="margin-left:6px;">VIGENTE</span>
                                    @endif
                                    <code style="font-size:10px;color:#bbb;margin-left:6px;">
                                        {{ substr($documento->content_hash, 0, 12) }}
                                    </code>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><span class="card-title">Notificaciones</span></div>

                <div style="padding:16px 20px;">
                    <p style="font-size:12px;color:var(--text-light);margin-bottom:12px;">
                        Direcciones que reciben aviso ante cada denuncia nueva.
                        Separadas por coma.
                    </p>

                    <input type="text" name="emails_alerta" maxlength="2000"
                           placeholder="compliance@empresa.com, legales@empresa.com"
                           value="{{ old('emails_alerta', $emailsAlerta) }}"
                           style="width:100%;padding:10px 13px;border:1px solid var(--border);border-radius:8px;font-size:13px;">

                    <div style="margin-top:12px;padding:10px 12px;background:#fff3cd;border:1px solid #ffeeba;border-radius:8px;font-size:12px;color:#856404;">
                        El envío automático de estas alertas todavía no está implementado.
                        Las direcciones se guardan y quedan listas para cuando se active.
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </form>
    @endif

    @endif {{-- cierra el @if (! $empresa) --}}

    <script>
        function copiarCanal(boton) {
            const campo = document.getElementById('link-canal');
            campo.select();
            navigator.clipboard.writeText(campo.value).then(() => {
                const texto = boton.textContent;
                boton.textContent = '✓ Copiado';
                setTimeout(() => { boton.textContent = texto; }, 1800);
            });
        }
    </script>

</x-admin.layout>
