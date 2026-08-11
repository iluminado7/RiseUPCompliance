<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Onboarding\TokenOnboarding;
use App\Services\ServicioOnboarding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Altas por invitacion. Solo superadmin (lo restringe la ruta).
 *
 * -- LOS CUATRO MOMENTOS DEL FLUJO --
 *
 * El token tiene tres estados en la base pero el flujo tiene cuatro
 * momentos. La distincion sale de cruzar el estado con la existencia del
 * borrador:
 *
 *   pending   + sin borrador -> esperando que la empresa complete
 *   pending   + con borrador -> la empresa esta cargando, a medio camino
 *   completed + con borrador -> LISTO PARA REVISAR
 *   completed + sin borrador -> alta confirmada (el borrador se borro)
 *   expired                  -> vencido o revocado
 *
 * El token pasa a 'completed' cuando la EMPRESA envia el formulario, no
 * cuando el superadmin aprueba. En el sistema original los datos espejo
 * nunca se borraban, asi que las altas ya confirmadas seguian figurando
 * como pendientes de revision para siempre.
 */
class OnboardingController extends Controller
{
    public function __construct(
        private readonly ServicioOnboarding $servicio,
    ) {}

    public function index(Request $request): View
    {
        $tokens = TokenOnboarding::with(['creadoPor:id,first_name,last_name', 'empresa'])
            ->withCount(['sucursales', 'usuarios'])
            ->latest('created_at')
            ->get()
            ->map(function (TokenOnboarding $token) {
                $tieneBorrador = $token->empresa !== null;

                // El vencimiento se evalua al mostrar y no se persiste: un
                // token vencido es vencido aunque nadie haya entrado a
                // marcarlo. El original lo actualizaba recien cuando
                // alguien abria el link.
                $token->situacion = match (true) {
                    $token->status === 'expired' => 'revocado',
                    $token->status === 'completed' && $tieneBorrador => 'por_revisar',
                    $token->status === 'completed' => 'confirmado',
                    $token->estaVencido() => 'vencido',
                    $tieneBorrador => 'en_curso',
                    default => 'enviado',
                };

                return $token;
            });

        $revisar = null;

        if ($idRevisar = $request->query('revisar')) {
            $revisar = TokenOnboarding::with([
                'empresa.sucursales',
                'empresa.usuarios',
                'empresa.datosFiscales',
            ])->find($idRevisar);

            // Solo se revisa lo que la empresa efectivamente envio.
            if ($revisar && (! $revisar->empresa || $revisar->status !== 'completed')) {
                $revisar = null;
            }
        }

        return view('admin.onboarding.index', [
            'tokens' => $tokens,
            'revisar' => $revisar,
        ]);
    }

    public function generar(): RedirectResponse
    {
        $token = $this->servicio->generarToken();

        return redirect()
            ->route('admin.onboarding.index')
            ->with('estado', 'Link generado. Vence el ' . $token->expires_at->format('d/m/Y H:i') . '.')
            // El token en claro solo existe en este momento: la base guarda
            // el valor pero la pantalla no lo lista, para que un vistazo
            // sobre el hombro no alcance para tomar un link ajeno.
            ->with('token_nuevo', $token->token);
    }

    public function revocar(TokenOnboarding $token): RedirectResponse
    {
        $this->servicio->revocar($token);

        return redirect()
            ->route('admin.onboarding.index')
            ->with('estado', 'Link revocado.');
    }

    public function confirmar(Request $request, TokenOnboarding $token): RedirectResponse
    {
        $ediciones = $request->validate([
            'name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/'],
            'default_language' => ['nullable', 'in:es,en,pt'],
            'timezone' => ['nullable', 'timezone'],
            'complaints_retention_days' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'files_retention_days' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'logs_retention_days' => ['nullable', 'integer', 'min:1', 'max:32767'],
        ]);

        try {
            $empresa = $this->servicio->confirmarAlta(
                $token,
                array_filter($ediciones, fn ($v) => $v !== null && $v !== '')
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.administracion.index', ['tab' => 'empresas'])
            ->with('estado', "Empresa {$empresa->name} dada de alta.");
    }
}
