<x-admin.layout-auth :titulo="'Panel de administración'">

    <h1 class="login-title">Panel de administración</h1>
    <p class="login-subtitle">Acceso restringido al personal autorizado</p>

    @if ($errors->any())
        <div class="login-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}" class="login-form" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email"
                   value="{{ old('email') }}"
                   placeholder="analista@empresa.com"
                   required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-login">Ingresar</button>
    </form>

    <div style="text-align:center;margin-top:20px;">
        <a href="{{ route('admin.password.solicitar') }}" style="font-size:13px;color:var(--text-light);">
            ¿Olvidaste tu contraseña?
        </a>
    </div>

</x-admin.layout-auth>
