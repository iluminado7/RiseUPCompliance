<?php

namespace App\Enums;

enum PrioridadDenuncia: string
{
    case Baja = 'low';
    case Media = 'medium';
    case Alta = 'high';
    case Critica = 'critical';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Baja => 'Baja',
            self::Media => 'Media',
            self::Alta => 'Alta',
            self::Critica => 'Crítica',
        };
    }
}
