<x-publico.layout titulo="Rise UP Compliance — Canal de denuncias">

    <style>
        .pt-card { text-align:center; padding:46px 36px; }
        .pt-card h1 { font-size:32px; line-height:1.2; margin:0 0 16px; color:var(--dark); }
        .pt-card p.intro {
            font-size:15px; line-height:1.65; color:#555;
            margin:0 auto 30px; max-width:540px;
        }
        .pt-nota {
            display:inline-block; font-size:12px; font-weight:700;
            color:var(--gold-dark); text-transform:uppercase;
            letter-spacing:.08em; margin-bottom:14px;
        }
        .pt-empezar {
            display:inline-block; background:var(--gold); color:var(--dark);
            padding:15px 34px; border-radius:10px; font-size:15px;
            font-weight:700; text-decoration:none;
        }
        .pt-empezar:hover { background:var(--gold-dark); color:#fff; }

        .pt-puntos {
            display:grid; grid-template-columns:repeat(3,1fr); gap:16px;
            margin-top:36px; text-align:left;
        }
        .pt-punto { padding:17px 19px; background:#fafaf7; border-radius:10px; }
        .pt-punto strong { display:block; font-size:13px; color:var(--dark); margin-bottom:5px; }
        .pt-punto span { font-size:12px; color:var(--text-light); line-height:1.5; }

        @media (max-width:640px) {
            .pt-card { padding:34px 22px; }
            .pt-card h1 { font-size:26px; }
            .pt-puntos { grid-template-columns:1fr; }
        }
    </style>

    <div class="cn-card pt-card">
        <span class="pt-nota">Canal seguro y confidencial</span>

        <h1>Canal de denuncias</h1>

        <p class="intro">
            Este espacio existe para que puedas reportar una irregularidad con
            resguardo. Antes de empezar, tené a mano la información relevante:
            qué pasó, cuándo, y quién estuvo involucrado si lo sabés.
        </p>

        <a href="{{ route('portada.selector') }}" class="pt-empezar">
            Iniciar un reporte →
        </a>

        <div class="pt-puntos">
            <div class="pt-punto">
                <strong>Podés ser anónimo</strong>
                <span>
                    Si elegís no identificarte, no vamos a pedirte ningún dato personal.
                </span>
            </div>
            <div class="pt-punto">
                <strong>Recibís un código</strong>
                <span>
                    Al enviar te damos un código para consultar el estado. Guardalo:
                    no lo podemos recuperar.
                </span>
            </div>
            <div class="pt-punto">
                <strong>Lo lee el equipo asignado</strong>
                <span>
                    Solo las personas habilitadas por la empresa acceden a tu denuncia.
                </span>
            </div>
        </div>
    </div>

    <div class="cn-card" style="text-align:center;">
        <p style="font-size:14px;margin:0 0 12px;">¿Ya hiciste una denuncia?</p>
        <a href="{{ route('seguimiento.formulario') }}" class="cn-btn cn-btn-secundario">
            Consultar el estado
        </a>
    </div>

</x-publico.layout>
