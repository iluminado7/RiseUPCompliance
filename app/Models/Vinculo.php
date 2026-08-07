<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Vínculo del denunciante con la empresa: empleado, ex-empleado,
 * cliente, proveedor, otro. Catálogo global.
 */
class Vinculo extends Model
{
    protected $table = 'reporter_relationships';

    protected $fillable = ['code', 'name_es', 'description_es', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'company_relationships', 'relationship_id', 'company_id')
            ->withPivot(['display_order', 'is_active']);
    }
}
