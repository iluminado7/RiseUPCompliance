<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eventos de la denuncia — la línea de tiempo que ve el investigador.
 *
 * No confundir con audit_logs: eso es la traza legal encadenada, esto es
 * la actividad del caso. payload NUNCA debe contener datos personales,
 * solo IDs.
 */
class EventoDenuncia extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'complaint_events';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'complaint_id',
        'user_id',
        'event_type',
        'payload',
        'request_id',
        'source_ip_hash',
        'source_ip_enc',
    ];

    protected $hidden = ['source_ip_hash', 'source_ip_enc'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}
