<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltroReportesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'empresa' => ['nullable', 'string', 'max:100'],
            'sucursal' => ['nullable', 'string', 'max:100'],
            'fecha' => ['nullable', Rule::in(['hoy', 'semana', 'mes', 'personalizado'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'tab' => ['nullable', Rule::in(['1', '2', '3', '4', '5', '6'])],
        ];
    }

    public function filtros(): array
    {
        $validador = $this->getValidatorInstance();

        $datos = $validador->fails()
            ? array_diff_key($this->all(), $validador->errors()->toArray())
            : $validador->validated();

        $datos = array_filter($datos, fn ($v) => $v !== null && $v !== '');

        // El periodo por defecto es el ultimo mes, igual que el original.
        $datos['fecha'] = $datos['fecha'] ?? 'mes';

        return $datos;
    }

    public function tab(): string
    {
        $tab = (string) $this->input('tab', '1');

        return in_array($tab, ['1', '2', '3', '4', '5', '6'], true) ? $tab : '1';
    }
}
