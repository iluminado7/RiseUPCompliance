@php
    use App\Enums\RolUsuario;

    $usuario = auth()->user();
    $rol = $usuario->nombreRol();

    // Etiqueta del ítem de denuncias según rol — portado del sidebar original.
    $etiquetaDenuncias = match ($rol) {
        RolUsuario::Superadmin => 'Denuncias globales',
        RolUsuario::AdminPrincipal => 'Bandeja de denuncias',
        default => 'Mis denuncias',
    };

    $esSuperadmin = $rol === RolUsuario::Superadmin;
    $esAdminPrincipal = $rol === RolUsuario::AdminPrincipal;
@endphp

<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="admin-sidebar">

    <div class="sidebar-logo">
        <span class="logo-mark">◆</span>
        <span class="logo-text">Rise UP Compliance</span>
    </div>

    <nav class="sidebar-nav">

        <div class="nav-section">Principal</div>

        <a href="{{ route('admin.dashboard') }}"
           class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
           onclick="closeSidebar()" title="Inicio">
            <span class="icon"><i class="icon-home"></i></span> Inicio
        </a>

        <a href="{{ route('admin.denuncias.index') }}"
           class="nav-item {{ request()->routeIs('admin.denuncias.*') ? 'active' : '' }}"
           onclick="closeSidebar()" title="{{ $etiquetaDenuncias }}">
            <span class="icon">
                <i class="{{ $esSuperadmin ? 'icon-file-warning' : 'icon-chart-column' }}"></i>
            </span> {{ $etiquetaDenuncias }}
        </a>

        @if ($esSuperadmin || $esAdminPrincipal)
            <a href="{{ route('admin.reportes.index') }}"
               class="nav-item {{ request()->routeIs('admin.reportes.*') ? 'active' : '' }}"
               onclick="closeSidebar()" title="Reportes">
                <span class="icon"><i class="icon-chart-column"></i></span> Reportes
            </a>
        @endif

        @if ($esSuperadmin)
            <div class="nav-section">Gestión</div>

            <a href="{{ route('admin.administracion.index') }}"
               class="nav-item {{ request()->routeIs('admin.administracion.*') ? 'active' : '' }}"
               onclick="closeSidebar()" title="Administración">
                <span class="icon"><i class="icon-users"></i></span> Administración
            </a>

            <a href="{{ route('admin.onboarding.index') }}"
               class="nav-item {{ request()->routeIs('admin.onboarding.*') ? 'active' : '' }}"
               onclick="closeSidebar()" title="Onboarding">
                <span class="icon"><i class="icon-badge-check"></i></span> Onboarding
            </a>

            <a href="{{ route('admin.catalogo.index') }}"
               class="nav-item {{ request()->routeIs('admin.catalogo.*') ? 'active' : '' }}"
               onclick="closeSidebar()" title="Catálogo">
                <span class="icon"><i class="icon-folder-tree"></i></span> Catálogo
            </a>

            <a href="{{ route('admin.configuracion.index') }}"
               class="nav-item {{ request()->routeIs('admin.configuracion.*') ? 'active' : '' }}"
               onclick="closeSidebar()" title="Config. del Canal">
                <span class="icon"><i class="icon-settings"></i></span> Config. del Canal
            </a>

            <a href="{{ route('admin.facturacion.index') }}"
               class="nav-item {{ request()->routeIs('admin.facturacion.*') ? 'active' : '' }}"
               onclick="closeSidebar()" title="Facturación">
                <span class="icon"><i class="icon-credit-card"></i></span> Facturación
            </a>

            <a href="{{ route('admin.config-global.index') }}"
               class="nav-item {{ request()->routeIs('admin.config-global.*') ? 'active' : '' }}"
               onclick="closeSidebar()" title="Config. global">
                <span class="icon"><i class="icon-globe"></i></span> Config. global
            </a>
        @endif

        @if ($esAdminPrincipal)
            <div class="nav-section">Gestión</div>

            <a href="{{ route('admin.administracion.index') }}"
               class="nav-item {{ request()->routeIs('admin.administracion.*') ? 'active' : '' }}"
               onclick="closeSidebar()" title="Sucursales y Usuarios">
                <span class="icon"><i class="icon-users"></i></span> Sucursales y Usuarios
            </a>

            <a href="{{ route('admin.facturacion.index') }}"
               class="nav-item {{ request()->routeIs('admin.facturacion.*') ? 'active' : '' }}"
               onclick="closeSidebar()" title="Facturación">
                <span class="icon"><i class="icon-credit-card"></i></span> Facturación
            </a>
        @endif

        <div class="nav-section">Soporte</div>

        <a href="{{ route('admin.ayuda') }}"
           class="nav-item {{ request()->routeIs('admin.ayuda') ? 'active' : '' }}"
           onclick="closeSidebar()" title="Ayuda y soporte">
            <span class="icon"><i class="icon-life-buoy"></i></span> Ayuda y soporte
        </a>

        {{--
            Logs: solo superadmin.

            El sidebar original consultaba permissions_json en cada carga de
            página para ver si otros roles tenían 'logs.view'. Ese permiso no
            está poblado en ningún rol del catálogo, así que la consulta
            siempre daba falso — una query por request para nada. Cuando haga
            falta abrirlo a otro rol, va en la Policy.
        --}}
        @if ($esSuperadmin)
            <div class="nav-section">Sistema</div>

            <a href="{{ route('admin.logs.index') }}"
               class="nav-item {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}"
               onclick="closeSidebar()">
                <span class="icon"><i class="icon-scroll-text"></i></span> Logs de actividad
            </a>
        @endif

    </nav>

    <div class="sidebar-footer">
        <a href="{{ route('admin.perfil') }}" class="sidebar-user" title="Mi perfil"
           style="text-decoration:none;display:block;">
            <strong>{{ $usuario->nombreCompleto() }}</strong>
            {{ $rol?->etiqueta() }}
            <span style="font-size:11px;color:rgba(255,255,255,0.4);display:block;margin-top:2px;">
                Ver mi perfil →
            </span>
        </a>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn-logout" style="width:100%;">Cerrar sesión</button>
        </form>
    </div>

</aside>

<script>
function toggleSidebar() {
    const isMobile = window.innerWidth <= 768;
    const sidebar  = document.getElementById('admin-sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const layout   = document.querySelector('.admin-layout');

    if (isMobile) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    } else {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        layout?.classList.toggle('sidebar-collapsed', isCollapsed);
        localStorage.setItem('sidebarCollapsed', isCollapsed ? '1' : '0');
    }
}

function closeSidebar() {
    document.getElementById('admin-sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').classList.remove('open');
}

(function () {
    if (window.innerWidth > 768 && localStorage.getItem('sidebarCollapsed') === '1') {
        document.getElementById('admin-sidebar')?.classList.add('collapsed');
        document.querySelector('.admin-layout')?.classList.add('sidebar-collapsed');
    }
})();

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
});
</script>
