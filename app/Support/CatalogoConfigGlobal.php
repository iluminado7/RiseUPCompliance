<?php

namespace App\Support;

/**
 * Catalogo de claves configurables del sistema.
 *
 * Agregar una clave aca es lo que la hace aparecer en la pantalla. Si una
 * fila existe en global_config pero no esta declarada, la pantalla la
 * muestra en solo lectura: se ve que esta, pero no se edita a ciegas.
 *
 * IMPORTANTE: el default declarado aca tiene que coincidir con el que use
 * el codigo que consume la clave. Si divergen, una instalacion sin fila en
 * la base se comporta distinto segun quien lea.
 */
final class CatalogoConfigGlobal
{
    public const GRUPO_EMPRESAS = 'Valores por defecto de nuevas empresas';
    public const GRUPO_RETENCION = 'Retencion de datos';

    /** Zonas horarias ofrecidas. Mismo listado que administracion y onboarding. */
    public const ZONAS = [
        'America/Argentina/Buenos_Aires' => 'Buenos Aires (UTC-3)',
        'America/Sao_Paulo' => 'Sao Paulo (UTC-3)',
        'America/Santiago' => 'Santiago (UTC-3/-4)',
        'America/Montevideo' => 'Montevideo (UTC-3)',
        'America/Asuncion' => 'Asuncion (UTC-3/-4)',
        'America/La_Paz' => 'La Paz (UTC-4)',
        'America/Bogota' => 'Bogota (UTC-5)',
        'America/Lima' => 'Lima (UTC-5)',
        'America/Mexico_City' => 'Ciudad de Mexico (UTC-6)',
        'Europe/Madrid' => 'Madrid (UTC+1/+2)',
        'UTC' => 'UTC',
    ];

    public const IDIOMAS = [
        'es' => 'Español',
        'en' => 'Ingles',
        'pt' => 'Portugues',
    ];

    /**
     * @return array<string, ClaveConfig>
     */
    public static function definiciones(): array
    {
        $claves = [
            new ClaveConfig(
                clave: 'system.default_timezone',
                etiqueta: 'Zona horaria',
                tipo: 'string',
                grupo: self::GRUPO_EMPRESAS,
                porDefecto: 'America/Argentina/Buenos_Aires',
                descripcion: 'Zona con la que se crean las empresas nuevas y en la '
                    . 'que se muestran las fechas cuando la empresa no definio la suya.',
                reglas: ['required', 'timezone'],
                opciones: self::ZONAS,
            ),
            new ClaveConfig(
                clave: 'system.default_language',
                etiqueta: 'Idioma',
                tipo: 'string',
                grupo: self::GRUPO_EMPRESAS,
                porDefecto: 'es',
                descripcion: 'Idioma inicial del canal publico de una empresa nueva.',
                reglas: ['required', 'in:es,en,pt'],
                opciones: self::IDIOMAS,
            ),
            new ClaveConfig(
                clave: 'system.retention_complaints_days',
                etiqueta: 'Denuncias (dias)',
                tipo: 'integer',
                grupo: self::GRUPO_RETENCION,
                porDefecto: 3660,
                descripcion: 'Cuanto se conserva una denuncia antes de borrarse.',
                reglas: ['required', 'integer', 'min:1', 'max:32767'],
                nota: 'El alta por onboarding usa 3660 y el alta manual 365. '
                    . 'La divergencia viene del sistema anterior y sigue pendiente '
                    . 'de definicion comercial: este valor no la unifica por si solo.',
            ),
            new ClaveConfig(
                clave: 'system.retention_files_days',
                etiqueta: 'Archivos adjuntos (dias)',
                tipo: 'integer',
                grupo: self::GRUPO_RETENCION,
                porDefecto: 3660,
                descripcion: 'Cuanto se conservan los adjuntos de una denuncia.',
                reglas: ['required', 'integer', 'min:1', 'max:32767'],
            ),
            new ClaveConfig(
                clave: 'system.retention_logs_days',
                etiqueta: 'Traza de auditoria (dias)',
                tipo: 'integer',
                grupo: self::GRUPO_RETENCION,
                porDefecto: 365,
                descripcion: 'Cuanto se conservan los registros de audit_logs.',
                reglas: ['required', 'integer', 'min:1', 'max:32767'],
                nota: 'Bajarlo no borra nada por si solo, pero define hasta donde '
                    . 'puede purgar el proceso de retencion. La cadena de hashes '
                    . 'no tolera huecos: purgar audit_logs necesita su propio '
                    . 'procedimiento.',
            ),
        ];

        $mapa = [];

        foreach ($claves as $c) {
            $mapa[$c->clave] = $c;
        }

        return $mapa;
    }

    public static function definicion(string $clave): ?ClaveConfig
    {
        return self::definiciones()[$clave] ?? null;
    }

    /** Definiciones agrupadas, en el orden en que se declararon. */
    public static function porGrupo(): array
    {
        $grupos = [];

        foreach (self::definiciones() as $def) {
            $grupos[$def->grupo][] = $def;
        }

        return $grupos;
    }

    /** Reglas para $request->validate(), con los campos ya des-punteados. */
    public static function reglasValidacion(): array
    {
        $reglas = [];

        foreach (self::definiciones() as $def) {
            $campo = 'config.' . $def->campo();

            // Un secreto vacio significa "dejar como estaba", asi que no
            // puede ser required.
            $reglas[$campo] = $def->esSecreta
                ? array_merge(['nullable'], array_diff($def->reglas, ['required']))
                : $def->reglas;
        }

        return $reglas;
    }

    public static function etiquetasValidacion(): array
    {
        $etiquetas = [];

        foreach (self::definiciones() as $def) {
            $etiquetas['config.' . $def->campo()] = mb_strtolower($def->etiqueta);
        }

        return $etiquetas;
    }
}
