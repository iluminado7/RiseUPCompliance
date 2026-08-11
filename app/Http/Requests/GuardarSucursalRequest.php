<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sucursal = $this->route('sucursal');

        return [
            'name' => ['required', 'string', 'max:200'],
            'internal_code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_headquarter' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'default_language' => ['nullable', Rule::in(['es', 'en', 'pt'])],
            'timezone' => ['nullable', 'timezone'],

            // Solo el superadmin manda company_id; para admin_principal
            // sale de su sesion y este campo se ignora.
            'company_id' => [
                $sucursal ? 'nullable' : 'required_without:_empresa_de_sesion',
                'integer',
                Rule::exists('companies', 'id'),
            ],
        ];
    }

    public function datos(): array
    {
        return [
            'name' => $this->input('name'),
            'internal_code' => $this->input('internal_code') ?: null,
            'address' => $this->input('address') ?: null,
            'is_headquarter' => $this->boolean('is_headquarter'),
            'is_active' => $this->boolean('is_active', true),
            'default_language' => $this->input('default_language') ?: null,
            'timezone' => $this->input('timezone') ?: null,
        ];
    }
}
