<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * files — adjuntos de las denuncias.
 *
 * clean_metadata guarda el resultado del strip de metadatos: qué se
 * encontró y qué se eliminó. Es la evidencia de que el EXIF stripping
 * (H-004) efectivamente corrió sobre este archivo.
 *
 * declared_mime viene del cliente y puede estar falseado; detected_mime
 * se calcula de los bytes. Las decisiones se toman con el segundo.
 *
 * CAMBIO: se agrega uq_files_id_company, que el original no tenía. Sin él
 * file_access_logs no puede declarar FK compuesta hacia files, y el
 * aislamiento entre empresas quedaría solo a cargo de la aplicación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('complaint_id');

            $table->string('original_name', 500);
            $table->string('storage_name', 500);
            $table->string('storage_bucket', 200);
            $table->string('storage_path', 1000);
            $table->string('storage_version_id', 200)->nullable();

            $table->string('sha256_hash', 64)->comment('SHA-256 del contenido — verificar en cada lectura');
            $table->bigInteger('size_bytes');
            $table->string('declared_mime', 100)->nullable()
                ->comment('Declarado por el cliente — puede estar falseado, no confiar solo en esto');
            $table->string('detected_mime', 100)->nullable()->comment('Detectado de los bytes — confiable');
            $table->string('detected_extension', 20)->nullable();

            $table->boolean('encrypted_at_rest')->default(true);
            $table->string('encryption_mode', 50)->nullable();
            $table->string('kms_key_id', 100)->nullable();

            $table->enum('file_status', ['pending', 'available', 'quarantine', 'deleted'])->default('pending');
            $table->string('scan_engine', 100)->nullable();
            $table->dateTime('scan_date')->nullable();
            $table->enum('scan_result', ['clean', 'infected', 'error', 'pending'])->default('pending');
            $table->json('clean_metadata')->nullable()
                ->comment('Resultado del strip de metadatos: qué se encontró y qué se eliminó');
            $table->string('quarantine_path', 1000)->nullable();

            $table->boolean('available_for_analyst')->default(false);
            $table->enum('uploaded_by_type', ['reporter', 'analyst', 'system']);
            $table->unsignedBigInteger('uploaded_by_user_id')->nullable();

            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('storage_name', 'uq_files_storage_name');
            $table->unique(['id', 'company_id'], 'uq_files_id_company');

            $table->index('complaint_id', 'idx_files_complaint');
            $table->index(['company_id', 'complaint_id'], 'idx_files_company_complaint');
            $table->index(['complaint_id', 'file_status', 'created_at'], 'idx_files_complaint_status_created');
            $table->index(['company_id', 'available_for_analyst', 'file_status'], 'idx_files_company_analyst_status');
            $table->index(['company_id', 'file_status'], 'idx_files_company_status');
            $table->index('sha256_hash', 'idx_files_sha256');

            $table->foreign('company_id', 'fk_files_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_files_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('uploaded_by_user_id', 'fk_files_uploaded_by')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('file_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('file_id');
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('action', ['preview', 'download', 'quarantine_move', 'delete']);
            $table->string('ip_hash', 64)->nullable()->comment('HMAC-SHA256 con IP_HASH_KEY');
            $table->text('ip_enc')->nullable()->comment('CIFRADO');
            $table->dateTime('created_at')->useCurrent();

            $table->index(['company_id', 'file_id', 'created_at'], 'idx_fal_company_file_created');
            $table->index(['company_id', 'user_id', 'created_at'], 'idx_fal_company_user_created');
            $table->index('complaint_id', 'idx_fal_complaint');

            $table->foreign('company_id', 'fk_file_access_logs_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['file_id', 'company_id'], 'fk_fal_file_company')
                ->references(['id', 'company_id'])->on('files')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_fal_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('user_id', 'fk_file_access_logs_user')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_access_logs');
        Schema::dropIfExists('files');
    }
};
