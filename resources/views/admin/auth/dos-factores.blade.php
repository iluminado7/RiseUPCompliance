<x-admin.layout-auth :titulo="'Verificación en dos pasos'">

    <h1 class="login-title">Verificación en dos pasos</h1>
    <p class="login-subtitle">Ingresá el código de tu aplicación de autenticación</p>

    @if ($errors->any())
        <div class="login-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.2fa.verificar') }}" class="login-form" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="codigo">Código de 6 dígitos</label>
            <input type="text" id="codigo" name="codigo"
                   inputmode="numeric" autocomplete="one-time-code"
                   maxlength="10" placeholder="000000"
                   required autofocus>
        </div>

        <button type="submit" class="btn-login">Verificar</button>
    </form>

</x-admin.layout-auth>
