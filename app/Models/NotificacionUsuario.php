<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Aviso hacia un usuario del panel. */
class NotificacionUsuario extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'user_notifications';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'complaint_id',
        'type',
        'status',
        'metadata',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function scopeNoLeidas(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }
}
