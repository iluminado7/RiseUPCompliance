<?php

namespace App\Http\Controllers\Publico;

use App\Enums\EstadoDenuncia;
use App\Http\Controllers\Controller;
use App\Models\Denuncia;
use App\Models\Mensaje;
use App\Models\SesionSeguimiento;
use App\Services\HashIp;
use App\Services\ServicioCifrado;
use App\Services\ServicioCodigoSeguimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Consulta del estado de una denuncia por codigo de seguimiento.
 *
 * SIN SESION AUTENTICADA. El codigo es lo unico que da acceso, asi que
 * esta pantalla es la superficie mas expuesta del sistema.
 *
 * -- QUE SE MUESTRA Y QUE NO (H-005 del informe) --
 *
 * Estado, fecha y chat. NUNCA los datos del denunciado: quien tenga el
 * codigo no necesariamente es el denunciante, y saber a quien se denuncio
 * puede poner a alguien en riesgo.
 *
 * -- CONTRA LA ENUMERACION (H-009) --
 *
 * Tres intentos fallidos por IP y por hora. Cada consulta queda registrada
 * en tracking_sessions, con la IP hasheada por HMAC: el SHA-256 sin clave
 * del sistema original era reversible sobre el espacio IPv4 completo, y
 * esta tabla relaciona una IP con una denuncia concreta.
 */
class SeguimientoController extends Controller
{
    private const MAX_INTENTOS = 3;
    private const VENTANA = 3600;

    public function __construct(
        private readonly ServicioCodigoSeguimiento $codigos,
        private readonly ServicioCifrado $cifrado,
        private readonly HashIp $hashIp,
    ) {}

    public function formulario(): View
    {
        return view('publico.seguimiento.formulario');
    }

