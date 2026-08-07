<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoDenuncia;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\FiltroDenunciasRequest;
use App\Models\Denuncia;
use App\Models\Empresa;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use App\Enums\PrioridadDenuncia;

/**
 * Listado de denuncias del panel.
 *
 * Diferencias con admin/denuncias.php:
 *
 * 1. FUENTE UNICA DE ASIGNACION.
 *    El original filtraba por complaints.assigned_to_user_id, mientras que
 *    ver_denuncia.php consultaba complaint_assignments con UNION de
 *    respaldo. Resultado: una denuncia podia no aparecer en el listado del
 *    gestor y sin embargo ser accesible por URL directa. Y como la columna
 *    guarda un solo asignado, con dos personas en un caso la segunda no lo
 *    veia nunca. Aca se usa complaint_assignments (H-010).
 *
 * 2. SIN WHERE company_id A MANO.
 *    Lo aplica TenantScope.
 *
 * 3. LISTAS BLANCAS DESDE LOS ENUMS.
 *    Ver FiltroDenunciasRequest.
 */
class DenunciaController extends Controller
{
    public function index(FiltroDenunciasRequest $request): View
    {
        $this->authorize('viewAny', Denuncia::class);

        $usuario = $request->user();
        $rol = $usuario->nombreRol();
        $filtros = $request->filtros();

        $consulta = Denuncia::with([
            'empresa:id,name',
            'sucursal:id,name',
            'categoria:id,name_es',
        ])
            ->withExists([
                'asignaciones as tiene_asignado' => fn (Builder $q) => $q->whereNull('ended_at'),
            ]);

        // Gestor e investigador ven solo lo asignado a ellos.
        if (in_array($rol, [RolUsuario::Gestor, RolUsuario::InvestigadorExterno], true)) {
            $consulta->whereHas('asignaciones', fn (Builder $q) => $q
                ->whereNull('ended_at')
                ->where('assigned_to_user_id', $usuario->id)
            );
        }

        $this->aplicarFiltros($consulta, $filtros);

        $denuncias = $consulta
            ->latest('created_at')
            ->paginate($request->porPagina())
            ->withQueryString();

        return view('admin.denuncias.index', [
            'denuncias' => $denuncias,
            'filtros' => $filtros,
            'porPagina' => $request->porPagina(),
            'esSuperadmin' => $rol === RolUsuario::Superadmin,
            'empresas' => $this->empresasConDenuncias($rol),
            'sucursales' => $this->sucursalesConDenuncias($filtros['empresa'] ?? null),
            'titulo' => $this->titulo($rol),
            'estados' => EstadoDenuncia::cases(),
            'prioridades' => PrioridadDenuncia::cases(),
        ]);
    }

    private function aplicarFiltros(Builder $consulta, array $f): void
    {
        $consulta
            ->when($f['codigo'] ?? null, fn (Builder $q, $v) => $q
                ->where('internal_code', 'like', '%' . $v . '%'))
            ->when($f['empresa'] ?? null, fn (Builder $q, $v) => $q
                ->whereHas('empresa', fn (Builder $e) => $e->where('name', 'like', '%' . $v . '%')))
            ->when($f['sucursal'] ?? null, fn (Builder $q, $v) => $q
                ->whereHas('sucursal', fn (Builder $s) => $s->where('name', 'like', '%' . $v . '%')))
            ->when($f['estado'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($f['prioridad'] ?? null, fn (Builder $q, $v) => $q->where('priority', $v));

        match ($f['fecha'] ?? null) {
            'hoy' => $consulta->whereDate('created_at', now()->toDateString()),
            'semana' => $consulta->where('created_at', '>=', now()->subDays(7)),
            'mes' => $consulta->where('created_at', '>=', now()->subDays(30)),
            'personalizado' => $consulta
                ->when($f['desde'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($f['hasta'] ?? null, fn (Builder $q, $v) => $q->whereDate('created_at', '<=', $v)),
            default => null,
        };
    }

    /**
     * Empresas con denuncias, para el autocompletado.
     *
     * Sin filtrar por status: una empresa desactivada conserva sus
     * denuncias historicas y hay que poder buscarlas.
     *
     * Solo tiene sentido para el superadmin; el resto ve una sola empresa.
     */
    private function empresasConDenuncias(?RolUsuario $rol)
    {
        if ($rol !== RolUsuario::Superadmin) {
            return collect();
        }

        return Empresa::whereHas('denuncias')->orderBy('name')->pluck('name');
    }

    /**
     * Sucursales con denuncias. Sin filtrar por is_active, por lo mismo.
     *
     * TenantScope ya limita Sucursal a la empresa del usuario, asi que
     * para roles no-superadmin devuelve las suyas sin filtro adicional.
     */
    private function sucursalesConDenuncias(?string $empresa)
    {
        return Sucursal::whereHas('denuncias')
            ->when($empresa, fn (Builder $q, $v) => $q
                ->whereHas('empresa', fn (Builder $e) => $e->where('name', $v)))
            ->orderBy('name')
            ->pluck('name');
    }

    private function titulo(?RolUsuario $rol): string
    {
        return match ($rol) {
            RolUsuario::Superadmin => 'Denuncias globales',
            RolUsuario::AdminPrincipal => 'Bandeja de denuncias',
            default => 'Mis denuncias',
        };
    }
}
