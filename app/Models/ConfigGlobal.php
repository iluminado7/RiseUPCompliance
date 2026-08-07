<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración global del sistema, en pares clave/valor.
 * Solo los managers de plataforma pueden modificarla.
 *
 * Cuando is_secret = 1 el valor va en value_enc, cifrado.
 */
class ConfigGlobal extends Model
{
    protected $table = 'global_config';

    public const CREATED_AT = null;

    protected $fillable = [
        'key',
        'value',
        'value_enc',
        'value_type',
        'is_secret',
        'version',
        'description',
        'updated_by_manager_id',
    ];

    protected $hidden = ['value_enc'];

    protected function casts(): array
    {
        return [
            'is_secret' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(ManagerPlataforma::class, 'updated_by_manager_id');
    }
}
