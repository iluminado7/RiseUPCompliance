<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Empresa;
use App\Models\Usuario;

/**
 * Gestion de empresas. Solo el superadmin las crea, edita y da de baja.
 *
 * El admin_principal ve la suya en la seccion "Sucursales y Usuarios" pero
 * no puede modificar sus datos comerciales ni su estado.
 */
class EmpresaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin);
    }

    public function view(Usuario $usuario, Empresa $empresa): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin)
            || $usuario->company_id === $empresa->id;
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin);
    }

    public function update(Usuario $usuario, Empresa $empresa): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin);
    }

    /**
     * Cambiar el estado operativo.
     *
     * Desactivar una empresa deja a todos sus usuarios afuera del panel en
     * el siguiente request (lo aplica el middleware EmpresaOperativa) y
     * cierra su canal publico. Es la accion mas destructiva de esta
     * pantalla sin ser un borrado.
     */
    public function cambiarEstado(Usuario $usuario, Empresa $empresa): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin);
    }
}
