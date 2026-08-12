<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portada del sistema y selector de empresa.
 *
 * Es la cara publica de Rise UP Compliance: presenta el canal, no a una
 * empresa en particular. Cada empresa cliente tiene despues su propio
 * portal en /{slug}.
 */
class PortadaController extends Controller
{
    private const MINIMO_CARACTERES = 2;
    private const MAXIMO_RESULTADOS = 8;

    public function portada(): View
    {
        return view('publico.portada.inicio');
    }

    public function selector(): View
    {
        return view('publico.portada.selector');
    }

    /**
     * Busqueda de empresas por nombre.
     *
     * -- POR QUE ESTO NO SE RESUELVE EN EL NAVEGADOR --
     *
     * El componente de autocompletado del panel precarga todas las
     * opciones en el HTML. Acá eso equivaldría a publicar la cartera de
     * clientes de GoHarv: estaría a un "ver código fuente" de distancia,
     * por más que el campo empiece vacío.
     *
     * Filtrando contra el servidor, quien ya sabe qué empresa busca la
     * encuentra, y quien solo curiosea no obtiene nada.
     *
     * -- CONTRA LA ENUMERACION --
     *
     * Mínimo dos caracteres: con uno solo, 27 consultas alcanzarían para
     * mapear casi todo. Y como máximo 8 resultados, para que una búsqueda
     * deliberadamente vaga tampoco sirva. El rate limiting está en la ruta.
     */
    public function buscar(Request $request): JsonResponse
    {
        $consulta = trim((string) $request->query('q'));

        if (mb_strlen($consulta) < self::MINIMO_CARACTERES) {
            return response()->json(['empresas' => []]);
        }

        $empresas = Empresa::where('status', 'active')
            ->where('name', 'like', '%' . $consulta . '%')
            ->orderBy('name')
            ->limit(self::MAXIMO_RESULTADOS)
            ->get(['name', 'slug'])
            ->map(fn (Empresa $empresa) => [
                'nombre' => $empresa->name,
                'url' => route('canal.inicio', $empresa->slug),
            ]);

        return response()->json(['empresas' => $empresas]);
    }

    /**
     * Envio del formulario, para quien tenga JavaScript desactivado.
     *
     * Si hay una sola coincidencia va derecho; si hay varias, se muestran
     * para elegir.
     */
    public function ir(Request $request): RedirectResponse|View
    {
        $datos = $request->validate([
            'empresa' => ['required', 'string', 'min:' . self::MINIMO_CARACTERES, 'max:200'],
        ]);

        $empresas = Empresa::where('status', 'active')
            ->where('name', 'like', '%' . $datos['empresa'] . '%')
            ->orderBy('name')
            ->limit(self::MAXIMO_RESULTADOS)
            ->get(['name', 'slug']);

        if ($empresas->count() === 1) {
            return redirect()->route('canal.inicio', $empresas->first()->slug);
        }

        return view('publico.portada.selector', [
            'resultados' => $empresas,
            'busqueda' => $datos['empresa'],
        ]);
    }
}
