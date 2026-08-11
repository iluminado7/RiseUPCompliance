<x-publico.layout titulo="Consultar denuncia">

    @if (session('estado'))
        <div class="cn-ok">{{ session('estado') }}</div>
    @endif

    @if ($errors->any())
        <div class="cn-error">{{ $errors->first() }}</div>
    @endif

    <div class="cn-card">
        <h2>Consultar el estado de una denuncia</h2>
        <p class="ayuda">
            Ingresá el código que recibiste al enviarla.
        </p>

        <form method="POST" action="{{ route('seguimiento.consultar') }}" autocomplete="off">
            @csrf

            <div class="campo" style="margin-bottom:16px;">
                <label for="codigo">Código de seguimiento</label>
                <input type="text" id="codigo" name="codigo" required autofocus
                       maxlength="40" placeholder="XXXXX-XXXXX-XXXXX-XXXXX"
                       style="font-family:monospace;font-size:16px;letter-spacing:1px;text-transform:uppercase;">
                <span class="nota">No importan las mayúsculas ni los guiones.</span>
            </div>

            <button type="submit" class="cn-btn cn-btn-principal">Consultar</button>
        </form>
    </div>

    <div class="cn-aviso">
        Si perdiste el código no vamos a poder recuperarlo: por diseño no lo
        guardamos. Tendrías que hacer una denuncia nueva.
    </div>

</x-publico.layout>
