@props([
    'nombre',
    'opciones' => [],
    'valor' => '',
    'placeholder' => 'Buscar...',
    'deshabilitado' => false,

    // Cuando este campo depende de otro (empresa -> sucursal), 'padre' es
    // el nombre del campo del que depende, y 'grupos' un mapa
    // valorDelPadre => [opciones].
    'padre' => null,
    'grupos' => null,

    // Valor oculto: mapa texto => id. Al elegir una opción se completa un
    // input hidden con el id. Sirve cuando la elección no es un filtro de
    // texto sino la selección de un registro concreto.
    'valores' => null,
    'nombreOculto' => null,
    'valorOculto' => null,

    // Envía el formulario al elegir una opción. Reemplaza al
    // onchange="this.form.submit()" de los <select>.
    'enviarAlElegir' => false,
])

@php
    $id = 'ac-' . $nombre;

    // El array se arma acá y se pasa en una línea: @json con argumentos
    // multilínea rompe el parser de Blade.
    $datosJson = [
        'opciones' => array_values($opciones instanceof \Illuminate\Support\Collection ? $opciones->all() : (array) $opciones),
        'grupos' => $grupos instanceof \Illuminate\Support\Collection ? $grupos->all() : $grupos,
        'valores' => $valores instanceof \Illuminate\Support\Collection ? $valores->all() : $valores,
    ];
@endphp

<div class="ac-wrap" data-ac="{{ $nombre }}"
     @if ($padre) data-ac-padre="{{ $padre }}" @endif
     @if ($enviarAlElegir) data-ac-enviar="1" @endif
     style="position:relative;flex:1;min-width:180px;">

    <input type="text"
           id="{{ $id }}"
           @if (! $nombreOculto) name="{{ $nombre }}" @endif
           value="{{ $valor }}"
           placeholder="{{ $placeholder }}"
           autocomplete="off"
           role="combobox"
           aria-expanded="false"
           aria-autocomplete="list"
           @disabled($deshabilitado)
           class="ac-input"
           style="width:100%;{{ $deshabilitado ? 'opacity:.5;cursor:not-allowed;' : '' }}">

    @if ($nombreOculto)
        <input type="hidden" name="{{ $nombreOculto }}" value="{{ $valorOculto }}" class="ac-oculto">
    @endif

    <ul class="ac-lista" role="listbox" hidden></ul>

    <script type="application/json" class="ac-datos">@json($datosJson)</script>
</div>

