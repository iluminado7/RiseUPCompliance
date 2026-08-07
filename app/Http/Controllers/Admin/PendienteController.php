<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Placeholder de las secciones todavía no portadas.
 *
 * Existe para que el sidebar esté completo desde el principio: una ruta
 * que no existe rompe la página entera con RouteNotFoundException, no
 * solo el enlace. Cada sección se va reemplazando por su controlador
 * real a medida que avanzan las etapas.
 */
class PendienteController extends Controller
{
    public function __invoke(string $seccion = 'Esta sección'): View
    {
        return view('admin.pendiente', ['seccion' => $seccion]);
    }
}
