<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServicioConfigGlobal;
use App\Support\CatalogoConfigGlobal;
use App\Support\ClaveConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Configuracion global del sistema. Solo superadmin (lo restringe la ruta).
 *
 * Lo que se toca aca no afecta a ninguna empresa existente: son los valores
 * con los que se crean las nuevas. Cambiar la retencion por defecto no
 * reescribe la de nadie.
 */
class ConfigGlobalController extends Controller
{
    public function __construct(
        private readonly ServicioConfigGlobal $servicio,
    ) {}

    public function index(): View
    {
        return view('admin.config-global.index', [
            'grupos' => CatalogoConfigGlobal::porGrupo(),
            'valores' => $this->servicio->valores(),
            'metadatos' => $this->servicio->metadatos(),
            'noDeclaradas' => $this->servicio->noDeclaradas(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $validado = $request->validate(
            CatalogoConfigGlobal::reglasValidacion(),
            [],
            CatalogoConfigGlobal::etiquetasValidacion()
        );

        // Del nombre de campo (con __) de vuelta a la clave real (con .).
        $entradas = [];

        foreach ($validado['config'] ?? [] as $campo => $valor) {
            $entradas[ClaveConfig::claveDesdeCampo($campo)] = $valor;
        }

        try {
            $modificadas = $this->servicio->actualizar($entradas);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.config-global.index')
            ->with('estado', $modificadas
                ? 'Configuracion actualizada (' . count($modificadas) . ' cambio(s)).'
                : 'No hubo cambios para guardar.');
    }
}
