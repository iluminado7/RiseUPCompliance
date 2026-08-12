<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Factura;
use App\Models\Usuario;

/**
 * Facturas.
 *
 * El admin_principal ve las de su empresa pero no las modifica: la
 * facturacion la administra GoHarv, no el cliente. Que el cliente pueda
 * marcar como pagada su propia factura seria darle control sobre la
 * cuenta corriente.
 */
class FacturaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin, RolUsuario::AdminPrincipal);
    }

    public function view(Usuario $usuario, Factura $factura): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin)
            || $usuario->company_id === $factura->company_id;
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin);
    }

    public function update(Usuario $usuario, Factura $factura): bool
    {
        return $usuario->tieneRol(RolUsuario::Superadmin);
    }
}
