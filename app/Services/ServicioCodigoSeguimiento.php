<?php

namespace App\Services;

use App\Models\Denuncia;
use App\Models\Empresa;
use App\Models\Sucursal;
use RuntimeException;

/**
 * Codigos de una denuncia.
 *
 * Hay DOS, y no son lo mismo:
 *
 *   internal_code   Referencia del panel: SUC-847291-603847. Legible,
 *                   unico por empresa, en claro en la base.
 *
 *   tracking_code   Lo que recibe el denunciante. 20 caracteres de
 *                   entropia real; la base guarda solo su SHA-256.
 *
 * -- POR QUE DOS --
 *
 * enviar_denuncia.php generaba el tracking_code y lo descartaba: al
 * denunciante le mostraba el internal_code y seguimiento.php buscaba por
 * ahi. Con eso, cualquiera con acceso de lectura a la base podia
 * consultar el seguimiento de cualquier denuncia, porque el codigo estaba
 * en claro en una columna.
 *
 * Separandolos, un dump de la base ya no sirve para eso: los
 * internal_code que contiene no abren el seguimiento.
 */
class ServicioCodigoSeguimiento
{
    /**
     * Alfabeto sin caracteres que se confunden al leerlos de un papel o
     * al dictarlos por telefono: sin I, L, O, 0 ni 1.
     */
    private const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const BLOQUES = 4;
    private const LARGO_BLOQUE = 5;

    /**
     * Codigo de seguimiento en texto plano.
     *
     * 20 caracteres sobre un alfabeto de 31 dan unos 10^29 valores. Se
     * usa random_bytes, nunca rand() ni mt_rand(), que son predecibles a
     * partir de unas pocas salidas.
     */
    public function generarTracking(): string
    {
        $bloques = [];

        for ($b = 0; $b < self::BLOQUES; $b++) {
            $bloque = '';

            for ($c = 0; $c < self::LARGO_BLOQUE; $c++) {
                $bloque .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
            }

            $bloques[] = $bloque;
        }

        return implode('-', $bloques);
    }

    public function hashear(string $tracking): string
    {
        return hash('sha256', $this->normalizar($tracking));
    }

    /**
     * Normaliza lo que escribe el denunciante: mayusculas, sin espacios, y
     * con los guiones en su lugar. Alguien que copia el codigo de un papel
     * puede omitirlos o tipear minusculas.
     */
    public function normalizar(string $tracking): string
    {
        $limpio = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $tracking));

        return implode('-', str_split($limpio, self::LARGO_BLOQUE));
    }

    /**
     * Codigo interno, unico por empresa.
     *
     * El prefijo sale del codigo de la sucursal, o de las tres primeras
     * letras de la empresa si la sucursal no tiene uno.
     */
    public function generarInterno(Empresa $empresa, ?Sucursal $sucursal): string
    {
        $prefijo = $this->prefijo($empresa, $sucursal);

        for ($intento = 0; $intento < 10; $intento++) {
            $codigo = sprintf(
                '%s-%06d-%06d',
                $prefijo,
                random_int(0, 999999),
                random_int(0, 999999)
            );

            $existe = Denuncia::withoutGlobalScopes()
                ->where('company_id', $empresa->id)
                ->where('internal_code', $codigo)
                ->exists();

            if (! $existe) {
                return $codigo;
            }
        }

        throw new RuntimeException('No se pudo generar un código interno único.');
    }

    private function prefijo(Empresa $empresa, ?Sucursal $sucursal): string
    {
        if ($sucursal?->internal_code) {
            return strtoupper($sucursal->internal_code);
        }

        $normalizado = preg_replace('/[^A-Za-z]/', '', $this->sinAcentos($empresa->name));

        return strtoupper(substr($normalizado, 0, 3)) ?: 'GEN';
    }

    private function sinAcentos(string $texto): string
    {
        $convertido = @iconv('UTF-8', 'ASCII//TRANSLIT', $texto);

        return $convertido === false ? $texto : $convertido;
    }
}
