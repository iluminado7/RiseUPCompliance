<x-admin.layout-auth :titulo="'Activar verificación en dos pasos'">

    <h1 class="login-title">Activar verificación en dos pasos</h1>
    <p class="login-subtitle">
        Escaneá el código con Google Authenticator, Authy o similar,
        y confirmá con el código que aparece en la app.
    </p>

    @if ($errors->any())
        <div class="login-error">{{ $errors->first() }}</div>
    @endif

    {{-- El QR se genera en el servidor: el secreto no sale de acá (H-027). --}}
    <div style="text-align:center;margin:24px 0;">
        {!! $qr !!}
    </div>

    <p style="font-size:12px;color:var(--text-light);text-align:center;word-break:break-all;">
        Si no podés escanear, cargá esta clave a mano:<br>
        <code>{{ $secreto }}</code>
    </p>

    <form method="POST" action="{{ route('admin.2fa.alta.confirmar') }}" class="login-form" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="codigo">Código de 6 dígitos</label>
            <input type="text" id="codigo" name="codigo"
                   inputmode="numeric" autocomplete="one-time-code"
                   maxlength="10" placeholder="000000"
                   required autofocus>
        </div>

        <button type="submit" class="btn-login">Activar</button>
    </form>

</x-admin.layout-auth>
