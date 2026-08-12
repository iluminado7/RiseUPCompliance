<x-admin.layout titulo="Ayuda y soporte">

    <style>
        .ay-cols { display:grid; grid-template-columns:220px 1fr; gap:24px; align-items:start; }
        .ay-indice {
            position:sticky; top:20px; padding:16px 18px; background:#fff;
            border:1px solid var(--border); border-radius:12px;
        }
        .ay-indice a {
            display:block; padding:6px 0; font-size:13px; color:var(--text-light);
            text-decoration:none; border-bottom:1px solid #f2f2f2;
        }
        .ay-indice a:last-child { border-bottom:none; }
        .ay-indice a:hover { color:var(--gold-dark); }
        .ay-buscar {
            width:100%; padding:10px 14px; border:1px solid var(--border);
            border-radius:10px; font-size:13px; font-family:inherit; outline:none;
            margin-bottom:18px;
        }
        .ay-buscar:focus { border-color:var(--gold); }
        .ay-articulo { padding:18px 0; border-bottom:1px solid var(--border); }
        .ay-articulo:last-child { border-bottom:none; }
        .ay-titulo { font-size:15px; font-weight:700; color:var(--dark); margin-bottom:8px; }
        .ay-articulo p { font-size:13px; color:var(--text-light); line-height:1.7; margin:0 0 10px; }
        .ay-articulo ul { margin:0 0 10px; padding-left:20px; }
        .ay-articulo li { font-size:13px; color:var(--text-light); line-height:1.7; margin-bottom:5px; }
        .ay-aviso {
            padding:10px 14px; border-radius:8px; font-size:12px; line-height:1.6;
            background:#e8f0fe; border-left:3px solid #1a5fb4; color:#1a5fb4;
        }
        .ay-aviso.ojo { background:#fff8e1; border-left-color:var(--gold); color:#856404; }
        .ay-vacio { padding:24px; text-align:center; font-size:13px; color:var(--text-light); }
        @media (max-width:900px) {
            .ay-cols { grid-template-columns:1fr; }
            .ay-indice { position:static; }
        }
    </style>

    <div class="ay-cols">

        <div class="ay-indice">
            @foreach ($secciones as $seccion)
                <a href="#{{ $seccion['id'] }}">{{ $seccion['titulo'] }}</a>
            @endforeach
        </div>

        <div>
            <input type="text" class="ay-buscar" id="ay-buscar"
                   placeholder="Buscar en la ayuda…" autocomplete="off">

            <div id="ay-sin-resultados" class="card ay-vacio" style="display:none;">
                No hay nada que coincida con esa búsqueda.
            </div>

            @foreach ($secciones as $seccion)
                <div class="card ay-seccion" id="{{ $seccion['id'] }}" style="margin-bottom:20px;">
                    <div class="card-header">
                        <span class="card-title">{{ $seccion['titulo'] }}</span>
                    </div>

                    <div style="padding:4px 24px 20px;">
                        @foreach ($seccion['articulos'] as $articulo)
                            <div class="ay-articulo" id="{{ $articulo['id'] }}">
                                <div class="ay-titulo">{{ $articulo['titulo'] }}</div>

                                @foreach ($articulo['parrafos'] as $parrafo)
                                    <p>{{ $parrafo }}</p>
                                @endforeach

                                @if (! empty($articulo['lista']))
                                    <ul>
                                        @foreach ($articulo['lista'] as $punto)
                                            <li>{{ $punto }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if (! empty($articulo['aviso']))
                                    <div class="ay-aviso {{ ($articulo['tipo'] ?? 'info') === 'ojo' ? 'ojo' : '' }}">
                                        {{ $articulo['aviso'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div style="padding:14px 18px;background:#f7f7f7;border:1px solid var(--border);border-radius:10px;font-size:13px;color:var(--text-light);line-height:1.7;">
                ¿No encontraste lo que buscabas? Escribinos a
                <a href="mailto:{{ $emailSoporte }}" style="color:var(--gold-dark);font-weight:600;">{{ $emailSoporte }}</a>.
                No incluyas en el mail el contenido de una denuncia ni datos del denunciante.
            </div>
        </div>

    </div>

    <script>
        (function () {
            const buscador = document.getElementById('ay-buscar');
            const secciones = document.querySelectorAll('.ay-seccion');
            const vacio = document.getElementById('ay-sin-resultados');

            buscador.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                let visibles = 0;

                secciones.forEach(function (seccion) {
                    let enSeccion = 0;

                    seccion.querySelectorAll('.ay-articulo').forEach(function (art) {
                        const coincide = q === '' || art.textContent.toLowerCase().includes(q);
                        art.style.display = coincide ? '' : 'none';
                        if (coincide) { enSeccion++; }
                    });

                    seccion.style.display = enSeccion ? '' : 'none';
                    visibles += enSeccion;
                });

                vacio.style.display = visibles ? 'none' : '';
            });
        })();
    </script>

</x-admin.layout>
