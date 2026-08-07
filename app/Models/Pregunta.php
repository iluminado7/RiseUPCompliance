<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pregunta del cuestionario de una categoría.
 *
 * Dos niveles: company_id NULL es el catálogo maestro (sirve a todas las
 * empresas); company_id poblado es una pregunta propia de esa empresa.
 *
 * question_text_es es INMUTABLE: para cambiar el enunciado se crea una
 * versión nueva, no se hace UPDATE. valid_from / valid_to delimitan la
 * vigencia.
 *
 * NO usa PerteneceAEmpresa: el scope filtraría por company_id y
 * escondería el catálogo maestro, que es justo lo que toda empresa
 * necesita ver. El filtrado correcto es "las mías O las del maestro",
 * que es lo que hace scopeParaEmpresa().
 */
class Pregunta extends Model
{
    protected $table = 'category_questions';

    protected $fillable = [
        'category_id',
        'company_id',
        'catalog_question_id',
        'version',
        'question_order',
        'question_text_es',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** Preguntas propias de la empresa más las del catálogo maestro. */
    public function scopeParaEmpresa(Builder $query, int $empresaId): Builder
    {
        return $query->where(function (Builder $q) use ($empresaId) {
            $q->where('company_id', $empresaId)->orWhereNull('company_id');
        });
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNull('valid_to')
            ->orderBy('question_order');
    }

    public function esDelCatalogoMaestro(): bool
    {
        return $this->company_id === null;
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'category_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'company_id');
    }

    public function preguntaBase(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'catalog_question_id');
    }
}
