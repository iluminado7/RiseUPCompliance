<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoDenuncia;
use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\FiltroReportesRequest;
use App\Models\Denuncia;
use App\Models\Empresa;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Reportes del panel. Solo superadmin y admin_principal.
 *
 * El filtrado por empresa lo hace TenantScope, asi que ninguna de estas
 * consultas lleva `where company_id` a mano.
 */
class ReporteController extends Controller
{
    /**
     * Horas sin pasar de Nuevo a Vista para considerar el plazo incumplido.
     *
     * El original declaraba SLA_DIAS = 7 pero contaba con 24 horas, y el
     * enlace de la tarjeta filtraba por 7 dias: tres numeros distintos
     * para la misma metrica.
     */
    private const HORAS_PLAZO = 24;

    public function index(FiltroReportesRequest $request): View
    {
        $filtros = $request->filtros();
        $tab = $request->tab();

        $esSuperadmin = $request->user()->nombreRol() === RolUsuario::Superadmin;

        $datos = [
            'filtros' => $filtros,
            'tab' => $tab,
            'esSuperadmin' => $esSuperadmin,
            'empresas' => $esSuperadmin ? $this->empresas() : collect(),
            'sucursales' => $this->sucursales($filtros['empresa'] ?? null),
            'horasPlazo' => self::HORAS_PLAZO,
        ];

        // Cada pestana calcula solo lo suyo. El original ejecutaba las
        // consultas de las seis en cada carga, aunque se viera una.
        $datos += match ($tab) {
            '1' => $this->resumen($filtros),
            '2' => ['porCategoria' => $this->porCategoria($filtros)],
            '3' => ['porSucursal' => $this->porSucursal($filtros)],
            '4' => $this->tiempos($filtros),
            '5' => ['equipo' => $this->desempenoEquipo($filtros)],
            '6' => ['tendencias' => $this->tendencias()],
        };

        return view('admin.reportes.index', $datos);
    }

    /** Consulta base con el periodo y los filtros aplicados. */
    private function base(array $f): Builder
    {
        return Denuncia::query()
            ->when($f['empresa'] ?? null, fn (Builder $q, $v) => $q
                ->whereHas('empresa', fn (Builder $e) => $e->where('name', 'like', '%' . $v . '%')))
            ->when($f['sucursal'] ?? null, fn (Builder $q, $v) => $q
                ->whereHas('sucursal', fn (Builder $s) => $s->where('name', 'like', '%' . $v . '%')))
            ->tap(fn (Builder $q) => $this->aplicarPeriodo($q, $f));
    }

    private function aplicarPeriodo(Builder $consulta, array $f): void
    {
        $columna = 'complaints.created_at';

        match ($f['fecha'] ?? 'mes') {
            'hoy' => $consulta->whereDate($columna, now()->toDateString()),
            'semana' => $consulta->where($columna, '>=', now()->subDays(7)),
            'personalizado' => $consulta
                ->when($f['desde'] ?? null, fn (Builder $q, $v) => $q->whereDate($columna, '>=', $v))
                ->when($f['hasta'] ?? null, fn (Builder $q, $v) => $q->whereDate($columna, '<=', $v)),
            default => $consulta->where($columna, '>=', now()->subDays(30)),
        };
    }

    // -- Pestana 1: resumen --------------------------------------

    private function resumen(array $f): array
    {
        $porEstado = $this->base($f)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = $porEstado->sum();
        $resueltas = $porEstado[EstadoDenuncia::Resuelta->value] ?? 0;
        $cerradas = $porEstado[EstadoDenuncia::Cerrada->value] ?? 0;

        // Plazo incumplido: sigue en Nuevo pasadas las horas del plazo,
        // es decir nadie la abrio todavia.
        $plazoIncumplido = $this->base($f)
            ->where('status', EstadoDenuncia::Nuevo)
            ->where('complaints.created_at', '<', now()->subHours(self::HORAS_PLAZO))
            ->count();

        return [
            'total' => $total,
            'nuevas' => $porEstado[EstadoDenuncia::Nuevo->value] ?? 0,
            'vistas' => $porEstado[EstadoDenuncia::Vista->value] ?? 0,
            'enProgreso' => $porEstado[EstadoDenuncia::EnProgreso->value] ?? 0,
            'resueltas' => $resueltas,
            'cerradas' => $cerradas,
            'archivadas' => $porEstado[EstadoDenuncia::Archivada->value] ?? 0,
            'tasaResolucion' => $total > 0 ? round($resueltas / $total * 100) : 0,
            'plazoIncumplido' => $plazoIncumplido,
            'plazoIncumplidoPct' => $total > 0 ? round($plazoIncumplido / $total * 100, 1) : 0,
            'finalizadas' => $resueltas + $cerradas,
        ];
    }

    // -- Pestana 2: categorias -----------------------------------

    private function porCategoria(array $f)
    {
        $filas = $this->base($f)
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('categoria:id,name_es')
            ->get();

        $total = $filas->sum('total');

        return $filas->map(fn ($fila) => [
            'nombre' => $fila->categoria?->name_es ?? 'Sin categoría',
            'volumen' => $fila->total,
            'porcentaje' => $total > 0 ? round($fila->total / $total * 100, 1) : 0,
        ]);
    }

    // -- Pestana 3: sucursales -----------------------------------

