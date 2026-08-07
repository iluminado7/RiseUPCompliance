<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Respuesta al cuestionario de la categoría.
 *
 * question_text es una copia inmutable del enunciado al momento del
 * envío: si el catálogo cambia después, la denuncia sigue mostrando la
 * pregunta tal como se le formuló a esa persona.
 *
 * H-003: answer_encrypted guardaba texto plano pese al nombre. El nombre
 * de la columna se conserva (§2.4) pero el contenido va cifrado de verdad.
 */
class Respuesta extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'complaint_answers';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'complaint_id',
        'category_question_id',
        'question_order',
        'question_text',
        'answer_encrypted',
    ];

    protected $hidden = ['answer_encrypted'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function denuncia(): BelongsTo
    {
        return $this->belongsTo(Denuncia::class, 'complaint_id');
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'category_question_id');
    }
}
