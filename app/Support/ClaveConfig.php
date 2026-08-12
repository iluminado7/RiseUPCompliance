<?php

namespace App\Support;

/**
 * Descripcion de una clave de global_config.
 *
 * global_config es clave/valor, pero la pantalla NO deja crear claves
 * arbitrarias: una clave que nadie lee es basura silenciosa, y una mal
 * tipeada rompe el default sin que nada avise. El catalogo declarado es
 * la fuente de verdad de que se puede configurar.
 */
final class ClaveConfig
{
    public function __construct(
        public readonly string $clave,
        public readonly string $etiqueta,
        public readonly string $tipo,          // coincide con global_config.value_type
        public readonly string $grupo,
        public readonly mixed $porDefecto,
        public readonly string $descripcion,
        public readonly array $reglas,
        public readonly bool $esSecreta = false,
        public readonly ?array $opciones = null,
        public readonly ?string $nota = null,
    ) {}

    /**
     * Nombre del campo en el formulario.
     *
     * Los puntos no pueden viajar en el name: el validador de Laravel los
     * lee como anidamiento, asi que 'config.system.default_timezone'
     * buscaria un array 'system' adentro de 'config'.
     */
    public function campo(): string
    {
        return str_replace('.', '__', $this->clave);
    }

    public static function claveDesdeCampo(string $campo): string
    {
        return str_replace('__', '.', $campo);
    }
}
