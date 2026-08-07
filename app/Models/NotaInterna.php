<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota interna del investigador sobre el caso.
 * Nunca visible para el denunciante.
 *
 * PENDIENTE: content va en texto plano, portado tal cual del original.
 * La auditoría no lo marcó (H-003 cubría solo las respuestas), pero estas
 * notas suelen tener más detalle sensible que la denuncia misma:
 * hipótesis, nombres de testigos, resultados parciales. Cifrarlas con el
 * mismo salt por denuncia es barato y conviene antes de tener datos reales.
 */
class NotaInterna extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'internal_notes';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'complaint_id',
        'user_id',
        'content',
        'is_priority',
    ];

    protected function casts(): array
    {
        return [
            'is_priority' => 'boolean',
            'created_at' => 'datetime',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    public function fueEditada(): bool
    {
        return $this->edited_at !== null;
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function editadaPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'edited_by_user_id');
    }

    public function eliminadaPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'deleted_by_user_id');
    }
}
