<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * category_questions — cuestionario por categoría de denuncia.
 *
 * Dos niveles: company_id NULL es el catálogo maestro (preguntas base
 * que sirven a todas las empresas); company_id poblado es una pregunta
 * propia de esa empresa.
 *
 * question_text_es es INMUTABLE: para cambiar el enunciado se crea una
 * versión nueva en vez de un UPDATE, y valid_from/valid_to delimitan la
 * vigencia. Así una denuncia vieja sigue mostrando la pregunta tal como
 * se le formuló a esa persona.
 *
 * CAMBIO: catalog_question_id era `int` mientras referenciaba un `bigint`,
 * y por eso nunca pudo tener FK. Se corrige el tipo y se agrega la FK
 * autorreferencial que el comentario del schema original describía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('company_id')->nullable()
                ->comment('NULL = catálogo maestro; poblado = pregunta exclusiva de la empresa');
            $table->unsignedBigInteger('catalog_question_id')->nullable()
                ->comment('Pregunta del catálogo base de la que deriva. NULL = personalizada');
            $table->smallInteger('version')->default(1);
            $table->smallInteger('question_order');
            $table->text('question_text_es')
                ->comment('INMUTABLE — para cambiarlo se crea una versión nueva, no se hace UPDATE');
            $table->dateTime('valid_from')->useCurrent();
            $table->dateTime('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['category_id', 'company_id', 'question_order', 'version'], 'uq_category_questions_scope');
            $table->index(['category_id', 'company_id', 'is_active', 'question_order'], 'idx_cq_active_order');
            $table->index('company_id', 'idx_cq_company');
            $table->index('catalog_question_id', 'idx_cq_catalog_question');

            $table->foreign('category_id', 'fk_category_questions_category')
                ->references('id')->on('complaint_categories')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('company_id', 'fk_category_questions_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('catalog_question_id', 'fk_category_questions_catalog')
                ->references('id')->on('category_questions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_questions');
    }
};
