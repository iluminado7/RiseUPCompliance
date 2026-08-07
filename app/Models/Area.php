<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Área o sector denunciado. Catálogo global. */
class Area extends Model
{
    protected $table = 'reported_areas';

    protected $fillable = ['code', 'name_es', 'description_es', 'display_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }

    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'company_areas', 'area_id', 'company_id')
            ->withPivot(['display_order', 'is_active']);
    }
}
