<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Versión de un documento legal: aviso de privacidad, términos, política.
 *
 * company_id NULL = documento global. Cada denuncia guarda qué versión
 * exacta aceptó el denunciante (complaints.privacy_notice_version_id):
 * es lo que permite demostrar después qué texto se le mostró.
 *
 * content_hash verifica que el documento no cambió desde su publicación.
 */
class DocumentoLegal extends Model
{
    protected $table = 'legal_document_versions';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'document_type',
        'version',
        'content_hash',
        'published_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }
}
