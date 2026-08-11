<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Sucursal;
use App\Models\Usuario;

class SucursalPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin, RolUsuario::AdminPrincipal);
    }

    public function create(Usuario $usuario): bool
    {
        return $this->viewAny($usuario);
    }

    public function update(Usuario $usuario, Sucursal $sucursal): bool
    {
        if ($usuario->tieneRol(RolUsuario::Superadmin)) {
            return true;
        }

        return $usuario->tieneRol(RolUsuario::AdminPrincipal)
            && $usuario->company_id === $sucursal->company_id;
    }
}
