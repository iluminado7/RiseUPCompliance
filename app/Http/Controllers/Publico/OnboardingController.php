<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Onboarding\TokenOnboarding;
use App\Services\ServicioBorradorOnboarding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Formulario publico de alta de empresa.
 *
 * SIN SESION AUTENTICADA. El token de la URL es el unico control de
 * acceso, asi que se valida en cada request: que exista, que no este
 * vencido y que no se haya usado.
 *
 * Vive en routes/web.php y no comparte middleware con el panel (2.1).
 */
class OnboardingController extends Controller
{
    public function __construct(
        private readonly ServicioBorradorOnboarding $servicio,
    ) {}

    public function mostrar(string $token): View
    {
        $registro = $this->resolverToken($token);

        if (! $registro->esUtilizable()) {
            return view('publico.onboarding.no-disponible', [
                'motivo' => $registro->status === 'completed'
                    ? 'Este formulario ya fue completado. Si necesitás corregir algo, escribinos.'
                    : 'Este enlace venció o fue revocado. Pedí uno nuevo a quien te lo envió.',
            ]);
        }

        $registro->load(['empresa.sucursales', 'empresa.usuarios', 'empresa.datosFiscales']);

        return view('publico.onboarding.formulario', [
            'token' => $registro,
            'paso' => $this->servicio->pasoActual($registro),
            'borrador' => $registro->empresa,
        ]);
    }

    public function guardarPaso1(Request $request, string $token): RedirectResponse
    {
        $registro = $this->resolverTokenUtilizable($token);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255'],
            'sede_nombre' => ['required', 'string', 'max:200'],
            'sede_direccion' => ['nullable', 'string', 'max:300'],
            'tax_id' => ['nullable', 'string', 'max:13'],
            'legal_name' => ['nullable', 'string', 'max:300'],
            'vat_status' => ['nullable', Rule::in(['RI', 'Monotax', 'Exempt', 'FinalConsumer'])],
            'fiscal_address' => ['nullable', 'string', 'max:1000'],
            'billing_emails' => ['nullable', 'string', 'max:1000'],
        ]);

        $datos['billing_emails'] = $this->emailsFacturacion($request->input('billing_emails'));

        $this->servicio->guardarPaso1($registro, $datos);

        return redirect()->route('onboarding.mostrar', $token);
    }

    public function guardarPaso2(Request $request, string $token): RedirectResponse
    {
        $registro = $this->resolverTokenUtilizable($token);

        $request->validate([
            'sucursales' => ['nullable', 'array', 'max:50'],
            'sucursales.*.nombre' => ['nullable', 'string', 'max:200'],
            'sucursales.*.direccion' => ['nullable', 'string', 'max:300'],
        ]);

        $this->servicio->guardarPaso2($registro, $request->input('sucursales', []));

        return redirect()->route('onboarding.mostrar', $token);
    }

    public function guardarPaso3(Request $request, string $token): RedirectResponse
    {
        $registro = $this->resolverTokenUtilizable($token);

        $request->validate([
            'usuarios' => ['required', 'array', 'min:1', 'max:20'],
            'usuarios.*.nombre' => ['required', 'string', 'max:100'],
            'usuarios.*.apellido' => ['required', 'string', 'max:100'],
            'usuarios.*.email' => ['required', 'email', 'max:255', 'distinct'],
            'usuarios.*.rol' => ['required', Rule::in(['admin_principal', 'gestor'])],
            // 12 caracteres con simbolos, igual que la recuperacion. El
            // original pedia 8 sin requisitos, asi que estos usuarios no
            // podian reusar su contrasena al recuperarla.
            'usuarios.*.password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()->symbols()],
        ], [], [
            'usuarios.*.nombre' => 'nombre',
            'usuarios.*.email' => 'email',
            'usuarios.*.password' => 'contraseña',
        ]);

        $this->servicio->guardarPaso3($registro, $request->input('usuarios'));

        return redirect()->route('onboarding.mostrar', $token);
    }

    public function confirmar(string $token): RedirectResponse
    {
        $registro = $this->resolverTokenUtilizable($token);
        $nombre = $registro->empresa?->name;

        $this->servicio->confirmarEnvio($registro);

        // Redirect y no vista: si devolviera la vista directamente, un F5
        // reintentaría el POST sobre una URL que ya no acepta nada.
        return redirect()
            ->route('onboarding.enviado', $token)
            ->with('empresa', $nombre);
    }

    public function enviado(string $token): View
    {
        // El token ya está usado, así que resolverTokenUtilizable() lo
        // rechazaría. Acá solo hace falta que exista.
        $this->resolverToken($token);

        return view('publico.onboarding.enviado', [
            'empresa' => session('empresa'),
        ]);
    }

    public function volver(Request $request, string $token): RedirectResponse
    {
        $registro = $this->resolverTokenUtilizable($token);

        $datos = $request->validate([
            'paso' => ['required', 'integer', 'min:1', 'max:3'],
        ]);

        $this->servicio->volverA($registro, $datos['paso']);

        return redirect()->route('onboarding.mostrar', $token);
    }

    // -- Internos ------------------------------------------------

    /**
     * Busca el token en tiempo constante.
     *
     * Se compara con hash_equals para no filtrar informacion por el tiempo
     * de respuesta. Devuelve 404 si no existe: un mensaje distinto
     * confirmaria que el token es valido pero, por ejemplo, vencido.
     */
    private function resolverToken(string $token): TokenOnboarding
    {
        $registro = TokenOnboarding::where('token', $token)->first();

        abort_unless($registro && hash_equals($registro->token, $token), 404);

        return $registro;
    }

    private function resolverTokenUtilizable(string $token): TokenOnboarding
    {
        $registro = $this->resolverToken($token);

        abort_unless($registro->esUtilizable(), 410, 'Este enlace ya no está disponible.');

        return $registro;
    }

    private function emailsFacturacion(?string $texto): ?array
    {
        if (! $texto) {
            return null;
        }

        $emails = array_values(array_filter(array_map('trim', explode(',', $texto))));

        return $emails ?: null;
    }
}
