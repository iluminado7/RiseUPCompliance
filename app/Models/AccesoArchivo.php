<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de cada acceso a un archivo adjunto.
 *
 * Quién vio o descargó qué evidencia y cuándo. Append-only en la
 * práctica: no hay razón legítima para editar ni borrar estas filas.
 */
class AccesoArchivo extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'file_access_logs';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'file_id',
        'complaint_id',
        'user_id',
        'action',
        'ip_hash',
        'ip_enc',
    ];

    protected $hidden = ['ip_hash', 'ip_enc'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'file_id');
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
