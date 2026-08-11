<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\ServicioAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Recuperación de contraseña.
 *
 * El diseño de la tabla ya era correcto en el sistema original y la
 * auditoría lo evaluó así: token de 256 bits, hasheado en base, 30
 * minutos de vigencia, un solo uso y mensaje genérico.
 *
 * Lo que faltaba era el rate limiting (H-024): sin límite, el endpoint
 * permite spam de correos, enumeración de usuarios por tiempos de
 * respuesta y DoS contra el servicio de email. Acá se limita por IP y
 * por email.
 *
 * El mensaje de respuesta es SIEMPRE el mismo, exista o no la cuenta.
 */
class RecuperarPasswordController extends Controller
{
    private const VIGENCIA_MINUTOS = 30;
    private const MAX_POR_EMAIL = 3;
    private const MAX_POR_IP = 5;
    private const VENTANA = 3600;

    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    // ── Solicitud ───────────────────────────────────────────────

    public function solicitar(): View
    {
        return view('admin.auth.recuperar-solicitar');
    }

    public function enviar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $this->verificarLimites($request, $datos['email']);

        $usuario = Usuario::where('email', $datos['email'])->first();

        // Solo se emite token si la cuenta existe y puede operar, pero la
        // respuesta al usuario es idéntica en todos los casos.
        if ($usuario && $usuario->status->puedeOperar()) {
            $this->emitirToken($usuario);
        }

        $this->auditoria->registrarSeguro([
            'company_id' => $usuario?->company_id,
            'user_id' => null,
            'origin' => 'web',
            'action' => 'user.password_reset_requested',
            'entity_type' => 'user',
            'entity_id' => $usuario?->id,
            'result' => 'success',
            'detail' => 'Solicitud de recuperación · email: ' . hash('sha256', $datos['email']),
        ]);

        return back()->with(
            'estado',
            'Si existe una cuenta con ese correo, vas a recibir un enlace para restablecer la contraseña.'
        );
    }

    // ── Restablecimiento ────────────────────────────────────────

    public function formulario(string $token): View
    {
        return view('admin.auth.recuperar-restablecer', ['token' => $token]);
    }

    public function restablecer(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()->symbols()],
        ]);

        $registro = DB::table('password_reset_tokens')
            ->where('token_hash', hash('sha256', $datos['token']))
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (! $registro) {
            throw ValidationException::withMessages([
                'token' => 'El enlace no es válido o ya venció. Pedí uno nuevo.',
            ]);
        }

        $usuario = Usuario::find($registro->user_id);

        if (! $usuario || ! $usuario->status->puedeOperar()) {
            throw ValidationException::withMessages([
                'token' => 'El enlace no es válido o ya venció. Pedí uno nuevo.',
            ]);
        }

        DB::transaction(function () use ($usuario, $datos) {
            $usuario->forceFill([
                'password_hash' => Hash::make($datos['password']),
                'password_changed_at' => now(),
                'must_change_password' => false,
                // Restablecer la contraseña levanta el bloqueo: quien
                // demostró control del correo no es el atacante que
                // provocó los intentos fallidos.
                'failed_attempts' => 0,
                'locked_until' => null,
            ])->save();

            // Un solo uso, y se invalidan los demás tokens vigentes de
            // esta cuenta: si se pidieron varios, el más nuevo gana y los
            // anteriores dejan de servir.
            DB::table('password_reset_tokens')
                ->where('user_id', $usuario->id)
                ->where('used', false)
                ->update(['used' => true]);

            $this->auditoria->registrar([
                'company_id' => $usuario->company_id,
                'user_id' => $usuario->id,
                'origin' => 'web',
                'action' => 'user.password_reset',
                'entity_type' => 'user',
                'entity_id' => $usuario->id,
                'result' => 'success',
                'detail' => 'Contraseña restablecida vía enlace de recuperación',
            ]);
        });

        return redirect()->route('admin.login')
            ->with('estado', 'Tu contraseña fue actualizada. Ya podés iniciar sesión.');
    }

    // ── Internos ────────────────────────────────────────────────

    private function emitirToken(Usuario $usuario): void
    {
        // 256 bits de random_bytes. El texto plano solo existe en el mail:
        // la base guarda únicamente el SHA-256.
        $token = bin2hex(random_bytes(32));

        DB::table('password_reset_tokens')->insert([
            'user_id' => $usuario->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(self::VIGENCIA_MINUTOS),
            'used' => false,
            'created_at' => now(),
        ]);

        // PENDIENTE: enviar el mail con Resend.
        // El envío queda para cuando el driver esté configurado y el
        // dominio verificado (§7 del brief). Mientras tanto el enlace
        // sale por el log, que en local es suficiente para probar.
        $url = route('admin.password.formulario', ['token' => $token]);

        if (app()->environment('local')) {
            logger()->info("[RECUPERACION] Enlace para {$usuario->email}: {$url}");
        }
    }

    private function verificarLimites(Request $request, string $email): void
    {
        $porIp = 'password:ip:' . $request->ip();
        $porEmail = 'password:email:' . hash('sha256', $email);

        foreach ([[$porIp, self::MAX_POR_IP], [$porEmail, self::MAX_POR_EMAIL]] as [$clave, $maximo]) {
            if (RateLimiter::tooManyAttempts($clave, $maximo)) {
                throw ValidationException::withMessages([
                    'email' => 'Demasiadas solicitudes. Esperá un rato antes de reintentar.',
                ]);
            }

            RateLimiter::hit($clave, self::VENTANA);
        }
    }
}