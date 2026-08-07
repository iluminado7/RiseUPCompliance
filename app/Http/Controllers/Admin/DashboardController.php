<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoDenuncia;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Models\Denuncia;
use App\Models\Empresa;
use App\Models\Factura;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/**
 * Dashboard del panel.
 *
 * El filtrado por empresa lo hace TenantScope: las consultas de acá no
 * llevan `where company_id`, y sin embargo cada usuario ve solo lo suyo.
 * Eso es lo que reemplaza al $whereEmpresa que el dashboard original
 * armaba a mano y concatenaba en cada query.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $usuario = auth()->user();
        $rol = $usuario->nombreRol();

        $esOperador = in_array($rol, [RolUsuario::Gestor, RolUsuario::InvestigadorExterno], true);

        return view('admin.dashboard', [
            'total' => Denuncia::count(),
            'sinAsignar' => $this->sinAsignar(),
            'enProgreso' => Denuncia::where('status', EstadoDenuncia::EnProgreso)->count(),
            'resueltas' => Denuncia::where('status', EstadoDenuncia::Resuelta)->count(),
            'misAsignadas' => $esOperador ? $this->misAsignadas() : null,
            'recientes' => $this->recientes($esOperador),
            'esOperador' => $esOperador,
            'widgets' => $rol === RolUsuario::Superadmin ? $this->widgetsPlataforma() : null,
        ]);
    }

    private function sinAsignar(): int
    {
        return Denuncia::where('status', EstadoDenuncia::Nuevo)
            ->whereDoesntHave('asignaciones', fn (Builder $q) => $q->whereNull('ended_at'))
            ->count();
    }

    /**
     * Denuncias activas asignadas al usuario.
     *
     * Cuenta desde complaint_assignments y no desde
     * complaints.assigned_to_user_id, que es la columna que puede
     * desincronizarse (H-010) y que además solo guarda un asignado: con
     * dos personas en un mismo caso, la segunda no lo veía.
     */
    private function misAsignadas(): int
    {
        return Denuncia::whereHas('asignaciones', fn (Builder $q) => $q
            ->whereNull('ended_at')
            ->where('assigned_to_user_id', auth()->id())
        )
            ->whereNotIn('status', [EstadoDenuncia::Cerrada, EstadoDenuncia::Archivada])
            ->count();
    }

    private function recientes(bool $soloMias)
    {
        return Denuncia::with(['empresa:id,name', 'categoria:id,name_es'])
            ->when($soloMias, fn (Builder $q) => $q
                ->whereHas('asignaciones', fn (Builder $a) => $a
                    ->whereNull('ended_at')
                    ->where('assigned_to_user_id', auth()->id())
                )
            )
            ->withExists(['asignaciones as tiene_asignado' => fn (Builder $q) => $q->whereNull('ended_at')])
            ->latest('created_at')
            ->limit(8)
            ->get();
    }

    /**
     * Vista global de plataforma. Solo superadmin.
     *
     * Empresa y Factura no llevan el scope de tenant aplicado para el
     * superadmin, así que estas cuentas son globales por construcción.
     */
    private function widgetsPlataforma(): array
    {
        $empresasPorEstado = Empresa::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'empresas_activas' => $empresasPorEstado['active'] ?? 0,
            'empresas_suspendidas' => $empresasPorEstado['suspended'] ?? 0,
            'empresas_inactivas' => $empresasPorEstado['deactivated'] ?? 0,

            'facturas_vencidas' => Factura::where('payment_status', 'overdue')->count(),

            'facturas_proximas' => Factura::where('payment_status', 'pending')
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->count(),

            'empresas_sin_actividad' => Empresa::where('status', 'active')
                ->whereDoesntHave('denuncias', fn (Builder $q) => $q
                    ->where('created_at', '>=', now()->subDays(30))
                )
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ];
    }
}
