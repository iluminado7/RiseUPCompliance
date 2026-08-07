<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Manager de plataforma — tabla tenant_managers.
 *
 * Es la entidad del lado de Go Harvey, no de la empresa cliente: cada
 * empresa tiene un manager asignado (companies.manager_id), y los
 * usuarios de plataforma se vinculan por users.manager_id.
 *
 * Ojo con la trampa del schema: companies.manager_id apunta ACÁ, no a
 * users. Es el error que ya costó una sesión de depuración durante el
 * onboarding.
 */
class ManagerPlataforma extends Model
{
    protected $table = 'tenant_managers';

    protected $fillable = [
        'full_name',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class, 'manager_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'manager_id');
    }
}
