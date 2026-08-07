<?php

namespace App\Enums;

enum EstadoEmpresa: string
{
    case Activa = 'active';
    case Suspendida = 'suspended';
    case Desactivada = 'deactivated';

    /**
     * Los usuarios pueden entrar al panel.
     *
     * Comportamiento portado de auth.php: una empresa `suspended` NO
     * bloquea el panel — sus usuarios siguen operando los casos abiertos.
     * Solo `deactivated` cierra el acceso.
     */
    public function permiteAccesoAlPanel(): bool
    {
        return $this !== self::Desactivada;
    }

    /** El canal público acepta denuncias nuevas. */
    public function canalPublicoAbierto(): bool
    {
        return $this === self::Activa;
    }
}