@once
    <style>
        /*
         * El .card de admin.css tiene overflow:hidden para redondear las
         * esquinas de las tablas, y eso RECORTA la lista desplegable --
         * no la tapa. Por eso subir el z-index no servía de nada.
         *
         * :has() deja desbordar solo a las tarjetas que contienen un
         * autocompletado; el resto conserva su overflow.
         */
        .card:has(.ac-wrap),
        .card:has(.ac-wrap) > form,
        .card:has(.ac-wrap) > div,
        .page-content:has(.ac-wrap) { overflow: visible; }

        .ac-input {
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
        .ac-input:focus { border-color: var(--gold); }
        .ac-input::placeholder { color: #bbb; }
        .ac-input:disabled { background: #f5f5f5; }

        .ac-lista {
            position:absolute; top:calc(100% + 4px); left:0; right:0;
            background:#fff; border:1px solid var(--border); border-radius:8px;
            box-shadow:0 8px 28px rgba(0,0,0,.16);
            max-height:240px; overflow-y:auto; z-index:600;
            list-style:none; margin:0; padding:4px 0;
        }
        .ac-lista[hidden] { display:none; }
        .ac-opcion {
            padding:9px 14px; font-size:13px; cursor:pointer; color:var(--text);
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        }
        .ac-opcion:hover, .ac-opcion.activa { background:rgba(191,174,118,.18); }
        .ac-opcion mark { background:transparent; color:var(--gold-dark); font-weight:700; }
        .ac-vacio { padding:10px 14px; font-size:12px; color:var(--text-light); }
    </style>

    <script>
    (function () {
        // Coincidencia en cualquier parte del texto, sin distinguir acentos
        // ni mayúsculas. El <datalist> nativo no garantiza esto: Chrome
        // busca la subcadena, Firefox y Safari coinciden desde el principio.
        const normalizar = (s) => (s || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();

        function resaltar(texto, consulta) {
            const el = document.createElement('span');
            if (!consulta) { el.textContent = texto; return el; }

            const pos = normalizar(texto).indexOf(normalizar(consulta));
            if (pos === -1) { el.textContent = texto; return el; }

            // Se arma con nodos de texto y no con innerHTML: los nombres
            // vienen de la base y podrían contener markup.
            el.append(document.createTextNode(texto.slice(0, pos)));
            const marca = document.createElement('mark');
            marca.textContent = texto.slice(pos, pos + consulta.length);
            el.append(marca, document.createTextNode(texto.slice(pos + consulta.length)));
            return el;
        }

        class Autocompletado {
            constructor(wrap) {
                this.wrap = wrap;
                this.input = wrap.querySelector('.ac-input');
                this.lista = wrap.querySelector('.ac-lista');
                this.oculto = wrap.querySelector('.ac-oculto');
                this.enviar = wrap.dataset.acEnviar === '1';

                const datos = JSON.parse(wrap.querySelector('.ac-datos').textContent);
                this.opciones = datos.opciones || [];
                this.grupos = datos.grupos || null;
                this.valores = datos.valores || null;

                this.indice = -1;
                this.enlazar();
            }

            enlazar() {
                this.input.addEventListener('input', () => {
                    // Si el texto deja de coincidir con una opción, el id
                    // oculto queda obsoleto: se limpia.
                    if (this.oculto && this.valores && !(this.input.value in this.valores)) {
                        this.oculto.value = '';
                    }
                    this.abrir();
                });
                this.input.addEventListener('focus', () => this.abrir());
                this.input.addEventListener('keydown', (e) => this.tecla(e));

                document.addEventListener('click', (e) => {
                    if (!this.wrap.contains(e.target)) this.cerrar();
                });
            }

            /** Opciones vigentes: si depende de un padre, las de su valor. */
            disponibles() {
                if (!this.grupos) return this.opciones;

                const padre = document.querySelector('[data-ac="' + this.wrap.dataset.acPadre + '"] .ac-input');
                const clave = padre ? padre.value.trim() : '';

                return this.grupos[clave] || [];
            }

            abrir() {
                const consulta = this.input.value.trim();
                const todas = this.disponibles();

                const coincidencias = consulta
                    ? todas.filter(o => normalizar(o).includes(normalizar(consulta)))
                    : todas;

                this.lista.replaceChildren();
                this.indice = -1;

                if (!coincidencias.length) {
                    const vacio = document.createElement('li');
                    vacio.className = 'ac-vacio';
                    vacio.textContent = consulta ? 'Sin coincidencias' : 'Sin opciones';
                    this.lista.append(vacio);
                    this.lista.hidden = false;
                    this.input.setAttribute('aria-expanded', 'true');
                    return;
                }

                coincidencias.slice(0, 50).forEach((texto) => {
                    const li = document.createElement('li');
                    li.className = 'ac-opcion';
                    li.setAttribute('role', 'option');
                    li.append(resaltar(texto, consulta));
                    li.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        this.elegir(texto);
                    });
                    this.lista.append(li);
                });

                this.lista.hidden = false;
                this.input.setAttribute('aria-expanded', 'true');
            }

            cerrar() {
                this.lista.hidden = true;
                this.input.setAttribute('aria-expanded', 'false');
                this.indice = -1;
            }

            elegir(texto) {
                this.input.value = texto;

                if (this.oculto && this.valores) {
                    this.oculto.value = this.valores[texto] ?? '';
                }

                this.cerrar();
                this.input.dispatchEvent(new Event('ac:elegido', { bubbles: true }));

                if (this.enviar) {
                    this.input.closest('form')?.submit();
                }
            }

            tecla(e) {
                const opciones = [...this.lista.querySelectorAll('.ac-opcion')];

                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (this.lista.hidden) { this.abrir(); return; }

                    this.indice += e.key === 'ArrowDown' ? 1 : -1;
                    if (this.indice < 0) this.indice = opciones.length - 1;
                    if (this.indice >= opciones.length) this.indice = 0;

                    opciones.forEach((o, i) => o.classList.toggle('activa', i === this.indice));
                    opciones[this.indice]?.scrollIntoView({ block: 'nearest' });
                    return;
                }

                if (e.key === 'Enter' && this.indice >= 0 && !this.lista.hidden) {
                    e.preventDefault();
                    opciones[this.indice].dispatchEvent(new Event('mousedown'));
                    return;
                }

                if (e.key === 'Escape') this.cerrar();
            }
        }

        function iniciar() {
            const instancias = new Map();

            document.querySelectorAll('.ac-wrap').forEach((wrap) => {
                instancias.set(wrap.dataset.ac, new Autocompletado(wrap));
            });

            // Encadenado: al cambiar el padre, el hijo se limpia y se
            // habilita o deshabilita según corresponda.
            instancias.forEach((instancia) => {
                const nombrePadre = instancia.wrap.dataset.acPadre;
                if (!nombrePadre) return;

                const padre = instancias.get(nombrePadre);
                if (!padre) return;

                const sincronizar = () => {
                    const tienePadre = padre.input.value.trim() !== '';
                    instancia.input.value = '';
                    if (instancia.oculto) instancia.oculto.value = '';
                    instancia.input.disabled = !tienePadre;
                    instancia.input.style.opacity = tienePadre ? '' : '.5';
                    instancia.input.style.cursor = tienePadre ? '' : 'not-allowed';
                    instancia.input.placeholder = tienePadre
                        ? 'Buscar...'
                        : 'Elegí una empresa primero';
                    instancia.cerrar();
                };

                padre.input.addEventListener('ac:elegido', sincronizar);
                padre.input.addEventListener('change', sincronizar);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', iniciar);
        } else {
            iniciar();
        }
    })();
    </script>
@endonce
