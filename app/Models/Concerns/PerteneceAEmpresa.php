<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Marca un modelo como perteneciente a una empresa (tenant).
 *
 * Hace dos cosas:
 *   - aplica TenantScope a todas las consultas;
 *   - completa company_id al crear, tomándolo del usuario autenticado.
 *
 * Para saltear el scope deliberadamente (comandos de consola, purga por
 * retención, verificador de la cadena de auditoría) usar:
 *
 *     Denuncia::withoutGlobalScope(TenantScope::class)
 *
 * Que sea explícito es la idea: si aparece en un controlador, se ve.
 */
trait PerteneceAEmpresa
{
    public static function bootPerteneceAEmpresa(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($modelo) {
            if ($modelo->company_id !== null) {
                return;
            }

            $usuario = Auth::user();

            if ($usuario && $usuario->company_id !== null) {
                $modelo->company_id = $usuario->company_id;
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }
}
