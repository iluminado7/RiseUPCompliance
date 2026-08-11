<?php

namespace App\Http\Requests;

use App\Services\ServicioUsuarioPanel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Alta y edicion de usuarios del panel.
 *
 * -- ROL ASIGNABLE --
 *
 * role_id se valida contra la lista que el usuario AUTENTICADO puede
 * asignar, no contra la tabla roles entera. Es lo que impide que un
 * admin_principal se promueva a superadmin mandando un role_id en el POST:
 * el original validaba esto al crear pero no al editar.
 *
 * -- CONTRASENA --
 *
 * 12 caracteres con letras, numeros y simbolos, igual que la recuperacion.
 * Antes el alta pedia 8 sin requisitos, asi que un usuario creado aca no
 * podia reusar su contrasena al recuperarla.
 */
class GuardarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $esAlta = $usuario === null;

        $rolesPermitidos = ServicioUsuarioPanel::rolesAsignables($this->user())
            ->pluck('id')
            ->all();

        $reglas = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'role_id' => ['required', 'integer', Rule::in($rolesPermitidos)],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
        ];

        if ($esAlta) {
            $reglas['password'] = ['required', 'confirmed', $this->reglaPassword()];
        } else {
            $reglas['status'] = ['required', Rule::in(['active', 'inactive', 'suspended'])];
            // Vacia = no cambiar. Si viene, tiene que cumplir la politica:
            // el original ignoraba en silencio las de menos de 8.
            $reglas['nueva_password'] = ['nullable', 'confirmed', $this->reglaPassword()];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'role_id.in' => 'No tenés permiso para asignar ese rol.',
            'email.unique' => 'Ya existe un usuario con ese email.',
        ];
    }

    /** @return array<int> */
    public function sucursales(): array
    {
        return array_map('intval', (array) $this->input('branch_ids', []));
    }

    private function reglaPassword(): Password
    {
        return Password::min(12)->letters()->numbers()->symbols();
    }
}
