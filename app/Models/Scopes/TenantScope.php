<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Scope global de empresa.
 *
 * Reemplaza el `WHERE company_id = ?` repetido en cada query del sistema
 * actual. Se aplica automáticamente a todo modelo que use el trait
 * PerteneceAEmpresa.
 *
 * ── REGLAS ──
 *
 * 1. El company_id sale SIEMPRE del usuario autenticado, NUNCA del request.
 *    Aceptarlo del request es permitir cambiar de tenant a voluntad.
 *    (Convención heredada de Business Partner.)
 *
 * 2. El superadmin atraviesa el scope: ve todas las empresas.
 *
 * 3. Sin usuario autenticado, el scope NO filtra.
 *
 *    Esto último es deliberado y es el punto delicado: el canal público no
 *    tiene sesión, así que acá no hay de dónde sacar la empresa. Los
 *    controladores de Http\Controllers\Publico DEBEN resolver la empresa
 *    desde el slug de la URL y filtrar explícitamente. Si alguno se olvida,
 *    la consulta devuelve datos de todas las empresas y nada lo impide.
 *
 *    Por eso el canal público consulta siempre a partir de la empresa ya
 *    resuelta ($empresa->denuncias()->...) y no del modelo pelado
 *    (Denuncia::where(...)), que es lo que hace visible el olvido.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $usuario = Auth::user();

        if (! $usuario) {
            return;
        }

        if ($usuario->esSuperadmin()) {
            return;
        }

        if ($usuario->company_id === null) {
            return;
        }

        $builder->where(
            $model->qualifyColumn('company_id'),
            $usuario->company_id
        );
    }
}
