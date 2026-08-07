<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Datos personales del denunciante (1:1 con la denuncia).
 *
 * Existe solo cuando la denuncia NO es anónima. Todo el contenido está
 * cifrado con claves derivadas de Denuncia::encryption_salt.
 *
 * Para purgar NO se borra esta fila: se destruye el salt en complaints.
 * Los datos quedan cifrados sin clave posible y la traza no muestra huecos.
 *
 * Todas las columnas cifradas están en $hidden: nunca deben salir en un
 * toArray() ni en la respuesta de un endpoint por descuido.
 */
class Denunciante extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'complaint_reporters';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'complaint_id',
        'first_name_enc',
        'last_name_enc',
        'gender_enc',
        'email_enc',
        'national_id_enc',
        'phone_enc',
        'encryption_key_id',
        'encryption_version',
    ];

    protected $hidden = [
        'first_name_enc',
        'last_name_enc',
        'gender_enc',
        'email_enc',
        'national_id_enc',
        'phone_enc',
        'encryption_key_id',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }
}
