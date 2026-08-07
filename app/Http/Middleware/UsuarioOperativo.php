<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Revalida al usuario en CADA request.
 *
 * auth.php leía todo de $_SESSION, escrito una sola vez en el login. Si
 * suspendías o bloqueabas a un usuario, su sesión abierta seguía
 * operando con normalidad hasta que cerrara el navegador. Lo mismo con el
 * rol: bajarlo de admin_principal a gestor no tenía efecto hasta el
 * siguiente login.
 *
 * El guard `web` recarga el usuario de la base en cada request, así que
 * acá alcanza con chequear y cortar.
 */
class UsuarioOperativo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return $next($request);
        }

        if (! $usuario->puedeOperar()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Tu cuenta ya no está activa.']);
        }

        return $next($request);
    }
}
