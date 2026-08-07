<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Usuario;

/**
 * Autorización sobre usuarios del panel.
 *
 * Usuario NO usa TenantScope (aplicarlo rompería el login del superadmin,
 * que no tiene company_id), así que el aislamiento entre empresas se
 * decide acá y no en el scope. Es la excepción que hay que tener presente.
 */
class UsuarioPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin, RolUsuario::AdminPrincipal);
    }

    public function view(Usuario $usuario, Usuario $destino): bool
    {
        if ($usuario->id === $destino->id) {
            return true;
        }

        if ($usuario->esSuperadmin()) {
            return true;
        }

        return $usuario->tieneRol(RolUsuario::AdminPrincipal)
            && $this->mismaEmpresa($usuario, $destino);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin, RolUsuario::AdminPrincipal);
    }

    public function update(Usuario $usuario, Usuario $destino): bool
    {
        if ($usuario->esSuperadmin()) {
            return true;
        }

        if (! $usuario->tieneRol(RolUsuario::AdminPrincipal)) {
            return $usuario->id === $destino->id;
        }

        if (! $this->mismaEmpresa($usuario, $destino)) {
            return false;
        }

        // Un admin_principal no puede tocar a alguien de rango igual o
        // mayor que el suyo: si no, dos admins de la misma empresa pueden
        // desactivarse mutuamente, o degradarse entre sí.
        return $destino->nombreRol()?->nivel() > $usuario->nombreRol()?->nivel()
            || $usuario->id === $destino->id;
    }

    public function delete(Usuario $usuario, Usuario $destino): bool
    {
        // Nadie se elimina a sí mismo: deja la empresa sin administrador.
        if ($usuario->id === $destino->id) {
            return false;
        }

        return $this->update($usuario, $destino);
    }

    private function mismaEmpresa(Usuario $usuario, Usuario $destino): bool
    {
        return $usuario->company_id !== null
            && $usuario->company_id === $destino->company_id;
    }
}
