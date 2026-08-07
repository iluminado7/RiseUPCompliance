<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rol del panel. Catálogo global, no pertenece a ninguna empresa.
 *
 * permissions_json se conserva por compatibilidad con el sistema actual,
 * pero las decisiones de autorización van en las Policies, no leyendo
 * este JSON: un permiso disperso en datos es más difícil de auditar que
 * uno expresado en código y cubierto por tests.
 */
class Rol extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'description',
        'hierarchy_level',
        'permissions_json',
    ];

    protected function casts(): array
    {
        return [
            'permissions_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'role_id');
    }
}
