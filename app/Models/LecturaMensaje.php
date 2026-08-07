<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marca de lectura de un mensaje por parte de un usuario del panel.
 *
 * No lleva company_id: se llega siempre a través del mensaje, que sí lo
 * tiene. Por eso no usa PerteneceAEmpresa.
 */
class LecturaMensaje extends Model
{
    protected $table = 'complaint_message_reads';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['message_id', 'user_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class, 'message_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }
}
