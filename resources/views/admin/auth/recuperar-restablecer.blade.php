<x-admin.layout-auth :titulo="'Nueva contraseña'">

    <h1 class="login-title">Nueva contraseña</h1>
    <p class="login-subtitle">Mínimo 12 caracteres, con letras, números y símbolos</p>

    @if ($errors->any())
        <div class="login-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.password.restablecer') }}" class="login-form" autocomplete="off">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label for="password">Nueva contraseña</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required autofocus>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Repetir contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-login">Guardar</button>
    </form>

</x-admin.layout-auth>
