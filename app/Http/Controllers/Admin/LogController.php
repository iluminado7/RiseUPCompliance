<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FiltroLogsRequest;
use App\Models\Empresa;
use App\Models\RegistroAuditoria;
use App\Services\ServicioCifrado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Logs de actividad.
 *
 * Solo superadmin: la ruta lo restringe con el middleware 'rol'.
 *
 * El original chequeaba permisos granulares (logs.view, logs.decrypt_ip,
 * logs.export, logs.verify_integrity) leyendo permissions_json en cada
 * carga. Ninguno de los cuatro roles del catalogo tiene poblada la clave
 * 'logs', asi que esos chequeos siempre daban falso y solo el superadmin
 * llegaba a la pantalla. Se porta ese comportamiento efectivo; si mas
 * adelante hace falta abrirlo a otro rol, va en una Policy.
 */
class LogController extends Controller
{
    private const POR_PAGINA = 25;

    public function __construct(
        private readonly ServicioCifrado $cifrado,
    ) {}

    public function index(FiltroLogsRequest $request): View
    {
        $filtros = $request->filtros();

        $consulta = RegistroAuditoria::query()
            ->with([
                'usuario:id,first_name,last_name,email',
                'empresa:id,name',
            ]);

        $this->aplicarFiltros($consulta, $filtros);

        $registros = $consulta
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('admin.logs.index', [
            'registros' => $registros,
            'filtros' => $filtros,
            'empresas' => Empresa::orderBy('name')->pluck('name', 'id'),
            'detalles' => $this->descifrarDetalles($registros),
        ]);
    }

    private function aplicarFiltros(Builder $consulta, array $f): void
    {
        $consulta
            ->when($f['empresa'] ?? null, fn (Builder $q, $v) => $q
                ->whereHas('empresa', fn (Builder $e) => $e->where('name', 'like', '%' . $v . '%')))

            ->when($f['usuario'] ?? null, fn (Builder $q, $v) => $q
                ->whereHas('usuario', fn (Builder $u) => $u
                    ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $v . '%'])
                    ->orWhere('email', 'like', '%' . $v . '%')))

            ->when($f['accion'] ?? null, fn (Builder $q, $v) => $q
                ->where('action', 'like', '%' . $v . '%'))

            ->when($f['resultado'] ?? null, fn (Builder $q, $v) => $q->where('result', $v))
            ->when($f['origen'] ?? null, fn (Builder $q, $v) => $q->where('origin', $v))

            ->when($f['denuncia'] ?? null, fn (Builder $q, $v) => $q
                ->whereHas('denunciaAfectada', fn (Builder $d) => $d->where('internal_code', $v)))

            ->when($f['desde'] ?? null, fn (Builder $q, $v) => $q
                ->where('created_at', '>=', $v . ' 00:00:00'))
            ->when($f['hasta'] ?? null, fn (Builder $q, $v) => $q
                ->where('created_at', '<=', $v . ' 23:59:59'));

        // Cadena de plataforma: eventos sin empresa. Son los login
        // fallidos y toda la actividad del superadmin, que en el sistema
        // anterior no se registraban en absoluto.
        if (($f['cadena'] ?? null) === 'plataforma') {
            $consulta->whereNull('company_id');
        }
    }

    /**
     * Descifra detail_enc de los registros de la pagina.
     *
     * Se hace en lote y fuera de la vista: un fallo de descifrado no debe
     * romper la pantalla, y el detalle es lo unico cifrado que se muestra.
     */
    private function descifrarDetalles($registros): array
    {
        $detalles = [];

        foreach ($registros as $registro) {
            if (! $registro->detail_enc) {
                continue;
            }

            try {
                $detalles[$registro->id] = $this->cifrado->descifrar($registro->detail_enc);
            } catch (Throwable $e) {
                Log::warning('[LOGS] No se pudo descifrar el detalle', [
                    'registro' => $registro->id,
                    'error' => $e->getMessage(),
                ]);
                $detalles[$registro->id] = null;
            }
        }

        return $detalles;
    }
}
