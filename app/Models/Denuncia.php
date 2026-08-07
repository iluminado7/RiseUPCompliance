<?php

namespace App\Models;

use App\Enums\EstadoDenuncia;
use App\Enums\PrioridadDenuncia;
use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Denuncia — entidad central del dominio.
 *
 * Dos ejes de estado, no confundirlos:
 *   - status: interno, lo ve el panel (new, seen, in_progress, ...)
 *   - public_status: lo ve el denunciante, en castellano ('Recibida', ...)
 *
 * encryption_salt es la clave del crypto-shredding: de él se derivan las
 * claves de todos los datos personales de esta denuncia. En $hidden para
 * que nunca salga en un toArray() o en un JSON por descuido.
 */
class Denuncia extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'complaints';

    protected $fillable = [
        'company_id',
        'internal_code',
        'is_anonymous',
        'intake_channel',
        'submission_status',
        'category_id',
        'relationship_id',
        'relationship_other_text',
        'branch_id',
        'reported_area_id',
        'reported_area_other',
        'reported_position_id',
        'reported_position_other',
        'incident_date',
        'priority',
        'status',
        'public_status',
    ];

    protected $hidden = [
        'encryption_salt',
        'tracking_code_hash',
        'reported_first_name_enc',
        'reported_last_name_enc',
    ];

    protected function casts(): array
    {
        return [
            'status' => EstadoDenuncia::class,
            'priority' => PrioridadDenuncia::class,
            'is_anonymous' => 'boolean',
            'chat_enabled' => 'boolean',
            'privacy_notice_accepted' => 'boolean',
            'captcha_validated' => 'boolean',
            'incident_date' => 'date',
            'chat_enabled_at' => 'datetime',
            'privacy_notice_accepted_at' => 'datetime',
            'captcha_validated_at' => 'datetime',
            'last_action_at' => 'datetime',
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'archived_at' => 'datetime',
            'purged_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ── Relaciones ──────────────────────────────────────────────

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'category_id');
    }

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(Vinculo::class, 'relationship_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'branch_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'reported_area_id');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'reported_position_id');
    }

    public function asignadoA(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'assigned_to_user_id');
    }

    public function denunciante(): HasOne
    {
        return $this->hasOne(Denunciante::class, 'complaint_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'complaint_id')->orderBy('question_order');
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'complaint_id')->orderBy('created_at');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(Archivo::class, 'complaint_id');
    }

    public function notas(): HasMany
    {
        return $this->hasMany(NotaInterna::class, 'complaint_id');
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstado::class, 'complaint_id')->orderBy('created_at');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'complaint_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoDenuncia::class, 'complaint_id');
    }

    public function exportaciones(): HasMany
    {
        return $this->hasMany(Exportacion::class, 'complaint_id');
    }

    // ── Estado ──────────────────────────────────────────────────

    public function puedePasarA(EstadoDenuncia $destino): bool
    {
        return $this->status->puedePasarA($destino);
    }

    /**
     * Plazo incumplido: 24 horas sin pasar de Nuevo a Vista.
     * Es la métrica de los reportes.
     */
    public function plazoIncumplido(): bool
    {
        return $this->status === EstadoDenuncia::Nuevo
            && $this->created_at->diffInHours(now()) >= 24;
    }

    /**
     * Los datos personales fueron destruidos por la purga de retención:
     * sin salt no hay clave, y nada de esta denuncia se puede descifrar.
     */
    public function estaPurgada(): bool
    {
        return $this->purged_at !== null || $this->encryption_salt === null;
    }

    public function asignacionVigente(): ?Asignacion
    {
        return $this->asignaciones()->whereNull('ended_at')->latest('created_at')->first();
    }
}
