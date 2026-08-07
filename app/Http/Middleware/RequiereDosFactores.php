<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que el segundo factor esté verificado en esta sesión.
 *
 * Cubre el caso de una sesión que se abrió por otra vía sin pasar por el
 * desafío. Si aparece alguna ruta que autentique sin verificar 2FA, este
 * middleware la ataja.
 *
 * PENDIENTE DE DECISIÓN: hoy el 2FA es opcional por usuario
 * (two_factor_enabled). Para volverlo obligatorio en ciertos roles —el
 * superadmin es el candidato obvio— el chequeo va acá: si el rol lo exige
 * y el usuario no lo tiene activo, redirigir al alta en vez de dejar pasar.
 */
class RequiereDosFactores
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->two_factor_enabled) {
            return $next($request);
        }

        if (! $request->session()->get('2fa.verificado', false)) {
            return redirect()->route('admin.2fa.mostrar');
        }

        return $next($request);
    }
}
