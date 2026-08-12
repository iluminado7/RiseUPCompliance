<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\HashIp;
use App\Services\ServicioAuditoria;
use App\Services\ServicioCifrado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Login del panel de gestión.
 *
 * Diferencias con admin/login.php:
 *
 * 1. BLOQUEO POR CUENTA, además del límite por IP.
 *    Las columnas failed_attempts y locked_until existían en el schema
 *    desde el principio y nunca se escribieron ni se leyeron. El único
 *    freno era el contador por IP, así que un atacante que rotara IPs
 *    tenía intentos ilimitados contra una cuenta concreta.
 *
 * 2. SIN sleep().
 *    El original hacía sleep(min($fallos, 5)), que retiene un worker de
 *    PHP-FPM durante la espera: un puñado de requests concurrentes con
 *    contraseña incorrecta ocupaba el pool y tiraba el sitio entero,
 *    incluido el canal público. El throttle rechaza sin retener proceso.
 *
 * 3. LOS FALLOS SE REGISTRAN.
 *    El original llamaba a registrarAudit con company_id = 0, y el helper
 *    abría con `if (!$company_id) return;`. Ningún intento fallido llegó
 *    nunca a audit_logs. Ahora van a la cadena de plataforma.
 *
 * 4. SIN trim() en la contraseña.
 *    El original la recortaba, así que cualquier contraseña con espacios
 *    al inicio o al final nunca habría validado.
 *
 * 5. 2FA REAL.
 *    Si el usuario lo tiene activo, la sesión no se abre hasta verificar
 *    el segundo factor.
 */
class LoginController extends Controller
{
    private const MAX_INTENTOS_IP = 5;
    private const VENTANA_IP = 900;        // 15 minutos
    private const MAX_INTENTOS_CUENTA = 5;
    private const BLOQUEO_CUENTA = 900;    // 15 minutos

    public function __construct(
        private readonly ServicioAuditoria $auditoria,
        private readonly ServicioCifrado $cifrado,
        private readonly HashIp $hashIp,
    ) {}

    public function mostrar(): View
    {
        return view('admin.auth.login');
    }

    public function ingresar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $this->verificarLimitePorIp($request);

        $usuario = Usuario::with('rol', 'empresa')
            ->where('email', $datos['email'])
            ->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password_hash)) {
            $this->registrarFallo($request, $usuario, 'credenciales');
            $this->rechazar();
        }

        if ($usuario->estaBloqueado()) {
            $this->registrarFallo($request, $usuario, 'cuenta_bloqueada', 'blocked');

            throw ValidationException::withMessages([
                'email' => 'La cuenta está bloqueada temporalmente por intentos fallidos.',
            ]);
        }

        if (! $usuario->status->puedeOperar()) {
            $this->registrarFallo($request, $usuario, 'usuario_inactivo', 'blocked');
            $this->rechazar();
        }

        // El denunciante no tiene cuenta: todo usuario válido acá es del panel.
        if ($usuario->nombreRol() === null) {
            $this->registrarFallo($request, $usuario, 'rol_invalido', 'blocked');
            $this->rechazar();
        }

        if ($usuario->empresa && ! $usuario->empresa->permiteAccesoAlPanel()) {
            $this->registrarFallo($request, $usuario, 'empresa_desactivada', 'blocked');

            throw ValidationException::withMessages([
                'email' => 'El acceso de esta empresa fue desactivado.',
            ]);
        }

        $this->limpiarIntentos($request, $usuario);

        // Con 2FA activo la sesión NO se abre todavía: solo queda anotado
        // quién superó el primer factor.
        if ($usuario->two_factor_enabled) {
            $request->session()->put('2fa.usuario_pendiente', $usuario->id);
            $request->session()->put('2fa.recordar', $request->boolean('remember'));

            return redirect()->route('admin.2fa.mostrar');
        }

        return $this->abrirSesion($request, $usuario);
    }

    /**
     * Abre la sesión efectivamente. La llama este controlador cuando no
     * hay 2FA, y DosFactoresController cuando el código se verificó.
     */
    public function abrirSesion(Request $request, Usuario $usuario, bool $recordar = false): RedirectResponse
    {
        Auth::login($usuario, $recordar);
        $request->session()->regenerate();
        $request->session()->forget(['2fa.usuario_pendiente', '2fa.recordar']);
        $request->session()->put('2fa.verificado', true);

        $usuario->forceFill([
            'last_login_at' => now(),
            'last_login_ip_hash' => $this->hashIp->hash($request->ip()),
            'last_login_ip_enc' => $this->cifrado->cifrar($request->ip()),
            'failed_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $this->auditoria->registrarSeguro([
            'company_id' => $usuario->company_id,
            'user_id' => $usuario->id,
            'origin' => 'web',
            'action' => 'user.login',
            'entity_type' => 'user',
            'entity_id' => $usuario->id,
            'result' => 'success',
            'detail' => 'Login exitoso · rol: ' . $usuario->nombreRol()?->value,
        ]);

        $request->session()->forget('url.intended');

        return redirect()->route('admin.dashboard');
    }

    public function salir(Request $request): RedirectResponse
    {
        $usuario = Auth::user();

        if ($usuario) {
            $this->auditoria->registrarSeguro([
                'company_id' => $usuario->company_id,
                'user_id' => $usuario->id,
                'origin' => 'web',
                'action' => 'user.logout',
                'entity_type' => 'user',
                'entity_id' => $usuario->id,
                'result' => 'success',
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    // ── Internos ────────────────────────────────────────────────

    private function verificarLimitePorIp(Request $request): void
    {
        $clave = 'login:ip:' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS_IP)) {
            $segundos = RateLimiter::availableIn($clave);

            throw ValidationException::withMessages([
                'email' => "Demasiados intentos. Reintentá en " . ceil($segundos / 60) . " minutos.",
            ]);
        }
    }

    /**
     * Mensaje idéntico para usuario inexistente, contraseña incorrecta y
     * usuario inactivo: distinguirlos permite enumerar cuentas válidas.
     */
    private function rechazar(): never
    {
        throw ValidationException::withMessages([
            'email' => 'Email o contraseña incorrectos.',
        ]);
    }

    private function registrarFallo(Request $request, ?Usuario $usuario, string $motivo, string $resultado = 'error'): void
    {
        RateLimiter::hit('login:ip:' . $request->ip(), self::VENTANA_IP);

        if ($usuario) {
            $usuario->increment('failed_attempts');
            $usuario->last_failed_login_at = now();

            if ($usuario->failed_attempts >= self::MAX_INTENTOS_CUENTA) {
                $usuario->locked_until = now()->addSeconds(self::BLOQUEO_CUENTA);
            }

            $usuario->save();
        }

        // El email va hasheado: audit_logs no debe volverse un padrón de
        // direcciones válidas para quien lo lea.
        $this->auditoria->registrarSeguro([
            'company_id' => $usuario?->company_id,
            'user_id' => null,
            'origin' => 'web',
            'action' => 'user.login_failed',
            'entity_type' => 'user',
            'entity_id' => $usuario?->id,
            'result' => $resultado,
            'detail' => "Intento fallido ({$motivo}) · email: " . hash('sha256', (string) $request->input('email')),
        ]);
    }

    private function limpiarIntentos(Request $request, Usuario $usuario): void
    {
        RateLimiter::clear('login:ip:' . $request->ip());

        if ($usuario->failed_attempts > 0 || $usuario->locked_until !== null) {
            $usuario->forceFill(['failed_attempts' => 0, 'locked_until' => null])->save();
        }
    }
}
