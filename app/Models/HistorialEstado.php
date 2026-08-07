<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transiciones de estado de una denuncia.
 *
 * from_status y to_status son string y no EstadoDenuncia a propósito: el
 * historial tiene que poder conservar estados que ya no existan en el
 * enum. Castearlos rompería el historial viejo si alguna vez se retira
 * un estado.
 */
class HistorialEstado extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'complaint_status_history';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'complaint_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'reason',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'changed_by_user_id');
    }
}
