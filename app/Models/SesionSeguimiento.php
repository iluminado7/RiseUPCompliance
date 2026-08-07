<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cada consulta al canal público de seguimiento.
 *
 * Es la tabla más delicada del sistema para el anonimato: relaciona una
 * IP con una denuncia. ip_hash es HMAC con IP_HASH_KEY, que vive solo en
 * el entorno — el SHA-256 sin clave del sistema original era reversible
 * por fuerza bruta sobre el espacio IPv4 completo.
 *
 * NO usa PerteneceAEmpresa: se escribe desde el canal público, sin
 * sesión, y company_id puede ser null cuando el código no corresponde a
 * ninguna empresa. Los intentos fallidos se registran igual: son la
 * señal de que alguien está probando códigos.
 */
class SesionSeguimiento extends Model
{
    protected $table = 'tracking_sessions';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'complaint_id',
        'tracking_code_hash',
        'ip_hash',
        'ip_enc',
        'user_agent_hash',
        'user_agent_enc',
        'result',
    ];

    protected $hidden = [
        'tracking_code_hash',
        'ip_hash',
        'ip_enc',
        'user_agent_hash',
        'user_agent_enc',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }
}
