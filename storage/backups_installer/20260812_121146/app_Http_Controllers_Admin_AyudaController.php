<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CatalogoAyuda;
use BackedEnum;
use Illuminate\View\View;

/**
 * Ayuda y soporte. La ven los cuatro roles, con contenido distinto.
 *
 * El filtrado por rol pasa en el servidor y no en la vista: un articulo
 * que no corresponde al rol no llega al HTML.
 */
class AyudaController extends Controller
{
    public function __invoke(): View
    {
        $rol = auth()->user()->role;

        // El rol puede venir como enum casteado o como string, segun como
        // este declarado el cast en el modelo.
        $rol = $rol instanceof BackedEnum ? $rol->value : (string) $rol;

        return view('admin.ayuda.index', [
            'secciones' => CatalogoAyuda::paraRol($rol),
            'emailSoporte' => CatalogoAyuda::EMAIL_SOPORTE,
        ]);
    }
}
