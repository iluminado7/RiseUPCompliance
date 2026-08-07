<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trazabilidad de procesamientos con modelos de lenguaje.
 *
 * input_hash guarda solo el SHA-256 de la entrada, nunca el texto de la
 * denuncia. contains_pii e input_redacted registran si se envió material
 * identificable a un tercero — dato relevante para el cumplimiento.
 */
class ProcesamientoIA extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'ai_processings';

    protected $fillable = [
        'company_id',
        'complaint_id',
        'processing_type',
        'status',
        'model',
        'provider',
        'provider_request_id',
        'processing_region',
        'input_hash',
        'output',
        'input_tokens',
        'output_tokens',
        'cost_usd',
        'contains_pii',
        'input_redacted',
        'prompt_version',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'output' => 'array',
            'contains_pii' => 'boolean',
            'input_redacted' => 'boolean',
            'cost_usd' => 'decimal:6',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }
}
