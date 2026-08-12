<x-publico.layout titulo="Elegí la empresa">

    <style>
        .sl-wrap { position:relative; }
        .sl-input {
            width:100%; padding:14px 17px; border:1px solid var(--border);
            border-radius:10px; font-size:16px; font-family:inherit; outline:none;
        }
        .sl-input:focus { border-color:var(--gold); }
        .sl-lista {
            list-style:none; margin:10px 0 0; padding:0;
        }
        .sl-opcion {
            display:block; padding:14px 17px; border:1px solid var(--border);
            border-radius:10px; margin-bottom:8px; font-size:15px;
            text-decoration:none; color:var(--text); transition:border-color .15s;
        }
        .sl-opcion:hover { border-color:var(--gold); background:#fffdf6; }
        .sl-opcion mark { background:transparent; color:var(--gold-dark); font-weight:700; }
        .sl-aviso { font-size:13px; color:var(--text-light); padding:14px 2px; }
    </style>

    <div class="cn-card">
        <h2>¿Sobre qué empresa querés denunciar?</h2>
        <p class="ayuda">
            Escribí el nombre de la empresa. Si tenés el enlace directo del canal,
            podés usarlo en lugar de buscar acá.
        </p>

        {{-- El formulario funciona sin JavaScript: si está desactivado, el
             POST devuelve los resultados en la misma pantalla. --}}
        <form method="POST" action="{{ route('portada.ir') }}" class="sl-wrap" autocomplete="off">
            @csrf

            <input type="text" name="empresa" id="buscador" class="sl-input"
                   placeholder="Nombre de la empresa..."
                   value="{{ $busqueda ?? '' }}"
                   minlength="2" maxlength="200" required autofocus>

            <ul class="sl-lista" id="resultados">
                @isset ($resultados)
                    @forelse ($resultados as $empresa)
                        <li>
                            <a href="{{ route('canal.inicio', $empresa->slug) }}" class="sl-opcion">
                                {{ $empresa->name }}
                            </a>
                        </li>
                    @empty
                        <li class="sl-aviso">
                            No encontramos ninguna empresa con ese nombre. Revisá cómo
                            está escrito, o pedile el enlace directo a quien te derivó acá.
                        </li>
                    @endforelse
                @endisset
            </ul>

            <noscript>
                <button type="submit" class="cn-btn cn-btn-principal" style="margin-top:10px;">
                    Buscar
                </button>
            </noscript>
        </form>
    </div>

    <div class="cn-card" style="text-align:center;">
        <p style="font-size:14px;margin:0 0 12px;">¿Ya hiciste una denuncia?</p>
        <a href="{{ route('seguimiento.formulario') }}" class="cn-btn cn-btn-secundario">
            Consultar el estado
        </a>
    </div>

    <script>
        const BUSCAR = '{{ route('portada.buscar') }}';
        const MINIMO = 2;

        const campo = document.getElementById('buscador');
        const lista = document.getElementById('resultados');

        let temporizador = null;
        let ultimaConsulta = '';

        const normalizar = (s) => (s || '')
            .toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

        function resaltar(texto, consulta) {
            const el = document.createElement('span');
            const pos = normalizar(texto).indexOf(normalizar(consulta));

            if (pos === -1) { el.textContent = texto; return el; }

            // Nodos de texto y no innerHTML: el nombre viene de la base.
            el.append(document.createTextNode(texto.slice(0, pos)));
            const marca = document.createElement('mark');
            marca.textContent = texto.slice(pos, pos + consulta.length);
            el.append(marca, document.createTextNode(texto.slice(pos + consulta.length)));
            return el;
        }

        function mostrarAviso(texto) {
            lista.replaceChildren();
            const li = document.createElement('li');
            li.className = 'sl-aviso';
            li.textContent = texto;
            lista.appendChild(li);
        }

        async function buscar() {
            const consulta = campo.value.trim();

            if (consulta.length < MINIMO) {
                lista.replaceChildren();
                return;
            }

            if (consulta === ultimaConsulta) return;
            ultimaConsulta = consulta;

            try {
                const respuesta = await fetch(BUSCAR + '?q=' + encodeURIComponent(consulta), {
                    headers: { 'Accept': 'application/json' },
                });

                if (respuesta.status === 429) {
                    mostrarAviso('Demasiadas búsquedas seguidas. Esperá un momento.');
                    return;
                }

                const datos = await respuesta.json();

                lista.replaceChildren();

                if (!datos.empresas.length) {
                    mostrarAviso(
                        'No encontramos ninguna empresa con ese nombre. Revisá cómo está ' +
                        'escrito, o pedile el enlace directo a quien te derivó acá.'
                    );
                    return;
                }

                datos.empresas.forEach((empresa) => {
                    const li = document.createElement('li');
                    const enlace = document.createElement('a');
                    enlace.className = 'sl-opcion';
                    enlace.href = empresa.url;
                    enlace.append(resaltar(empresa.nombre, consulta));
                    li.appendChild(enlace);
                    lista.appendChild(li);
                });
            } catch (e) {
                mostrarAviso('No pudimos completar la búsqueda. Probá de nuevo.');
            }
        }

        // Se espera a que la persona deje de escribir: sin esto, cada tecla
        // sería una consulta y el rate limiting saltaría enseguida.
        campo.addEventListener('input', () => {
            clearTimeout(temporizador);
            temporizador = setTimeout(buscar, 280);
        });

        campo.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(temporizador);
                buscar();
            }
        });
    </script>

</x-publico.layout>