    private function porSucursal(array $f)
    {
        $filas = $this->base($f)
            ->selectRaw('branch_id, COUNT(*) as total')
            ->groupBy('branch_id')
            ->orderByDesc('total')
            ->with('sucursal:id,name')
            ->get();

        $total = $filas->sum('total');

        return $filas->map(fn ($fila) => [
            'nombre' => $fila->sucursal?->name ?? 'Sin sucursal',
            'casos' => $fila->total,
            'porcentaje' => $total > 0 ? round($fila->total / $total * 100, 1) : 0,
        ]);
    }

    // -- Pestana 4: tiempos --------------------------------------

    private function tiempos(array $f): array
    {
        // resolved_at, NO updated_at. Ver la nota al principio del script.
        $promedio = $this->base($f)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, complaints.created_at, complaints.resolved_at)) as horas')
            ->value('horas');

        $conResolucion = $this->base($f)->whereNotNull('resolved_at')->count();

        // Tiempo hasta la primera lectura: mide la capacidad de reaccion,
        // que es lo que el plazo de 24 horas vigila.
        $primeraLectura = $this->base($f)
            ->join('complaint_status_history as h', function ($join) {
                $join->on('h.complaint_id', '=', 'complaints.id')
                    ->where('h.to_status', '=', EstadoDenuncia::Vista->value);
            })
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, complaints.created_at, h.created_at)) as horas')
            ->value('horas');

        $totalPeriodo = $this->base($f)->count();

        $plazoIncumplido = $this->base($f)
            ->where('status', EstadoDenuncia::Nuevo)
            ->where('complaints.created_at', '<', now()->subHours(self::HORAS_PLAZO))
            ->count();

        return [
            'promedioHoras' => $promedio ? round((float) $promedio, 1) : null,
            'promedioDias' => $promedio ? round((float) $promedio / 24, 1) : null,
            'conResolucion' => $conResolucion,
            'primeraLecturaHoras' => $primeraLectura ? round((float) $primeraLectura, 1) : null,
            'plazoIncumplido' => $plazoIncumplido,
            'plazoIncumplidoPct' => $totalPeriodo > 0 ? round($plazoIncumplido / $totalPeriodo * 100, 1) : 0,
        ];
    }

    // -- Pestana 5: equipo ---------------------------------------

    /**
     * Desempeno por analista.
     *
     * Cuenta desde complaint_assignments y no desde
     * complaints.assigned_to_user_id: esa columna guarda un solo asignado,
     * asi que con dos investigadores en un caso el segundo no aparecia en
     * ningun reporte (H-010).
     *
     * Consecuencia esperable: un caso con dos asignados suma para los dos.
     * Es lo correcto -- ambos trabajaron en el.
     */
    private function desempenoEquipo(array $f)
    {
        $idsDenuncias = $this->base($f)
            ->whereIn('status', [EstadoDenuncia::Resuelta, EstadoDenuncia::Cerrada])
            ->pluck('id');

        if ($idsDenuncias->isEmpty()) {
            return collect();
        }

        return DB::table('complaint_assignments as a')
            ->join('users as u', 'u.id', '=', 'a.assigned_to_user_id')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->join('complaints as c', 'c.id', '=', 'a.complaint_id')
            ->whereIn('a.complaint_id', $idsDenuncias)
            ->groupBy('u.id', 'u.first_name', 'u.last_name', 'r.name')
            ->selectRaw('
                u.id,
                u.first_name,
                u.last_name,
                r.name as rol,
                COUNT(DISTINCT a.complaint_id) as casos,
                AVG(TIMESTAMPDIFF(HOUR, c.created_at, c.resolved_at)) as horas_promedio
            ')
            ->orderByDesc('casos')
            ->get()
            ->map(fn ($fila) => [
                'nombre' => trim($fila->first_name . ' ' . $fila->last_name),
                'rol' => RolUsuario::tryFrom($fila->rol)?->etiqueta() ?? $fila->rol,
                'esExterno' => $fila->rol === RolUsuario::InvestigadorExterno->value,
                'casos' => $fila->casos,
                'diasPromedio' => $fila->horas_promedio ? round((float) $fila->horas_promedio / 24, 1) : null,
            ]);
    }

    // -- Pestana 6: tendencias -----------------------------------

    /**
     * Ultimos 12 meses. No usa los filtros de periodo a proposito: una
     * tendencia sobre "el ultimo mes" no seria una tendencia.
     */
    private function tendencias()
    {
        return Denuncia::query()
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw("
                DATE_FORMAT(created_at, '%Y-%m') as mes,
                COUNT(*) as ingresadas,
                SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) as finalizadas
            ")
            ->groupBy('mes')
            ->orderByDesc('mes')
            ->get()
            ->map(fn ($fila) => [
                'mes' => $fila->mes,
                'etiqueta' => \Carbon\Carbon::createFromFormat('Y-m', $fila->mes)
                    ->locale('es')->isoFormat('MMMM YYYY'),
                'ingresadas' => $fila->ingresadas,
                'finalizadas' => $fila->finalizadas,
            ]);
    }

    // -- Listas para los filtros ---------------------------------

    /**
     * Sin filtrar por status: una empresa desactivada conserva sus
     * denuncias historicas y hay que poder consultarlas. El original
     * filtraba por status='active' y las dejaba fuera del alcance.
     */
    private function empresas()
    {
        return Empresa::whereHas('denuncias')->orderBy('name')->pluck('name');
    }

    private function sucursales(?string $empresa)
    {
        return Sucursal::whereHas('denuncias')
            ->when($empresa, fn (Builder $q, $v) => $q
                ->whereHas('empresa', fn (Builder $e) => $e->where('name', $v)))
            ->orderByDesc('is_headquarter')
            ->orderBy('name')
            ->pluck('name');
    }
}