    public function consultar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:40'],
        ]);

        $clave = 'seguimiento:' . $request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $this->registrarConsulta($request, null, null, 'rate_limit');

            $minutos = ceil(RateLimiter::availableIn($clave) / 60);

            throw ValidationException::withMessages([
                'codigo' => "Demasiados intentos. Volvé a probar en {$minutos} minutos.",
            ]);
        }

        $hash = $this->codigos->hashear($datos['codigo']);

        $denuncia = Denuncia::withoutGlobalScopes()
            ->where('tracking_code_hash', $hash)
            ->first();

        if (! $denuncia) {
            RateLimiter::hit($clave, self::VENTANA);
            $this->registrarConsulta($request, null, null, 'invalid_code');

            throw ValidationException::withMessages([
                'codigo' => 'El código no corresponde a ninguna denuncia.',
            ]);
        }

        RateLimiter::clear($clave);
        $this->registrarConsulta($request, $denuncia->company_id, $denuncia->id, 'success');

        // El código queda en la sesión, no en la URL: en la barra de
        // direcciones entra al historial del navegador y viaja en el
        // Referer de cualquier recurso externo que cargue la página.
        $request->session()->put('seguimiento.denuncia_id', $denuncia->id);

        return redirect()->route('seguimiento.estado');
    }

    public function estado(Request $request): View|RedirectResponse
    {
        $denuncia = $this->denunciaDeSesion($request);

        if (! $denuncia) {
            return redirect()->route('seguimiento.formulario');
        }

        return view('publico.seguimiento.estado', [
            'denuncia' => $denuncia,
            'mensajes' => $this->conversacion($denuncia),
            'puedeEscribir' => $denuncia->chat_enabled && ! $denuncia->is_anonymous,
        ]);
    }

    public function responder(Request $request): RedirectResponse
    {
        $denuncia = $this->denunciaDeSesion($request);

        if (! $denuncia) {
            return redirect()->route('seguimiento.formulario');
        }

        abort_unless($denuncia->chat_enabled && ! $denuncia->is_anonymous, 403);

        $datos = $request->validate([
            'contenido' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($denuncia, $datos) {
            $mensaje = new Mensaje([
                'company_id' => $denuncia->company_id,
                'complaint_id' => $denuncia->id,
                'sender_type' => 'reporter',
                'sender_user_id' => null,
                'encryption_key_id' => $this->cifrado->claveActivaId(),
            ]);

            $mensaje->save();

            $mensaje->content = $this->cifrado->cifrar(
                $datos['contenido'],
                $denuncia->encryption_salt,
                'complaint_messages.content:' . $mensaje->id
            );
            $mensaje->save();

            // Los mensajes del analista quedan marcados como leídos: si el
            // denunciante está respondiendo, los vio.
            Mensaje::where('complaint_id', $denuncia->id)
                ->where('sender_type', 'analyst')
                ->where('is_read_by_reporter', false)
                ->update(['is_read_by_reporter' => true]);

            $denuncia->forceFill(['last_action_at' => now()])->save();

            DB::table('user_notifications')->insert(
                DB::table('users')
                    ->join('roles', 'roles.id', '=', 'users.role_id')
                    ->where('users.company_id', $denuncia->company_id)
                    ->where('users.status', 'active')
                    ->whereIn('roles.name', ['admin_principal', 'gestor', 'investigador_externo'])
                    ->pluck('users.id')
                    ->map(fn ($id) => [
                        'company_id' => $denuncia->company_id,
                        'user_id' => $id,
                        'complaint_id' => $denuncia->id,
                        'type' => 'new_message',
                        'status' => 'pending',
                        'created_at' => now(),
                    ])
                    ->all()
            );
        });

        return redirect()->route('seguimiento.estado')
            ->with('estado', 'Tu mensaje fue enviado.');
    }

    public function salir(Request $request): RedirectResponse
    {
        $request->session()->forget('seguimiento.denuncia_id');

        return redirect()->route('seguimiento.formulario')
            ->with('estado', 'Cerraste la consulta.');
    }

    // -- Internos ------------------------------------------------

    private function denunciaDeSesion(Request $request): ?Denuncia
    {
        $id = $request->session()->get('seguimiento.denuncia_id');

        if (! $id) {
            return null;
        }

        return Denuncia::withoutGlobalScopes()->find($id);
    }

    /**
     * Conversación visible para el denunciante.
     *
     * Los mensajes retirados por el analista no se incluyen, y los que no
     * se puedan descifrar se muestran como error en lugar de romper la
     * página entera.
     */
    private function conversacion(Denuncia $denuncia)
    {
        if (! $denuncia->chat_enabled) {
            return collect();
        }

        return $denuncia->mensajes()
            ->visiblesParaDenunciante()
            ->get()
            ->map(function (Mensaje $mensaje) use ($denuncia) {
                try {
                    $texto = $this->cifrado->descifrar(
                        $mensaje->content,
                        $denuncia->encryption_salt,
                        'complaint_messages.content:' . $mensaje->id
                    );
                } catch (Throwable $e) {
                    Log::warning('[SEGUIMIENTO] No se pudo descifrar un mensaje', [
                        'mensaje' => $mensaje->id,
                    ]);
                    $texto = null;
                }

                return [
                    // El denunciante ve "El equipo", no el nombre del
                    // investigador: la identidad de quien investiga tampoco
                    // es información que deba circular.
                    'de_equipo' => $mensaje->sender_type !== 'reporter',
                    'texto' => $texto,
                    'fecha' => $mensaje->created_at,
                ];
            });
    }

    /**
     * Registra la consulta en tracking_sessions.
     *
     * Los intentos fallidos también: son la señal de que alguien está
     * probando códigos.
     */
    private function registrarConsulta(Request $request, ?int $empresaId, ?int $denunciaId, string $resultado): void
    {
        try {
            SesionSeguimiento::create([
                'company_id' => $empresaId,
                'complaint_id' => $denunciaId,
                'tracking_code_hash' => $this->codigos->hashear((string) $request->input('codigo')),
                'ip_hash' => $this->hashIp->hash($request->ip()),
                'ip_enc' => $this->cifrado->cifrar($request->ip()),
                'user_agent_hash' => $this->hashIp->hash($request->userAgent()),
                'user_agent_enc' => $this->cifrado->cifrar($request->userAgent()),
                'result' => $resultado,
            ]);
        } catch (Throwable $e) {
            // Perder el registro de una consulta no debe impedirle a
            // alguien ver el estado de su denuncia.
            Log::error('[SEGUIMIENTO] No se pudo registrar la consulta', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
