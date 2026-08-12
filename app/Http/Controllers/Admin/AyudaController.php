<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CatalogoAyuda;
use BackedEnum;
use Illuminate\View\View;

/**
 * Ayuda y soporte. La ven los cuatro roles, con guias distintas.
 *
 * El filtrado por rol pasa en el servidor: una guia que no corresponde al
 * rol no llega al HTML.
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
            'rol' => $rol,
            'etiquetaRol' => CatalogoAyuda::etiquetaRol($rol),
            'guias' => CatalogoAyuda::guias($rol),
            'preguntas' => CatalogoAyuda::preguntasFrecuentes(),
            'manualDisponible' => CatalogoAyuda::manualDisponible($rol),
            'archivoManual' => CatalogoAyuda::archivoManual($rol),
            'emailSoporte' => CatalogoAyuda::EMAIL_SOPORTE,
        ]);
    }
}
