<?php

namespace App\Http\Middleware;

use App\Enums\RolUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Restringe una ruta a ciertos roles.
 *
 *     Route::middleware('rol:superadmin,admin_principal')
 *
 * PUNTO ÚNICO DE AUTORIZACIÓN (§8.3 del brief).
 *
 * Todo chequeo de acceso al módulo pasa por acá, para que agregar el
 * entitlement del core más adelante sea una línea:
 *
 *     hoy:     Route::middleware(['auth', 'rol:gestor'])
 *     mañana:  Route::middleware(['auth', 'modulo:compliance', 'rol:gestor'])
 *
 * Si el chequeo estuviera repartido por los controladores, ese cambio
 * serían cincuenta ediciones en vez de una.
 *
 * Devuelve 404 y no 403 (§6.1): un 403 confirma que la ruta existe.
 */
class RequiereRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            throw new NotFoundHttpException;
        }

        $actual = $usuario->nombreRol();

        if ($actual === null) {
            throw new NotFoundHttpException;
        }

        $permitidos = array_filter(
            array_map(static fn (string $r) => RolUsuario::tryFrom($r), $roles)
        );

        if (! in_array($actual, $permitidos, true)) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }
}
