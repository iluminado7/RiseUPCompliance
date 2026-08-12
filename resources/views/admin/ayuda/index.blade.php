<x-admin.layout titulo="Ayuda y soporte">

    <style>
        .doc-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
        .doc-card {
            background:#fff; border:1px solid var(--border); border-radius:12px;
            padding:20px 22px; display:flex; align-items:flex-start; gap:14px;
        }
        .doc-icon { font-size:24px; flex-shrink:0; margin-top:2px; }
        .doc-info h4 { font-size:14px; font-weight:700; color:var(--dark); margin-bottom:4px; }
        .doc-info p { font-size:13px; color:var(--text-light); line-height:1.5; }

        .manual-cta {
            background:linear-gradient(135deg,#1a1710 0%,#2d2820 100%);
            border-radius:12px; padding:28px 32px; display:flex; align-items:center;
            justify-content:space-between; gap:20px; flex-wrap:wrap; margin-bottom:24px;
        }
        .manual-cta h3 { color:#BFAE76; font-size:17px; font-weight:700; margin-bottom:6px; }
        .manual-cta p { color:rgba(255,255,255,.6); font-size:13px; line-height:1.6; }
        .btn-manual {
            background:#BFAE76; color:#1a1710; padding:12px 22px; border-radius:10px;
            font-size:14px; font-weight:700; text-decoration:none; white-space:nowrap;
            flex-shrink:0;
        }
        .btn-manual:hover { background:#c9b87e; }
        .btn-manual.inactivo { opacity:.45; cursor:not-allowed; }

        .faq-item {
            background:#fff; border:1px solid var(--border); border-radius:12px;
            margin-bottom:10px; overflow:hidden;
        }
        .faq-question {
            padding:16px 20px; font-size:14px; font-weight:600; color:var(--dark);
            cursor:pointer; display:flex; justify-content:space-between; align-items:center;
            user-select:none; gap:14px;
        }
        .faq-question:hover { background:#fafafa; }
        .faq-question .chevron { font-size:11px; color:var(--text-light); flex-shrink:0; transition:transform .2s; }
        .faq-item.open .faq-question { color:var(--gold-dark); }
        .faq-item.open .chevron { transform:rotate(180deg); }
        .faq-answer {
            display:none; padding:14px 20px 16px; font-size:13px; color:#555;
            line-height:1.7; border-top:1px solid var(--border);
        }
        .faq-item.open .faq-answer { display:block; }

        @media (max-width:768px) { .doc-grid { grid-template-columns:1fr; } }
    </style>

    <div class="manual-cta">
        <div>
            <h3>📋 Manual de uso completo</h3>
            <p>
                Guía detallada de todas las secciones del panel, organizada por rol.<br>
                @if ($manualDisponible)
                    Incluye ejemplos, casos frecuentes y referencia de cada funcionalidad.
                @else
                    En preparación. Mientras tanto, abajo están las preguntas frecuentes.
                @endif
            </p>
        </div>

        @if ($manualDisponible)
            <a href="{{ asset('docs/' . $archivoManual) }}" class="btn-manual" download>
                📥 Descargar manual — {{ $etiquetaRol }}
            </a>
        @else
            <span class="btn-manual inactivo">Manual en preparación</span>
        @endif
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="card-title">Guías disponibles para tu rol</span>
        </div>
        <div style="padding:0 24px 24px;">
            @if (empty($guias))
                <p style="color:var(--text-light);font-size:14px;">
                    No hay guías disponibles para tu rol.
                </p>
            @else
                <div class="doc-grid">
                    @foreach ($guias as $guia)
                        <div class="doc-card">
                            <div class="doc-icon">{{ $guia['icono'] }}</div>
                            <div class="doc-info">
                                <h4>{{ $guia['titulo'] }}</h4>
                                <p>{{ $guia['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <span class="card-title">Preguntas frecuentes</span>
        </div>
        <div style="padding:0 24px 24px;">
            @foreach ($preguntas as $pregunta)
                <div class="faq-item" id="{{ $pregunta['id'] }}">
                    <div class="faq-question" onclick="alternarFaq(this)">
                        {{ $pregunta['pregunta'] }}
                        <span class="chevron">▼</span>
                    </div>
                    <div class="faq-answer">{{ $pregunta['respuesta'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div style="padding:16px 20px;background:#f7f7f7;border:1px solid var(--border);border-radius:12px;font-size:13px;color:var(--text-light);line-height:1.7;">
        ¿No encontraste lo que buscabas? Escribinos a
        <a href="mailto:{{ $emailSoporte }}" style="color:var(--gold-dark);font-weight:600;">{{ $emailSoporte }}</a>.
        Contanos qué pantalla estabas usando, qué hiciste y qué esperabas que pasara.
        <br><br>
        <strong style="color:#856404;">No incluyas en el mail el contenido de una denuncia,
        datos del denunciante ni archivos del caso.</strong>
        Con el código interno alcanza para identificarlo.
    </div>

    <script>
        function alternarFaq(el) {
            const item = el.closest('.faq-item');
            const abierto = item.classList.contains('open');

            document.querySelectorAll('.faq-item.open').forEach(function (i) {
                i.classList.remove('open');
            });

            if (! abierto) {
                item.classList.add('open');
            }
        }
    </script>

</x-admin.layout>
