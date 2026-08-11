<?php

namespace App\Http\Requests;

use App\Rules\Cuit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edicion de empresa.
 *
 * El slug NO esta entre las reglas de edicion a proposito: es la URL del
 * canal publico y no debe cambiarse desde el formulario. Ver la nota en
 * EmpresaController.
 */
class GuardarEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $empresa = $this->route('empresa');
        $esAlta = $empresa === null;

        $reglas = [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255'],

            'default_language' => ['required', Rule::in(['es', 'en', 'pt'])],
            'timezone' => ['required', 'timezone'],

            'complaints_retention_days' => ['required', 'integer', 'min:1', 'max:32767'],
            'files_retention_days' => ['required', 'integer', 'min:1', 'max:32767'],
            'logs_retention_days' => ['required', 'integer', 'min:1', 'max:32767'],

            // Fiscales: todos opcionales, igual que el original.
            'tax_id' => ['nullable', 'string', new Cuit],
            'legal_name' => ['nullable', 'string', 'max:300'],
            'vat_status' => ['nullable', Rule::in(['RI', 'Monotax', 'Exempt', 'FinalConsumer'])],
            'fiscal_address' => ['nullable', 'string', 'max:1000'],
            'billing_emails' => ['nullable', 'string', 'max:1000'],
            'preferred_payment' => ['nullable', Rule::in(['bank_transfer', 'debit', 'check'])],
            'billing_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'uses_global_price' => ['nullable', 'boolean'],
            'custom_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ];

        if ($esAlta) {
            // El slug solo se define al crear.
            $reglas['slug'] = [
                'required', 'string', 'max:100',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('companies', 'slug'),
            ];

            // Sede central: se crea junto con la empresa.
            $reglas['sede_nombre'] = ['required', 'string', 'max:200'];
            $reglas['sede_codigo'] = ['nullable', 'string', 'max:50'];
            $reglas['sede_direccion'] = ['nullable', 'string', 'max:1000'];
        } else {
            $reglas['status'] = ['required', Rule::in(['active', 'suspended', 'deactivated'])];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'El slug solo puede tener minúsculas, números y guiones.',
            'slug.unique' => 'Ya existe una empresa con ese slug.',
            'sede_nombre.required' => 'La sede central necesita un nombre.',
        ];
    }

    /** billing_emails llega como texto separado por comas y se guarda como JSON. */
    public function emailsFacturacion(): ?array
    {
        $texto = $this->input('billing_emails');

        if (! $texto) {
            return null;
        }

        $emails = array_values(array_filter(array_map('trim', explode(',', $texto))));

        return $emails ?: null;
    }
}
