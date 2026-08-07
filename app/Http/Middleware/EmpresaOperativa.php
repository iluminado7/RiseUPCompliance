<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta el acceso al panel si la empresa del usuario fue desactivada.
 *
 * Comportamiento portado literal de auth.php: `suspended` NO bloquea — sus
 * usuarios siguen operando los casos abiertos, solo se cierra el canal
 * público. Únicamente `deactivated` cierra el panel.
 *
 * El superadmin no tiene empresa, así que nunca se bloquea.
 */
class EmpresaOperativa
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->company_id) {
            return $next($request);
        }

        $empresa = $usuario->empresa;

        if ($empresa && ! $empresa->permiteAccesoAlPanel()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'El acceso de esta empresa fue desactivado.']);
        }

        return $next($request);
    }
}
