<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Adjunto de una denuncia.
 *
 * clean_metadata guarda el resultado del strip de metadatos: es la
 * evidencia de que el EXIF stripping corrió sobre este archivo (H-004).
 *
 * declared_mime viene del cliente y puede estar falseado. Las decisiones
 * se toman SIEMPRE con detected_mime, que se calcula de los bytes.
 */
class Archivo extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'files';

    protected $fillable = [
        'company_id',
        'complaint_id',
        'original_name',
        'storage_name',
        'storage_bucket',
        'storage_path',
        'sha256_hash',
        'size_bytes',
        'declared_mime',
        'detected_mime',
        'detected_extension',
        'file_status',
        'clean_metadata',
        'available_for_analyst',
        'uploaded_by_type',
        'uploaded_by_user_id',
    ];

    protected $hidden = ['storage_path', 'quarantine_path', 'kms_key_id'];

    protected function casts(): array
    {
        return [
            'clean_metadata' => 'array',
            'encrypted_at_rest' => 'boolean',
            'available_for_analyst' => 'boolean',
            'size_bytes' => 'integer',
            'scan_date' => 'datetime',
            'deleted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** Disponibles para el investigador: escaneados, limpios y habilitados. */
    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('file_status', 'available')
            ->where('scan_result', 'clean')
            ->whereNull('deleted_at');
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'uploaded_by_user_id');
    }

    public function accesos(): HasMany
    {
        return $this->hasMany(AccesoArchivo::class, 'file_id');
    }
}
