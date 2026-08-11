<x-admin.layout-auth :titulo="'Cambiá tu contraseña'">

    <h1 class="login-title">Cambiá tu contraseña</h1>
    <p class="login-subtitle">
        Tu cuenta se creó con una contraseña provisoria. Antes de seguir,
        elegí una que solo vos conozcas.
    </p>

    @if ($errors->any())
        <div class="login-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.password.cambio-obligatorio.guardar') }}"
          class="login-form" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="password_actual">Contraseña actual</label>
            <input type="password" id="password_actual" name="password_actual"
                   placeholder="••••••••" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Nueva contraseña</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required>
            <span style="font-size:11px;color:var(--text-light);">
                Mínimo 12 caracteres, con letras, números y símbolos.
            </span>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Repetir nueva contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-login">Guardar y continuar</button>
    </form>

    <div style="text-align:center;margin-top:20px;">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit"
                    style="background:none;border:none;font-size:13px;color:var(--text-light);cursor:pointer;">
                Cerrar sesión
            </button>
        </form>
    </div>

</x-admin.layout-auth>
