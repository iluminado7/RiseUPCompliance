<?php

namespace App\Enums;

/**
 * Estados internos de una denuncia.
 *
 * Los valores son los mismos strings que persiste el sistema actual (§2.4).
 *
 * ── SOBRE LAS TRANSICIONES ──
 *
 * Esto NO es un port: es una definición nueva. El cambiar_estado.php
 * original no tenía máquina de estados — cualquier estado de una lista
 * blanca saltaba a cualquier otro sin validación. Y además escribía sobre
 * public_status en vez de status, con una lista que incluía 'rejected'
 * (inexistente) y omitía 'seen' y 'archived'.
 *
 * El estado 'under_review' del schema original quedó sin uso: se decidió
 * que En progreso cubre esa etapa.
 */
enum EstadoDenuncia: string
{
    case Nuevo = 'new';
    case Vista = 'seen';
    case EnProgreso = 'in_progress';
    case Resuelta = 'resolved';
    case Cerrada = 'closed';
    case Archivada = 'archived';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Nuevo => 'Nueva',
            self::Vista => 'Vista',
            self::EnProgreso => 'En progreso',
            self::Resuelta => 'Resuelta',
            self::Cerrada => 'Cerrada',
            self::Archivada => 'Archivada',
        };
    }

    /**
     * Estado que ve el denunciante en el seguimiento público.
     *
     * Nuevo y Vista comparten etiqueta a propósito: que el denunciante
     * sepa el momento exacto en que alguien abrió su caso no le aporta
     * nada y expone el ritmo de trabajo interno.
     */
    public function estadoPublico(): string
    {
        return match ($this) {
            self::Nuevo => 'Nuevo', 
            self::Vista => 'Vista',
            self::EnProgreso => 'En progreso',
            self::Resuelta => 'Resuelta',
            self::Cerrada, self::Archivada => 'Cerrada',
        };
    }

    /**
     * Transiciones válidas desde este estado.
     *
     * Una denuncia resuelta o cerrada puede reabrirse a En progreso: si
     * aparece información nueva, la investigación continúa sobre el mismo
     * caso en vez de abrir uno duplicado.
     *
     * Nunca se vuelve a Nuevo: ese estado significa "todavía nadie la
     * miró", y eso no se puede deshacer.
     */
    public function transicionesValidas(): array
    {
        return match ($this) {
            self::Nuevo => [self::Vista],
            self::Vista => [self::EnProgreso, self::Resuelta],
            self::EnProgreso => [self::Resuelta, self::Cerrada],
            self::Resuelta => [self::EnProgreso, self::Cerrada],
            self::Cerrada => [self::EnProgreso, self::Archivada],
            self::Archivada => [self::EnProgreso]
        };
    }

    public function puedePasarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesValidas(), true);
    }

    public function esTerminal(): bool
    {
        return $this->transicionesValidas() === [];
    }

    /** Reapertura: vuelve a una etapa activa desde una de cierre. */
    public function esReapertura(self $destino): bool
    {
        return $destino === self::EnProgreso
            && in_array($this, [self::Resuelta, self::Cerrada], true);
    }

    /**
     * Estados que cuentan como "ya vista" para el plazo incumplido
     * (24 h sin pasar de Nuevo a Vista).
     */
    public function fueVista(): bool
    {
        return $this !== self::Nuevo;
    }

    /** Estados en los que el caso ya no está en curso. */
    public function estaFinalizada(): bool
    {
        return in_array($this, [self::Resuelta, self::Cerrada, self::Archivada], true);
    }
}