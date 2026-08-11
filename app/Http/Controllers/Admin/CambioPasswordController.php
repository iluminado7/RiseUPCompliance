<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ServicioAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Cambio de contrasena obligatorio en el primer ingreso.
 *
 * Lo dispara users.must_change_password, que el onboarding setea al crear
 * los usuarios de una empresa nueva.
 */
class CambioPasswordController extends Controller
{
    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    public function mostrar(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.cambio-obligatorio');
    }

    public function guardar(Request $request): RedirectResponse
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

        DB::transaction(function () use ($usuario, $datos) {
            $usuario->forceFill([
                'password_hash' => Hash::make($datos['password']),
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
                'detail' => 'Cambio obligatorio en el primer ingreso',
            ]);
        });

        // Sesion nueva: la anterior se abrio con la contrasena vieja.
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')
            ->with('estado', 'Tu contraseña fue actualizada.');
    }
}
