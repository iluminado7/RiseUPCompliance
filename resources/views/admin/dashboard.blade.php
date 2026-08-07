<x-admin.layout-auth :titulo="'Panel'">

    <h1 class="login-title">Sesión iniciada</h1>

    <p class="login-subtitle">
        {{ auth()->user()->nombreCompleto() }} ·
        {{ auth()->user()->nombreRol()?->etiqueta() }}
        @if (auth()->user()->empresa)
            · {{ auth()->user()->empresa->name }}
        @else
            · Plataforma
        @endif
    </p>

    @if (session('estado'))
        <div class="login-info">{{ session('estado') }}</div>
    @endif

    <p style="font-size:13px;color:var(--text-light);text-align:center;margin:24px 0;">
        Placeholder. El dashboard real llega en la Etapa 5.
    </p>

    @unless (auth()->user()->two_factor_enabled)
        <div style="text-align:center;margin-bottom:16px;">
            <a href="{{ route('admin.2fa.alta') }}" style="font-size:13px;">
                Activar verificación en dos pasos
            </a>
        </div>
    @endunless

    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="btn-login">Cerrar sesión</button>
    </form>

</x-admin.layout-auth>
