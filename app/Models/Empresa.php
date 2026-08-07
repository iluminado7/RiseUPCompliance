<?php

namespace App\Models;

use App\Enums\EstadoEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Empresa cliente (tenant).
 *
 * No usa PerteneceAEmpresa: es la empresa misma, no algo que pertenece a
 * una. El filtrado de qué empresas ve cada rol va en la Policy.
 *
 * El slug es la clave del canal público: /{slug} es el formulario de
 * denuncia de esta empresa.
 */
class Empresa extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'manager_id',
        'name',
        'email',
        'slug',
        'status',
        'public_configuration',
        'default_language',
        'timezone',
        'complaints_retention_days',
        'logs_retention_days',
        'files_retention_days',
    ];

    protected $hidden = [
        'sensitive_configuration_enc',
    ];

    protected function casts(): array
    {
        return [
            'status' => EstadoEmpresa::class,
            'public_configuration' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ── Relaciones ──────────────────────────────────────────────

    public function managerPlataforma(): BelongsTo
    {
        return $this->belongsTo(ManagerPlataforma::class, 'manager_id');
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class, 'company_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'company_id');
    }

    public function denuncias(): HasMany
    {
        return $this->hasMany(Denuncia::class, 'company_id');
    }

    public function datosFiscales(): HasOne
    {
        return $this->hasOne(DatosFiscales::class, 'company_id');
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'company_categories', 'company_id', 'category_id')
            ->withPivot(['display_order', 'is_active']);
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'company_areas', 'company_id', 'area_id')
            ->withPivot(['display_order', 'is_active']);
    }

    public function cargos(): BelongsToMany
    {
        return $this->belongsToMany(Cargo::class, 'company_positions', 'company_id', 'position_id')
            ->withPivot(['display_order', 'is_active']);
    }

    public function vinculos(): BelongsToMany
    {
        return $this->belongsToMany(Vinculo::class, 'company_relationships', 'company_id', 'relationship_id')
            ->withPivot(['display_order', 'is_active']);
    }

    // ── Estado ──────────────────────────────────────────────────

    public function permiteAccesoAlPanel(): bool
    {
        return $this->status->permiteAccesoAlPanel();
    }

    public function canalPublicoAbierto(): bool
    {
        return $this->status->canalPublicoAbierto();
    }
}
