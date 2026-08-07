<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asignación de una denuncia a un investigador.
 *
 * ended_at NULL = asignación vigente. El historial completo queda acá.
 *
 * H-010: esta tabla convive con Denuncia::assigned_to_user_id y las dos
 * pueden desincronizarse. La fuente de verdad debería ser esta; la
 * columna en complaints es denormalización para los índices de listado y
 * hay que escribirla SIEMPRE en la misma transacción. Se resuelve al
 * escribir el flujo de asignación (Etapa 5).
 */
class Asignacion extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'complaint_assignments';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'complaint_id',
        'assigned_to_user_id',
        'assigned_by_user_id',
        'reason',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }

    public function asignadoA(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'assigned_to_user_id');
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'assigned_by_user_id');
    }
}
