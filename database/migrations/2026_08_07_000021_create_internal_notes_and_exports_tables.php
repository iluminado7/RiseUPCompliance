<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * internal_notes — notas de los investigadores sobre el caso.
 * Nunca visibles para el denunciante.
 *
 * PENDIENTE (Etapa 5): `content` se porta en texto plano, tal como el
 * original. La auditoría no lo marcó — H-003 cubría solo las respuestas
 * del cuestionario — pero estas notas suelen contener más detalle
 * sensible que la denuncia misma: hipótesis, nombres de testigos,
 * resultados parciales. Cifrarlas con el mismo salt por denuncia es
 * barato y conviene hacerlo antes de que haya datos reales.
 *
 * complaint_exports — registro de PDFs y paquetes de evidencia generados.
 * file_hash permite demostrar que un export presentado como prueba es
 * exactamente el que el sistema produjo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('user_id');
            $table->text('content');
            $table->text('original_content')->nullable()->comment('Copia previa a la edición');
            $table->boolean('is_priority')->default(false);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('edited_at')->nullable();
            $table->unsignedBigInteger('edited_by_user_id')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();

            $table->index(['complaint_id', 'created_at'], 'idx_in_complaint_created');
            $table->index(['company_id', 'complaint_id', 'is_priority'], 'idx_in_company_complaint_priority');

            $table->foreign('company_id', 'fk_internal_notes_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_in_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('user_id', 'fk_internal_notes_user')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('edited_by_user_id', 'fk_internal_notes_edited_by')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('deleted_by_user_id', 'fk_internal_notes_deleted_by')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('complaint_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('generated_by_user_id');
            $table->enum('export_type', ['complaint_pdf', 'evidence_zip', 'audit_bundle']);
            $table->json('query_params_json')->nullable()
                ->comment('Filtros aplicados al generar: company_id, branch_id, status, rango de fechas');
            $table->string('storage_path', 1000);
            $table->string('file_hash', 64)->comment('SHA-256 del archivo exportado');
            $table->bigInteger('file_size_bytes');
            $table->dateTime('created_at')->useCurrent();

            $table->index(['company_id', 'complaint_id', 'created_at'], 'idx_cex_company_complaint_created');
            $table->index(['generated_by_user_id', 'created_at'], 'idx_cex_user_created');

            $table->foreign('company_id', 'fk_complaint_exports_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_cex_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('generated_by_user_id', 'fk_complaint_exports_user')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_exports');
        Schema::dropIfExists('internal_notes');
    }
};
