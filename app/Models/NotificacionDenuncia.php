<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aviso hacia el DENUNCIANTE (email o código de seguimiento).
 * recipient_enc va cifrado: en una denuncia no anónima, el email
 * identifica a la persona.
 */
class NotificacionDenuncia extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'complaint_notifications';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'complaint_id',
        'type',
        'channel',
        'recipient_enc',
        'status',
        'sent_at',
    ];

    protected $hidden = ['recipient_enc'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }
}
