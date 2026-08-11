<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de administracion, con tres pestanas.
 *
 * Tiene dos caras segun el rol, como en el sidebar:
 *   - superadmin: "Administracion" -- empresas, sucursales y usuarios de todas
 *   - admin_principal: "Sucursales y Usuarios" -- solo lo suyo
 */
class AdministracionController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $esSuperadmin = $usuario->nombreRol() === RolUsuario::Superadmin;

        $tab = $this->tabValida($request, $esSuperadmin);
        $filtroEmpresa = trim((string) $request->query('empresa'));
        $filtroSucursal = trim((string) $request->query('sucursal'));

        $datos = [
            'tab' => $tab,
            'esSuperadmin' => $esSuperadmin,
            'filtroEmpresa' => $filtroEmpresa,
            'filtroSucursal' => $filtroSucursal,
            'titulo' => $esSuperadmin ? 'Administración' : 'Sucursales y Usuarios',
            'opcionesEmpresa' => $this->opcionesEmpresa(),
            'sucursalesPorEmpresa' => $tab === 'sucursales' ? $this->sucursalesPorEmpresa() : [],
        ];

        $datos += match ($tab) {
            'empresas' => ['empresas' => $this->empresas($filtroEmpresa)],
            'sucursales' => ['sucursales' => $this->sucursales($filtroEmpresa, $filtroSucursal)],
            'usuarios' => ['usuarios' => $this->usuarios($filtroEmpresa)],
        };

        return view('admin.administracion.index', $datos);
    }

    private function empresas(string $filtro)
    {
        $this->authorize('viewAny', Empresa::class);

        return Empresa::with(['managerPlataforma:id,full_name', 'datosFiscales'])
            ->withCount('denuncias')
            ->when($filtro, fn (Builder $q, $v) => $q->where('name', 'like', '%' . $v . '%'))
            ->orderBy('name')
            ->get();
    }

    /** TenantScope ya limita Sucursal a la empresa del admin_principal. */
    private function sucursales(string $filtroEmpresa, string $filtroSucursal)
    {
        $this->authorize('viewAny', Sucursal::class);

        return Sucursal::with('empresa:id,name')
            ->withCount('denuncias')
            ->when($filtroEmpresa, fn (Builder $q, $v) => $q
                ->whereHas('empresa', fn (Builder $e) => $e->where('name', 'like', '%' . $v . '%')))
            ->when($filtroSucursal, fn (Builder $q, $v) => $q
                ->where('name', 'like', '%' . $v . '%'))
            ->orderBy('company_id')
            ->orderByDesc('is_headquarter')
            ->orderBy('name')
            ->get();
    }

    /**
     * Usuario NO lleva TenantScope, asi que el filtrado por empresa va
     * explicito aca. Es la excepcion documentada en UsuarioPolicy.
     */
    private function usuarios(string $filtro)
    {
        $this->authorize('viewAny', Usuario::class);

        $autor = auth()->user();

        return Usuario::with(['rol:id,name', 'empresa:id,name', 'sucursales:id,name'])
            ->whereHas('rol')
            ->when(
                $autor->nombreRol() !== RolUsuario::Superadmin,
                fn (Builder $q) => $q->where('company_id', $autor->company_id),
                fn (Builder $q) => $q->when($filtro, fn (Builder $s, $v) => $s
                    ->whereHas('empresa', fn (Builder $e) => $e->where('name', 'like', '%' . $v . '%')))
            )
            ->orderBy('first_name')
            ->get();
    }

    /** Nombres de empresa para el autocompletado. */
    private function opcionesEmpresa()
    {
        return auth()->user()->nombreRol() === RolUsuario::Superadmin
            ? Empresa::orderBy('name')->pluck('name')
            : Empresa::where('id', auth()->user()->company_id)->pluck('name');
    }

    /**
     * Sucursales agrupadas por nombre de empresa, para el segundo campo.
     *
     * Van precargadas en la pagina. Con las decenas de empresas que maneja
     * el sistema el JSON es chico y filtrar en memoria es instantaneo. Si
     * alguna vez son cientos de empresas con miles de sucursales, conviene
     * reemplazar la precarga por una consulta al servidor.
     *
     * @return array<string, array<string>>
     */
    private function sucursalesPorEmpresa(): array
    {
        return Sucursal::with('empresa:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'company_id'])
            ->groupBy(fn (Sucursal $s) => $s->empresa->name)
            ->map(fn ($grupo) => $grupo->pluck('name')->values()->all())
            ->all();
    }

    private function tabValida(Request $request, bool $esSuperadmin): string
    {
        $tab = (string) $request->query('tab', $esSuperadmin ? 'empresas' : 'sucursales');

        $permitidas = $esSuperadmin
            ? ['empresas', 'sucursales', 'usuarios']
            : ['sucursales', 'usuarios'];

        return in_array($tab, $permitidas, true) ? $tab : $permitidas[0];
    }
}
