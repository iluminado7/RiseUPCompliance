<x-publico.layout titulo="Denuncia enviada" :empresa="$empresa">

    <div class="cn-card" style="text-align:center;">
        <div style="font-size:44px;margin-bottom:12px;">✓</div>
        <h2>Recibimos tu denuncia</h2>
        <p class="ayuda">
            El equipo de {{ $empresa->name }} va a revisarla. Podés consultar el
            estado en cualquier momento con el código de abajo.
        </p>
    </div>

    <div class="cn-card" style="border:2px solid var(--gold);text-align:center;">
        <h2>Tu código de seguimiento</h2>
        <p class="ayuda">
            Anotalo ahora. <strong>No lo vamos a poder recuperar</strong>: no
            guardamos el código, solo una huella suya.
        </p>

        <div style="font-family:monospace;font-size:26px;font-weight:700;letter-spacing:2px;color:var(--dark);padding:20px;background:#fffbe6;border-radius:10px;margin:16px 0;word-break:break-all;">
            {{ $tracking }}
        </div>

        <button type="button" class="cn-btn cn-btn-principal" onclick="copiar(this)">
            Copiar código
        </button>
    </div>

    <div class="cn-aviso">
        <strong>Guardalo en un lugar seguro.</strong>
        Si denunciaste de forma anónima, este código es la única forma de volver
        a tu denuncia. Nadie puede dártelo de nuevo: ni el equipo que investiga
        ni nosotros lo tenemos.
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('seguimiento.formulario') }}" class="cn-btn cn-btn-secundario">
            Consultar el estado
        </a>
          <a href="{{ route('canal.inicio', $empresa->slug) }}" class="cn-btn cn-btn-secundario">
            Hacer otra denuncia
        </a>
    </div>

    <script>
        function copiar(boton) {
            navigator.clipboard.writeText('{{ $tracking }}').then(() => {
                const texto = boton.textContent;
                boton.textContent = '✓ Copiado';
                setTimeout(() => { boton.textContent = texto; }, 2000);
            });
        }
    </script>

</x-publico.layout>
