<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * complaint_reporters — datos personales del denunciante (1:1 con la denuncia).
 *
 * Todo el contenido está cifrado con claves derivadas de
 * complaints.encryption_salt. Para purgar NO se borran estas filas:
 * se destruye el salt en complaints, y estos datos quedan irrecuperables.
 *
 * complaint_answers — respuestas al cuestionario de la categoría.
 *
 * H-003 de la auditoría: la columna answer_encrypted guardaba texto plano
 * pese al nombre. Acá se mantiene el nombre (§2.4, strings inmutables) pero
 * el contenido se cifra de verdad, con la misma derivación por denuncia.
 *
 * question_text es una copia inmutable del enunciado al momento del envío:
 * si el catálogo cambia después, la denuncia conserva lo que la persona
 * efectivamente respondió.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_reporters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('complaint_id');

            $table->text('first_name_enc')->nullable()->comment('CIFRADO');
            $table->text('last_name_enc')->nullable()->comment('CIFRADO');
            $table->text('gender_enc')->nullable()->comment('CIFRADO');
            $table->text('email_enc')->nullable()->comment('CIFRADO');
            $table->text('national_id_enc')->nullable()->comment('CIFRADO');
            $table->text('phone_enc')->nullable()->comment('CIFRADO');

            $table->string('encryption_key_id', 100)
                ->comment('ID de la clave maestra usada (ej: key-v1). El salt vive en complaints');
            $table->smallInteger('encryption_version')->default(1);
            $table->dateTime('created_at')->useCurrent();

            $table->unique('complaint_id', 'uq_complaint_reporters_complaint');
            $table->unique(['id', 'company_id'], 'uq_complaint_reporters_id_company');
            $table->index(['company_id', 'complaint_id'], 'idx_complaint_reporters_company_complaint');

            $table->foreign('company_id', 'fk_complaint_reporters_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_complaint_reporters_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('complaint_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('category_question_id')->nullable()
                ->comment('Null si la pregunta se eliminó del catálogo');
            $table->smallInteger('question_order');
            $table->text('question_text')->comment('Copia inmutable del enunciado al momento del envío');
            $table->text('answer_encrypted')->nullable()->comment('CIFRADO — crypto-shredding vía complaints.encryption_salt');
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['complaint_id', 'question_order'], 'uq_complaint_answers_order');
            $table->index('complaint_id', 'idx_complaint_answers_complaint');
            $table->index(['company_id', 'complaint_id'], 'idx_complaint_answers_company_complaint');
            $table->index('category_question_id', 'idx_complaint_answers_question');

            $table->foreign('company_id', 'fk_complaint_answers_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_complaint_answers_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_answers');
        Schema::dropIfExists('complaint_reporters');
    }
};
