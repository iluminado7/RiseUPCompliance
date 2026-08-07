<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\ServicioAuditoria;
use App\Services\ServicioDosFactores;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Verificación y alta del segundo factor.
 *
 * El desafío ocurre entre el primer factor y la apertura de sesión: en
 * ese intervalo el usuario NO está autenticado, solo anotado en sesión
 * como pendiente. Si abandona, no queda nada abierto.
 *
 * El código TOTP también se limita por intentos: seis dígitos son un
 * millón de combinaciones, y sin límite un atacante que ya tenga la
 * contraseña puede probarlas.
 */
class DosFactoresController extends Controller
{
    private const MAX_INTENTOS = 5;
    private const VENTANA = 900;

    public function __construct(
        private readonly ServicioDosFactores $dosFactores,
        private readonly ServicioAuditoria $auditoria,
        private readonly LoginController $login,
    ) {}

    // ── Desafío durante el login ────────────────────────────────

    public function mostrar(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('2fa.usuario_pendiente')) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.dos-factores');
    }

    public function verificar(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo' => ['required', 'string', 'max:10'],
        ]);

        $usuarioId = $request->session()->get('2fa.usuario_pendiente');

        if (! $usuarioId) {
            return redirect()->route('admin.login');
        }

        $clave = '2fa:' . $usuarioId;

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $request->session()->forget(['2fa.usuario_pendiente', '2fa.recordar']);

            throw ValidationException::withMessages([
                'codigo' => 'Demasiados códigos incorrectos. Volvé a iniciar sesión.',
            ]);
        }

        $usuario = Usuario::with('rol', 'empresa')->findOrFail($usuarioId);

        if (! $this->dosFactores->verificar($usuario, $request->input('codigo'))) {
            RateLimiter::hit($clave, self::VENTANA);

            $this->auditoria->registrarSeguro([
                'company_id' => $usuario->company_id,
                'user_id' => null,
                'origin' => 'web',
                'action' => 'user.2fa_failed',
                'entity_type' => 'user',
                'entity_id' => $usuario->id,
                'result' => 'error',
                'detail' => 'Código de segundo factor incorrecto',
            ]);

            throw ValidationException::withMessages([
                'codigo' => 'El código no es válido.',
            ]);
        }

        RateLimiter::clear($clave);

        return $this->login->abrirSesion(
            $request,
            $usuario,
            (bool) $request->session()->get('2fa.recordar', false)
        );
    }

    // ── Alta del segundo factor (usuario ya autenticado) ────────

    public function mostrarAlta(Request $request): View
    {
        $usuario = $request->user();
        $secreto = $this->dosFactores->generarSecreto();

        // El secreto todavía no se guarda: solo se persiste cuando el
        // usuario demuestra que lo copió bien. Si se guardara antes, un
        // secreto mal transcrito lo dejaría afuera de su propia cuenta.
        $request->session()->put('2fa.secreto_propuesto', $secreto);

        return view('admin.auth.alta-dos-factores', [
            'secreto' => $secreto,
            'qr' => $this->dosFactores->qrSvg($usuario, $secreto),
        ]);
    }

    public function confirmarAlta(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo' => ['required', 'string', 'max:10'],
        ]);

        $secreto = $request->session()->get('2fa.secreto_propuesto');

        if (! $secreto) {
            return redirect()->route('admin.2fa.alta')
                ->withErrors(['codigo' => 'La configuración expiró. Empezá de nuevo.']);
        }

        if (! $this->dosFactores->verificarConSecreto($secreto, $request->input('codigo'))) {
            throw ValidationException::withMessages([
                'codigo' => 'El código no coincide. Revisá que la app esté bien configurada.',
            ]);
        }

        $usuario = $request->user();

        $this->dosFactores->guardarSecreto($usuario, $secreto);
        $this->dosFactores->activar($usuario);

        $request->session()->forget('2fa.secreto_propuesto');
        $request->session()->put('2fa.verificado', true);

        $this->auditoria->registrarSeguro([
            'company_id' => $usuario->company_id,
            'user_id' => $usuario->id,
            'origin' => 'web',
            'action' => 'user.2fa_enabled',
            'entity_type' => 'user',
            'entity_id' => $usuario->id,
            'result' => 'success',
        ]);

        return redirect()->route('admin.dashboard')
            ->with('estado', 'El segundo factor quedó activado.');
    }
}
