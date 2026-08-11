<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServicioAuditoria;
use App\Services\ServicioCifrado;
use App\Services\ServicioDosFactores;
use App\Services\ServicioUsuarioPanel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Perfil del usuario autenticado.
 *
 * Todo lo de acá opera sobre auth()->user() y nada más: no hay ningún
 * parámetro que permita apuntar a otro usuario. Es la contracara de
 * UsuarioPolicy::update(), que prohíbe editarse a sí mismo desde
 * Administración — allá están el rol y el estado, que nadie debe poder
 * cambiarse solo; acá está lo propio, que nadie más debería tocar.
 */
class PerfilController extends Controller
{
    private const ZONAS = [
        'America/Argentina/Buenos_Aires' => 'Buenos Aires (UTC-3)',
        'America/Sao_Paulo' => 'São Paulo (UTC-3)',
        'America/Montevideo' => 'Montevideo (UTC-3)',
        'America/Santiago' => 'Santiago (UTC-3/-4)',
        'America/Bogota' => 'Bogotá (UTC-5)',
        'America/Lima' => 'Lima (UTC-5)',
        'America/Mexico_City' => 'Ciudad de México (UTC-6)',
        'Europe/Madrid' => 'Madrid (UTC+1/+2)',
        'Europe/London' => 'Londres (UTC+0/+1)',
        'UTC' => 'UTC',
    ];

    public function __construct(
        private readonly ServicioAuditoria $auditoria,
        private readonly ServicioDosFactores $dosFactores,
        private readonly ServicioUsuarioPanel $usuarios,
        private readonly ServicioCifrado $cifrado,
    ) {}

    public function index(Request $request): View
    {
        $usuario = $request->user()->load(['rol', 'empresa:id,name', 'sucursales:id,name']);

        $tab = $request->query('tab', 'datos');

        if (! in_array($tab, ['datos', 'password', '2fa', 'preferencias'], true)) {
            $tab = 'datos';
        }

        return view('admin.perfil.index', [
            'usuario' => $usuario,
            'tab' => $tab,
            'zonas' => self::ZONAS,
            'telefono' => $this->usuarios->telefonoDe($usuario),
        ]);
    }

    // -- Datos personales ----------------------------------------

    public function guardarDatos(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            // Sin regex de "solo letras". El original rechazaba guiones,
            // apóstrofes, diéresis y las vocales del portugués, así que
            // había gente que no podía escribir su propio nombre.
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($usuario, $datos) {
            $usuario->first_name = $datos['first_name'];
            $usuario->last_name = $datos['last_name'];

            $usuario->phone = ($datos['phone'] ?? '') === ''
                ? null
                : $this->cifrado->cifrar($datos['phone'], null, 'users.phone:' . $usuario->id);

            $usuario->save();

            $this->auditoria->registrar([
                'company_id' => $usuario->company_id,
                'user_id' => $usuario->id,
                'origin' => 'web',
                'action' => 'user.profile_updated',
                'entity_type' => 'user',
                'entity_id' => $usuario->id,
                'result' => 'success',
            ]);
        });

        return redirect()
            ->route('admin.perfil', ['tab' => 'datos'])
            ->with('estado', 'Datos actualizados.');
    }

    // -- Contraseña ----------------------------------------------

    public function cambiarPassword(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'password_actual' => ['required', 'string'],
            'password' => [
                'required', 'confirmed',
                Password::min(12)->letters()->numbers()->symbols(),
            ],
        ]);

        if (! Hash::check($datos['password_actual'], $usuario->password_hash)) {
            throw ValidationException::withMessages([
                'password_actual' => 'La contraseña actual no es correcta.',
            ]);
        }

        if (Hash::check($datos['password'], $usuario->password_hash)) {
            throw ValidationException::withMessages([
                'password' => 'La nueva contraseña tiene que ser distinta de la actual.',
            ]);
        }

        DB::transaction(function () use ($usuario) {
            $usuario->forceFill([
                'password_hash' => Hash::make(request('password')),
                'password_changed_at' => now(),
                'must_change_password' => false,
            ])->save();

            $this->auditoria->registrar([
                'company_id' => $usuario->company_id,
                'user_id' => $usuario->id,
                'origin' => 'web',
                'action' => 'user.password_changed',
                'entity_type' => 'user',
                'entity_id' => $usuario->id,
                'result' => 'success',
                'detail' => 'Cambio desde el perfil',
            ]);
        });

        // La sesión actual se abrió con la contraseña vieja. El original
        // no la tocaba.
        $request->session()->regenerate();

        return redirect()
            ->route('admin.perfil', ['tab' => 'password'])
            ->with('estado', 'Contraseña actualizada.');
    }

    // -- Segundo factor ------------------------------------------

    /**
     * Desactivar el 2FA.
     *
     * Pide la contraseña a propósito: si alguien deja la sesión abierta,
     * no puede bastar un clic para quitarle el segundo factor a la cuenta.
     * En el sistema original esta acción no existía en ningún lado.
     */
    public function desactivarDosFactores(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $request->validate([
            'password_actual' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password_actual'), $usuario->password_hash)) {
            throw ValidationException::withMessages([
                'password_actual' => 'La contraseña no es correcta.',
            ]);
        }

        DB::transaction(function () use ($usuario, $request) {
            $this->dosFactores->desactivar($usuario);

            $request->session()->forget('2fa.verificado');

            $this->auditoria->registrar([
                'company_id' => $usuario->company_id,
                'user_id' => $usuario->id,
                'origin' => 'web',
                'action' => 'user.2fa_disabled',
                'entity_type' => 'user',
                'entity_id' => $usuario->id,
                'result' => 'success',
            ]);
        });

        return redirect()
            ->route('admin.perfil', ['tab' => '2fa'])
            ->with('estado', 'La verificación en dos pasos quedó desactivada.');
    }

    // -- Preferencias --------------------------------------------

    public function guardarPreferencias(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'preferred_language' => ['required', Rule::in(['es', 'en', 'pt'])],
            'timezone' => ['required', Rule::in(array_keys(self::ZONAS))],
        ]);

        $usuario->fill($datos)->save();

        return redirect()
            ->route('admin.perfil', ['tab' => 'preferencias'])
            ->with('estado', 'Preferencias guardadas.');
    }
}
