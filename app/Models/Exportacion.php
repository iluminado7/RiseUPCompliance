<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PDF o paquete de evidencia generado a partir de una denuncia.
 *
 * file_hash permite demostrar que un export presentado como prueba es
 * exactamente el que el sistema produjo.
 */
class Exportacion extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'complaint_exports';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'complaint_id',
        'generated_by_user_id',
        'export_type',
        'query_params_json',
        'storage_path',
        'file_hash',
        'file_size_bytes',
    ];

    protected $hidden = ['storage_path'];

    protected function casts(): array
    {
        return [
            'query_params_json' => 'array',
            'file_size_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }

    public function generadaPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'generated_by_user_id');
    }
}
