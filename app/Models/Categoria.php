<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría de irregularidad. Catálogo global: cada empresa elige qué
 * subconjunto ofrece en su canal, vía la pivote company_categories.
 */
class Categoria extends Model
{
    protected $table = 'complaint_categories';

    protected $fillable = ['code', 'name_es', 'description_es', 'is_active'];

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
        return $query->where('is_active', true);
    }

    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'company_categories', 'category_id', 'company_id')
            ->withPivot(['display_order', 'is_active']);
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'category_id');
    }

    public function denuncias(): HasMany
    {
        return $this->hasMany(Denuncia::class, 'category_id');
    }
}
