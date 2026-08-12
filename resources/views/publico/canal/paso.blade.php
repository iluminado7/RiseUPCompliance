<x-publico.layout :titulo="'Denunciar — ' . $empresa->name" :empresa="$empresa">

    @php
        $total = count($etiquetas);
        $anterior = fn (string $campo, $porDefecto = null) => old($campo, $guardado[$campo] ?? $porDefecto);
    @endphp

    <style>
        .cn-pasos { display:flex; gap:5px; margin-bottom:8px; }
        .cn-paso {
            flex:1; height:5px; border-radius:999px; background:#e0ddd2;
        }
        .cn-paso.hecho { background:var(--gold-dark); }
        .cn-paso.actual { background:var(--gold); }
        .cn-paso-info {
            display:flex; justify-content:space-between; font-size:12px;
            color:var(--text-light); margin-bottom:22px;
        }
        .cn-nav { display:flex; gap:10px; margin-top:6px; }
        .cn-nav .cn-btn-principal { flex:1; }
        .cn-resumen-fila {
            display:flex; justify-content:space-between; gap:14px;
            padding:9px 0; border-bottom:1px solid #f0f0f0; font-size:14px;
        }
        .cn-resumen-fila span:first-child { color:var(--text-light); }
        .cn-resumen-fila span:last-child { text-align:right; font-weight:600; }
    </style>

    <div class="cn-pasos">
        @foreach ($etiquetas as $numero => $etiqueta)
            <div class="cn-paso {{ $numero < $paso ? 'hecho' : ($numero === $paso ? 'actual' : '') }}"></div>
        @endforeach
    </div>

    <div class="cn-paso-info">
        <span>{{ $etiquetas[$paso] }}</span>
        <span>Paso {{ $paso }} de {{ $total }}</span>
    </div>

    @if ($errors->any())
        <div class="cn-error">
            @foreach ($errors->all() as $mensaje)
                <div>{{ $mensaje }}</div>
            @endforeach
        </div>
    @endif

    @if (session('error'))
        <div class="cn-error">{{ session('error') }}</div>
    @endif

    @if (session('estado'))
        <div class="cn-ok">{{ session('estado') }}</div>
    @endif

    <form method="POST" action="{{ route('canal.guardar', [$empresa->slug, $paso]) }}"
          @if ($paso === 6) enctype="multipart/form-data" @endif>
        @csrf

        {{-- Campo trampa: invisible para una persona, irresistible para un
             formulario automatizado. --}}
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off"
               style="position:absolute;left:-9999px;opacity:0;" aria-hidden="true">

        {{-- ══ PASO 1 — ANONIMATO ══ --}}
        @if ($paso === 1)
            {{-- Sin esto, alguien que se equivocó de empresa en el buscador
                 no tendría cómo darse cuenta hasta el resumen final, y para
                 entonces ya cargó todo. --}}
            <div class="cn-card" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <span style="font-size:14px;">
                    <strong>{{ $empresa->name }}</strong>
                </span>
                <a href="{{ route('portada.selector') }}"
                   style="font-size:13px;color:var(--gold-dark);">Cambiar empresa</a>
            </div>

            <div class="cn-card">
                <h2>¿Querés mantener el anonimato?</h2>
                <p class="ayuda">
                    Podés denunciar sin identificarte. Si elegís hacerlo, no vamos
                    a pedirte ningún dato personal.
                </p>

                <div style="display:flex;flex-direction:column;gap:10px;">
                    <label style="display:flex;align-items:center;gap:10px;font-size:14px;cursor:pointer;">
                        <input type="radio" name="anonimo" value="si" required
                               @checked($anterior('anonimo', 'si') === 'si')
                               onchange="alternarDatos()">
                        Sí, quiero permanecer anónimo
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;font-size:14px;cursor:pointer;">
                        <input type="radio" name="anonimo" value="no"
                               @checked($anterior('anonimo') === 'no')
                               onchange="alternarDatos()">
                        Prefiero dejar mis datos de contacto
                    </label>
                </div>

                <div id="aviso-anonimo" class="cn-aviso" style="margin-top:16px;">
                    <strong>Vas a denunciar de forma anónima.</strong>
                    Evitá incluir tu nombre, documento, correo o teléfono en las
                    respuestas y en los archivos que adjuntes. Si lo hacés, tu
                    identidad podría quedar expuesta.
                </div>

                <div id="datos-personales" style="display:none;margin-top:16px;">
                    <div class="cn-grid">
                        <div class="campo">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" maxlength="30"
                                   value="{{ $anterior('nombre') }}">
                        </div>
                        <div class="campo">
                            <label for="apellido">Apellido *</label>
                            <input type="text" id="apellido" name="apellido" maxlength="30"
                                   value="{{ $anterior('apellido') }}">
                        </div>
                        <div class="campo">
                            <label for="email">Correo electrónico *</label>
                            <input type="email" id="email" name="email" maxlength="255"
                                   value="{{ $anterior('email') }}">
                            <span class="nota">Es lo que permite que el equipo te escriba.</span>
                        </div>
                        <div class="campo">
                            <label for="telefono">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" maxlength="20"
                                   value="{{ $anterior('telefono') }}">
                        </div>
                        <div class="campo">
                            <label for="documento">DNI o cédula</label>
                            <input type="text" id="documento" name="documento" maxlength="20"
                                   value="{{ $anterior('documento') }}">
                        </div>
                        <div class="campo">
                            <label for="genero">Género</label>
                            <input type="text" id="genero" name="genero" maxlength="50"
                                   value="{{ $anterior('genero') }}">
                        </div>

                        @if ($vinculos->isNotEmpty())
                            <div class="campo ancho">
                                <label for="relationship_id">Tu relación con la empresa</label>
                                <select id="relationship_id" name="relationship_id">
                                    <option value="">— Elegir —</option>
                                    @foreach ($vinculos as $vinculo)
                                        <option value="{{ $vinculo->id }}"
                                            @selected((int) $anterior('relationship_id') === $vinculo->id)>
                                            {{ $vinculo->name_es }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <script>
                function alternarDatos() {
                    const anonimo = document.querySelector('input[name="anonimo"]:checked')?.value === 'si';

                    document.getElementById('datos-personales').style.display = anonimo ? 'none' : 'block';
                    document.getElementById('aviso-anonimo').style.display = anonimo ? 'block' : 'none';

                    // Los campos ocultos dejan de ser obligatorios: si no, el
                    // navegador bloquea el envío por un campo que no se ve.
                    ['nombre', 'apellido', 'email'].forEach(id => {
                        const campo = document.getElementById(id);
                        if (campo) campo.required = !anonimo;
                    });
                }

                alternarDatos();
            </script>

        {{-- ══ PASO 2 — SUCURSAL ══ --}}
        @elseif ($paso === 2)
            <div class="cn-card">
                <h2>¿Dónde ocurrió?</h2>

                @if ($sucursales->isEmpty())
                    <p class="ayuda">Esta empresa no tiene sucursales cargadas. Podés continuar.</p>
                @else
                    <div class="campo">
                        <label for="branch_id">Sucursal</label>
                        <select id="branch_id" name="branch_id">
                            <option value="">— Prefiero no decirlo —</option>
                            @foreach ($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}"
                                    @selected((int) $anterior('branch_id') === $sucursal->id)>
                                    {{ $sucursal->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

        {{-- ══ PASO 3 — DENUNCIADO ══ --}}
        @elseif ($paso === 3)
            <div class="cn-card">
                <h2>¿A quién denunciás?</h2>
                <p class="ayuda">
                    Si no sabés quién es, escribí "No especificado". Cuanta más
                    información aportes, más precisa va a ser la investigación.
                </p>

                <div class="cn-grid">
                    <div class="campo">
                        <label for="denunciado_nombre">Nombre *</label>
                        <input type="text" id="denunciado_nombre" name="denunciado_nombre"
                               maxlength="50" required
                               value="{{ $anterior('denunciado_nombre', 'No especificado') }}">
                    </div>

                    <div class="campo">
                        <label for="denunciado_apellido">Apellido *</label>
                        <input type="text" id="denunciado_apellido" name="denunciado_apellido"
                               maxlength="50" required
                               value="{{ $anterior('denunciado_apellido', 'No especificado') }}">
                    </div>

                    @if ($areas->isNotEmpty())
                        <div class="campo">
                            <label for="area_id">Sector</label>
                            <select id="area_id" name="area_id">
                                <option value="">— Sin especificar —</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}"
                                        @selected((int) $anterior('area_id') === $area->id)>
                                        {{ $area->name_es }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($cargos->isNotEmpty())
                        <div class="campo">
                            <label for="position_id">Cargo</label>
                            <select id="position_id" name="position_id">
                                <option value="">— Sin especificar —</option>
                                @foreach ($cargos as $cargo)
                                    <option value="{{ $cargo->id }}"
                                        @selected((int) $anterior('position_id') === $cargo->id)>
                                        {{ $cargo->name_es }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

        {{-- ══ PASO 4 — CATEGORÍA ══ --}}
        @elseif ($paso === 4)
            <div class="cn-card">
                <h2>¿Qué querés reportar?</h2>

                @if ($categorias->isEmpty())
                    <p class="ayuda">
                        Este canal todavía no tiene categorías configuradas.
                        Comunicate con la empresa.
                    </p>
                @else
                    <p class="ayuda">
                        Elegí el tipo de irregularidad. Si necesitás reportar más de una,
                        podés enviar una denuncia por cada tipo.
                    </p>

                    <div style="display:flex;flex-direction:column;gap:10px;">
                        @foreach ($categorias as $categoria)
                            <label style="display:flex;align-items:flex-start;gap:11px;padding:13px 15px;border:1px solid var(--border);border-radius:10px;cursor:pointer;font-size:14px;">
                                <input type="radio" name="category_id" value="{{ $categoria->id }}" required
                                       @checked((int) $anterior('category_id') === $categoria->id)
                                       style="margin-top:3px;">
                                <span>
                                    <strong>{{ $categoria->name_es }}</strong>
                                    @if ($categoria->description_es)
                                        <span style="display:block;font-size:12px;color:var(--text-light);margin-top:3px;">
                                            {{ $categoria->description_es }}
                                        </span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

        {{-- ══ PASO 5 — DETALLES ══ --}}
        @elseif ($paso === 5)
            <div class="cn-card">
                <h2>Contanos qué pasó</h2>

                @if (($completo['anonimo'] ?? 'si') === 'si')
                    <div class="cn-aviso">
                        <strong>Estás respondiendo de forma anónima.</strong>
                        Evitá incluir tu nombre, documento, correo o teléfono en las
                        respuestas. Si lo hacés, tu identidad podría quedar expuesta.
                    </div>
                @endif

                <div class="campo" style="margin-bottom:18px;">
                    <label for="incident_date">¿Cuándo ocurrió? *</label>
                    <input type="date" id="incident_date" name="incident_date" required
                           max="{{ now()->toDateString() }}"
                           value="{{ $anterior('incident_date') }}">
                </div>

                @forelse ($preguntas as $indice => $enunciado)
                    <div class="campo" style="margin-bottom:16px;">
                        <label for="respuesta-{{ $indice }}">{{ $enunciado }}</label>
                        <textarea id="respuesta-{{ $indice }}" name="respuestas[{{ $indice }}]"
                                  rows="3" maxlength="5000">{{ $anterior('respuestas')[$indice] ?? '' }}</textarea>
                    </div>
                @empty
                    <p class="ayuda">
                        Esta categoría no tiene preguntas configuradas. Podés continuar.
                    </p>
                @endforelse
            </div>

        {{-- ══ PASO 6 — EVIDENCIA ══ --}}
        @elseif ($paso === 6)
            <div class="cn-card">
                <h2>Adjuntar evidencia</h2>
                <p class="ayuda">Opcional. Un archivo por denuncia, hasta 25 MB.</p>

                @if ($adjunto)
                    <div style="padding:13px 15px;background:#f0f7f0;border:1px solid #c3e6cb;border-radius:8px;font-size:13px;color:#155724;margin-bottom:14px;">
                        Archivo cargado: <strong>{{ $adjunto['nombre_original'] }}</strong>
                        <label style="display:flex;align-items:center;gap:8px;margin-top:10px;cursor:pointer;color:#b00020;">
                            <input type="checkbox" name="quitar_archivo" value="1">
                            Quitar este archivo
                        </label>
                    </div>
                @endif

                <div class="campo">
                    <label for="archivo">{{ $adjunto ? 'Reemplazar por otro' : 'Elegir archivo' }}</label>
                    <input type="file" id="archivo" name="archivo"
                           accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
                    <span class="nota">Imágenes, PDF o Word.</span>
                </div>

                <div class="cn-aviso" style="margin-top:14px;margin-bottom:0;">
                    <strong>Sobre las fotos.</strong>
                    A las imágenes les quitamos automáticamente los datos ocultos
                    (ubicación, modelo del teléfono, fecha). A los PDF y documentos
                    de Word todavía no: si denunciás de forma anónima, revisá que no
                    lleven tu nombre en las propiedades del archivo.
                </div>
            </div>

        {{-- ══ PASO 7 — REVISAR Y ENVIAR ══ --}}
        @else
            <div class="cn-card">
                <h2>Revisá antes de enviar</h2>
                <p class="ayuda">
                    Si algo no está bien, volvé atrás y corregilo. Una vez enviada
                    no vas a poder modificarla.
                </p>

                <div class="cn-resumen-fila">
                    <span>Anonimato</span>
                    <span>{{ $resumen['anonimo'] ? 'Denuncia anónima' : 'Con mis datos' }}</span>
                </div>

                @unless ($resumen['anonimo'])
                    <div class="cn-resumen-fila">
                        <span>Contacto</span>
                        <span>{{ $resumen['contacto'] }} · {{ $resumen['email'] }}</span>
                    </div>
                @endunless

                @if ($resumen['sucursal'])
                    <div class="cn-resumen-fila">
                        <span>Sucursal</span>
                        <span>{{ $resumen['sucursal'] }}</span>
                    </div>
                @endif

                <div class="cn-resumen-fila">
                    <span>Denunciado</span>
                    <span>
                        {{ $resumen['denunciado'] }}
                        @if ($resumen['area'] || $resumen['cargo'])
                            <span style="display:block;font-weight:400;font-size:12px;color:var(--text-light);">
                                {{ collect([$resumen['area'], $resumen['cargo']])->filter()->join(' · ') }}
                            </span>
                        @endif
                    </span>
                </div>

                <div class="cn-resumen-fila">
                    <span>Tipo</span>
                    <span>{{ $resumen['categoria'] }}</span>
                </div>

                <div class="cn-resumen-fila">
                    <span>Fecha del hecho</span>
                    <span>{{ $resumen['fecha'] }}</span>
                </div>

                <div class="cn-resumen-fila">
                    <span>Respuestas</span>
                    <span>{{ count($resumen['respuestas']) }} completadas</span>
                </div>

                <div class="cn-resumen-fila" style="border-bottom:none;">
                    <span>Adjunto</span>
                    <span>{{ $adjunto['nombre_original'] ?? 'Sin archivo' }}</span>
                </div>
            </div>

            <div class="cn-card">
                <h2>Aviso de privacidad</h2>

                @if ($avisoPrivacidad)
                    <div style="max-height:200px;overflow-y:auto;padding:14px;background:#fafafa;border:1px solid var(--border);border-radius:8px;font-size:13px;line-height:1.6;margin-bottom:14px;">
                        {!! nl2br(e($avisoPrivacidad)) !!}
                    </div>
                @else
                    <p class="ayuda">
                        Tus datos se tratan de forma confidencial y solo se usan para
                        investigar lo que reportás.
                    </p>
                @endif

                <label style="display:flex;align-items:flex-start;gap:10px;font-size:14px;cursor:pointer;">
                    <input type="checkbox" name="acepta_privacidad" value="1" required
                           style="margin-top:3px;">
                    Leí y acepto el aviso de privacidad *
                </label>
            </div>
        @endif

        <div class="cn-nav">
            @if ($paso > 1)
                <a href="{{ route('canal.volver', [$empresa->slug, $paso]) }}"
                   class="cn-btn cn-btn-secundario">← Atrás</a>
            @endif

            <button type="submit" class="cn-btn cn-btn-principal">
                {{ $paso === $total ? 'Enviar denuncia' : 'Continuar' }}
            </button>
        </div>
    </form>

    @if ($paso > 1)
        <form method="POST" action="{{ route('canal.abandonar', $empresa->slug) }}"
              style="margin-top:14px;text-align:center;"
              onsubmit="return confirm('¿Descartar todo lo que cargaste? No se puede recuperar.');">
            @csrf
            <button type="submit"
                    style="background:none;border:none;font-size:12px;color:var(--text-light);cursor:pointer;text-decoration:underline;">
                Descartar y empezar de nuevo
            </button>
        </form>
    @endif

    @if ($paso === $total)
        <p style="font-size:12px;color:var(--text-light);text-align:center;margin-top:12px;">
            Al enviar vas a recibir un código de seguimiento. Guardalo:
            es lo único que te permite consultar el estado de tu denuncia.
        </p>
    @endif

</x-publico.layout>
