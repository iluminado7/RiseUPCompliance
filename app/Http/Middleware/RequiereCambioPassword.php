<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fuerza el cambio de contrasena en el primer ingreso.
 *
 * users.must_change_password existe en el schema desde el principio y el
 * onboarding la seteaba en 1, pero NADA en el sistema la leia: el usuario
 * entraba con la contrasena que otra persona de su empresa cargo en un
 * formulario web, y nunca se le pedia cambiarla.
 *
 * Se deja pasar la propia pantalla de cambio y el logout, para no dejar al
 * usuario encerrado sin salida.
 */
class RequiereCambioPassword
{
    private const PERMITIDAS = [
        'admin.password.cambio-obligatorio',
        'admin.password.cambio-obligatorio.guardar',
        'admin.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->must_change_password) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::PERMITIDAS, true)) {
            return $next($request);
        }

        return redirect()->route('admin.password.cambio-obligatorio');
    }
}
