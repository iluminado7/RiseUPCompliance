<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mensaje del chat entre denunciante e investigador.
 *
 * content va cifrado con clave derivada de Denuncia::encryption_salt.
 * En el sistema actual el encryption_key_id era el literal
 * 'key-dev-placeholder' y el contenido iba en claro.
 *
 * El analista puede retirar un mensaje solo si el denunciante todavía no
 * lo leyó. original_content guarda la copia de auditoría, también
 * cifrada, y nunca se muestra al denunciante.
 */
class Mensaje extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'complaint_messages';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'complaint_id',
        'sender_type',
        'sender_user_id',
        'content',
        'encryption_key_id',
        'is_read_by_reporter',
    ];

    protected $hidden = [
        'content',
        'original_content',
        'encryption_key_id',
    ];

    protected function casts(): array
    {
        return [
            'is_read_by_reporter' => 'boolean',
            'is_deleted_by_sender' => 'boolean',
            'deleted_by_sender_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** Mensajes que el denunciante debe poder ver. */
    public function scopeVisiblesParaDenunciante(Builder $query): Builder
    {
        return $query->where('is_deleted_by_sender', false);
    }

    public function puedeRetirarse(): bool
    {
        return ! $this->is_read_by_reporter && ! $this->is_deleted_by_sender;
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'sender_user_id');
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(LecturaMensaje::class, 'message_id');
    }
}
