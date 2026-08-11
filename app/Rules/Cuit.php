<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida un CUIT/CUIL argentino por digito verificador (modulo 11).
 *
 * El brief (8.1) senala que el CUIT es la clave natural de deduplicacion
 * de organizaciones entre modulos de la plataforma unificada: es mejor
 * identificador que el nombre. Por eso conviene que sea correcto desde el
 * principio y no despues, con datos ya cargados.
 *
 * La regla equivalente existe en Business Partner. Si se porta aquella,
 * reemplazar esta por la portada en vez de mantener dos.
 */
class Cuit implements ValidationRule
{
    private const MULTIPLICADORES = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digitos = preg_replace('/\D/', '', (string) $value);

        if (strlen($digitos) !== 11) {
            $fail('El CUIT debe tener 11 dígitos.');

            return;
        }

        $suma = 0;
        for ($i = 0; $i < 10; $i++) {
            $suma += (int) $digitos[$i] * self::MULTIPLICADORES[$i];
        }

        $resto = $suma % 11;
        $verificador = match ($resto) {
            0 => 0,
            1 => 9,
            default => 11 - $resto,
        };

        if ($verificador !== (int) $digitos[10]) {
            $fail('El CUIT no es válido: el dígito verificador no corresponde.');
        }
    }
}
