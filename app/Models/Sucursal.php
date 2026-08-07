<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'branches';

    protected $fillable = [
        'company_id',
        'name',
        'internal_code',
        'address',
        'is_headquarter',
        'is_active',
        'default_language',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'is_headquarter' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function denuncias(): HasMany
    {
        return $this->hasMany(Denuncia::class, 'branch_id');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'user_branches', 'branch_id', 'user_id');
    }
}
