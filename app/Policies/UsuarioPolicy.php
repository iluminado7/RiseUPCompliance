<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Usuario;

/**
 * Autorizacion sobre usuarios del panel.
 *
 * Usuario NO usa TenantScope -- aplicarlo romperia el login del
 * superadmin, que no tiene company_id -- asi que el aislamiento entre
 * empresas se decide ACA y no en el scope. Es la excepcion que hay que
 * tener presente.
 *
 * -- LA ESCALADA QUE ESTO CIERRA --
 *
 * administracion.php validaba en crear_usuario que un admin_principal solo
 * pudiera crear gestores, pero editar_usuario no tenia esa validacion ni
 * ninguna que limitara a que usuarios podia editar: solo chequeaba que el
 * objetivo no fuera otro superadmin.
 *
 * Un admin_principal podia entonces mandar un POST con su propio
 * usuario_id y role_id = superadmin y promoverse solo, o editar usuarios
 * de otras empresas pasando cualquier id.
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

        if ($usuario->tieneRol(RolUsuario::Superadmin)) {
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
        // Nadie se edita a si mismo desde acá: cambiarse el propio rol o
        // el propio estado es escalada de privilegios o autobloqueo. El
        // perfil propio se edita en su pantalla, sin esos campos.
        //
        // Reemplaza a la proteccion hardcodeada `$uid === 7` del original,
        // que ademas dejaba de significar algo apenas cambiaban los ids.
        if ($usuario->id === $destino->id) {
            return false;
        }

        // Un superadmin no puede tocar a otro superadmin.
        if ($destino->tieneRol(RolUsuario::Superadmin)) {
            return false;
        }

        if ($usuario->tieneRol(RolUsuario::Superadmin)) {
            return true;
        }

        if (! $usuario->tieneRol(RolUsuario::AdminPrincipal)) {
            return false;
        }

        // El admin_principal solo dentro de su empresa y solo sobre roles
        // de menor jerarquia que el suyo.
        return $this->mismaEmpresa($usuario, $destino)
            && ($destino->nombreRol()?->nivel() ?? 0) > ($usuario->nombreRol()?->nivel() ?? 0);
    }

    public function delete(Usuario $usuario, Usuario $destino): bool
    {
        return $this->update($usuario, $destino);
    }

    private function mismaEmpresa(Usuario $usuario, Usuario $destino): bool
    {
        return $usuario->company_id !== null
            && $usuario->company_id === $destino->company_id;
    }
}
