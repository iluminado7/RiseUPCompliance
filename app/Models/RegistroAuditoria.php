<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de la traza de auditoría encadenada.
 *
 * APPEND-ONLY. Nunca UPDATE ni DELETE. Los métodos de escritura y borrado
 * están deshabilitados a propósito: el único camino para insertar es
 * AuditLogService, que calcula el hash y toma el lock del sequence.
 *
 * chain_scope es columna generada: 0 = cadena de plataforma (eventos sin
 * empresa: login fallido, actividad del superadmin), >0 = cadena de esa
 * empresa. El verificador recorre N+1 cadenas.
 *
 * NO usa PerteneceAEmpresa: la lectura de logs se decide por Policy, y
 * un scope automático escondería la cadena de plataforma.
 */
class RegistroAuditoria extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = [
        'ip_hash',
        'ip_enc',
        'user_agent_hash',
        'user_agent_enc',
        'detail_enc',
        'session_id',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** Cadena de eventos de plataforma (sin empresa). */
    public function scopeCadenaDePlataforma(Builder $query): Builder
    {
        return $query->where('chain_scope', 0)->orderBy('company_sequence');
    }

    public function scopeCadenaDeEmpresa(Builder $query, int $empresaId): Builder
    {
        return $query->where('chain_scope', $empresaId)->orderBy('company_sequence');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    // ── Append-only, garantizado en código ──────────────────────

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('audit_logs es append-only: no se puede modificar un registro.');
    }

    public function delete(): bool
    {
        throw new \LogicException('audit_logs es append-only: no se puede borrar un registro.');
    }
    public function denunciaAfectada(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'affected_complaint_id');
    }
}
