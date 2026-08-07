<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_document_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()
                ->comment('Null cuando es un documento global');
            $table->enum('document_type', ['privacy_notice', 'terms', 'policy']);
            $table->string('version', 30);
            $table->string('content_hash', 64)
                ->comment('SHA-256 del contenido — verificar antes de darlo por válido');
            $table->dateTime('published_at');
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['company_id', 'document_type', 'version'], 'uq_legal_doc_versions');
            $table->index(['company_id', 'document_type', 'is_active'], 'idx_legal_doc_active');

            $table->foreign('company_id', 'fk_legal_doc_versions_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_versions');
    }
};
