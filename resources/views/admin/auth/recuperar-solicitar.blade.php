<x-admin.layout-auth :titulo="'Recuperar contraseña'">

    <h1 class="login-title">Recuperar contraseña</h1>
    <p class="login-subtitle">Te enviamos un enlace para restablecerla</p>

    @if (session('estado'))
        <div class="login-info">{{ session('estado') }}</div>
    @endif

    @if ($errors->any())
        <div class="login-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.password.enviar') }}" class="login-form" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email"
                   value="{{ old('email') }}"
                   placeholder="analista@empresa.com"
                   required autofocus>
        </div>

        <button type="submit" class="btn-login">Enviar enlace</button>
    </form>

    <div style="text-align:center;margin-top:20px;">
        <a href="{{ route('admin.login') }}" style="font-size:13px;color:var(--text-light);">
            Volver al inicio de sesión
        </a>
    </div>

</x-admin.layout-auth>
