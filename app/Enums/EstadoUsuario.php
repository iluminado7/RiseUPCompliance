<?php

namespace App\Enums;

enum EstadoUsuario: string
{
    case Activo = 'active';
    case Inactivo = 'inactive';
    case Suspendido = 'suspended';

    /**
     * Solo los activos pueden iniciar sesión Y mantenerla.
     *
     * En el sistema actual esto se chequeaba únicamente en el login: una
     * sesión ya abierta sobrevivía a la suspensión del usuario. Con el
     * guard `web` el usuario se recarga de la base en cada request, así
     * que el middleware puede cortar la sesión en el acto.
     */
    public function puedeOperar(): bool
    {
        return $this === self::Activo;
    }
}
