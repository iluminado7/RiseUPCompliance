<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltroLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'empresa' => ['nullable', 'string', 'max:100'],
            'usuario' => ['nullable', 'string', 'max:150'],
            'accion' => ['nullable', 'string', 'max:100'],
            'resultado' => ['nullable', Rule::in(['success', 'error', 'blocked'])],
            'origen' => ['nullable', Rule::in(['web', 'api', 'system', 'cron'])],
            'denuncia' => ['nullable', 'string', 'max:50'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'cadena' => ['nullable', 'string', 'max:20'],
        ];
    }

    /** Filtros validos, descartando los que no pasaron validacion. */
    public function filtros(): array
    {
        $validador = $this->getValidatorInstance();

        $datos = $validador->fails()
            ? array_diff_key($this->all(), $validador->errors()->toArray())
            : $validador->validated();

        return array_filter($datos, fn ($v) => $v !== null && $v !== '');
    }
}
