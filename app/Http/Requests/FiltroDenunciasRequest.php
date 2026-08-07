<?php

namespace App\Http\Requests;

use App\Enums\EstadoDenuncia;
use App\Enums\PrioridadDenuncia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros del listado de denuncias.
 *
 * El sistema original validaba a mano con mb_substr, preg_match y listas
 * blancas repartidas por el archivo. Acá va todo junto, y las listas
 * blancas salen de los Enums en vez de estar duplicadas como literales
 * (la del original tenia 'under_review' y 'rejected', que no existen, y
 * le faltaba 'seen').
 *
 * Los filtros invalidos se descartan en silencio en lugar de devolver un
 * error: un listado con un parametro raro en la URL deberia mostrar el
 * listado, no una pantalla de validacion.
 */
class FiltroDenunciasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => mb_substr(trim((string) $this->input('codigo')), 0, 50) ?: null,
            'empresa' => mb_substr(trim((string) $this->input('empresa')), 0, 100) ?: null,
            'sucursal' => mb_substr(trim((string) $this->input('sucursal')), 0, 100) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:50'],
            'empresa' => ['nullable', 'string', 'max:100'],
            'sucursal' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::enum(EstadoDenuncia::class)],
            'prioridad' => ['nullable', Rule::enum(PrioridadDenuncia::class)],
            'fecha' => ['nullable', Rule::in(['hoy', 'semana', 'mes', 'personalizado'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'per_page' => ['nullable', Rule::in([10, 25, 50])],
            'pagina' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Devuelve solo los filtros validos. Descarta los que no pasaron
     * validacion en vez de rechazar el request entero.
     */
    public function filtros(): array
    {
        $validador = $this->getValidatorInstance();
        $datos = $validador->fails()
            ? array_diff_key($this->all(), $validador->errors()->toArray())
            : $validador->validated();

        return array_filter($datos, fn ($v) => $v !== null && $v !== '');
    }

    public function porPagina(): int
    {
        $valor = (int) $this->input('per_page', 10);

        return in_array($valor, [10, 25, 50], true) ? $valor : 10;
    }
}
