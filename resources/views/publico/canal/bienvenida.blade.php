<x-publico.layout :titulo="'Canal de denuncias — ' . $empresa->name" :empresa="$empresa">

    <style>
        .bv-card { text-align:center; padding:44px 36px; }
        .bv-card h1 {
            font-size:31px; line-height:1.2; margin:0 0 16px; color:var(--dark);
        }
        .bv-card p.intro {
            font-size:15px; line-height:1.65; color:#555; margin:0 auto 28px;
            max-width:520px;
        }
        .bv-nota {
            display:inline-block; font-size:12px; font-weight:700;
            color:var(--gold-dark); text-transform:uppercase;
            letter-spacing:.08em; margin-bottom:14px;
        }
        .bv-empezar {
            display:inline-block; background:var(--gold); color:var(--dark);
            padding:15px 34px; border-radius:10px; font-size:15px;
            font-weight:700; text-decoration:none;
        }
        .bv-empezar:hover { background:var(--gold-dark); color:#fff; }

        .bv-puntos {
            display:grid; grid-template-columns:repeat(3,1fr); gap:16px;
            margin:34px 0 0; text-align:left;
        }
        .bv-punto {
            padding:16px 18px; background:#fafaf7; border-radius:10px;
        }
        .bv-punto strong {
            display:block; font-size:13px; color:var(--dark); margin-bottom:5px;
        }
        .bv-punto span { font-size:12px; color:var(--text-light); line-height:1.5; }

        .bv-modal {
            display:none; position:fixed; inset:0; background:rgba(0,0,0,.55);
            z-index:900; padding:24px; overflow-y:auto;
        }
        .bv-modal.abierto { display:flex; align-items:flex-start; justify-content:center; }
        .bv-modal-caja {
            background:#fff; border-radius:14px; max-width:640px; width:100%;
            padding:28px; margin:auto;
        }
        .bv-modal-caja h3 { margin:0 0 14px; font-size:17px; color:var(--dark); }
        .bv-modal-texto {
            max-height:56vh; overflow-y:auto; font-size:13px; line-height:1.65;
            color:#444; padding-right:6px;
        }

        @media (max-width:640px) {
            .bv-card { padding:32px 22px; }
            .bv-card h1 { font-size:25px; }
            .bv-puntos { grid-template-columns:1fr; }
        }
    </style>

    <div class="cn-card bv-card">
        <span class="bv-nota">Canal seguro y confidencial</span>

        <h1>Bienvenido al canal de denuncias de {{ $empresa->name }}</h1>

        <p class="intro">
            Este espacio existe para que puedas reportar una irregularidad con
            resguardo. Antes de empezar, tené a mano la información relevante:
            qué pasó, cuándo, y quién estuvo involucrado si lo sabés.
        </p>

        <a href="{{ route('canal.paso', [$empresa->slug, 1]) }}" class="bv-empezar">
            Iniciar el reporte →
        </a>

        <div class="bv-puntos">
            <div class="bv-punto">
                <strong>Podés ser anónimo</strong>
                <span>
                    Si elegís no identificarte, no vamos a pedirte ningún dato personal.
                </span>
            </div>
            <div class="bv-punto">
                <strong>Recibís un código</strong>
                <span>
                    Al enviar te damos un código para consultar el estado. Guardalo:
                    no lo podemos recuperar.
                </span>
            </div>
            <div class="bv-punto">
                <strong>Lo lee el equipo asignado</strong>
                <span>
                    Solo las personas habilitadas por {{ $empresa->name }} acceden a
                    tu denuncia.
                </span>
            </div>
        </div>
    </div>

    <div class="cn-card" style="text-align:center;">
        <p style="font-size:14px;margin:0 0 12px;">
            ¿Ya hiciste una denuncia?
        </p>
        <a href="{{ route('seguimiento.formulario') }}" class="cn-btn cn-btn-secundario">
            Consultar el estado
        </a>

        @if ($avisoPrivacidad)
            <p style="margin-top:20px;font-size:13px;">
                <button type="button" onclick="abrirAviso()"
                        style="background:none;border:none;font-size:13px;color:var(--gold-dark);cursor:pointer;text-decoration:underline;font-weight:600;padding:0;">
                    Leer el aviso de privacidad
                </button>
            </p>
        @endif
    </div>

    @if ($avisoPrivacidad)
        <div class="bv-modal" id="modal-aviso" onclick="cerrarAviso(event)">
            <div class="bv-modal-caja" onclick="event.stopPropagation()">
                <h3>Aviso de privacidad</h3>
                <div class="bv-modal-texto">{!! nl2br(e($avisoPrivacidad)) !!}</div>
                <button type="button" class="cn-btn cn-btn-secundario"
                        onclick="cerrarAviso()" style="margin-top:18px;">
                    Cerrar
                </button>
            </div>
        </div>

        <script>
            function abrirAviso() {
                document.getElementById('modal-aviso').classList.add('abierto');
            }

            function cerrarAviso(evento) {
                // Con el clic en el fondo también se cierra, pero no con el
                // clic dentro de la caja.
                if (evento && evento.target !== evento.currentTarget) return;
                document.getElementById('modal-aviso').classList.remove('abierto');
            }

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') cerrarAviso();
            });
        </script>
    @endif

</x-publico.layout>
