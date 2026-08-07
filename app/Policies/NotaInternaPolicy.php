<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\NotaInterna;
use App\Models\Usuario;

/**
 * Edicion y borrado de notas internas.
 *
 * Portado de ver_denuncia.php: puede editar el autor, el superadmin o el
 * admin_principal.
 *
 * Hay una inconsistencia heredada que conviene tener presente: el
 * admin_principal figura como editor autorizado pero NO puede ver las
 * notas (verNotas solo habilita superadmin e investigador_externo). O sea
 * que en la practica nunca llega al formulario de edicion. Se porta tal
 * cual; corregirlo es decidir cual de las dos reglas esta mal, y eso es
 * un cambio funcional.
 */
class NotaInternaPolicy
{
    public function update(Usuario $usuario, NotaInterna $nota): bool
    {
        if ($nota->deleted_at !== null) {
            return false;
        }

        return $nota->user_id === $usuario->id
            || $usuario->tieneRol(RolUsuario::Superadmin, RolUsuario::AdminPrincipal);
    }

    public function delete(Usuario $usuario, NotaInterna $nota): bool
    {
        return $this->update($usuario, $nota);
    }
}
