<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Cargo o puesto de la persona denunciada. Catálogo global. */
class Cargo extends Model
{
    protected $table = 'reported_positions';

    protected $fillable = ['code', 'name_es', 'description_es', 'display_order', 'is_active'];

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
        return $query->where('is_active', true)->orderBy('display_order');
    }

    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'company_positions', 'position_id', 'company_id')
            ->withPivot(['display_order', 'is_active']);
    }
}
