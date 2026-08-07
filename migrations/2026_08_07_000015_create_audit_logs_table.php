<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * audit_logs — traza append-only con encadenamiento de hashes.
 *
 * NUNCA hacer UPDATE ni DELETE sobre esta tabla.
 *
 * ── CAMBIOS RESPECTO DEL SCHEMA ORIGINAL ──
 *
 * 1. company_id pasa a NULLABLE.
 *    En el original era NOT NULL con FK, y por eso el helper abría con
 *    `if (!$company_id) return;`. Consecuencia: los intentos de login
 *    fallidos y TODA la actividad del superadmin (que no tiene company_id)
 *    nunca se registraron. Con la columna nullable, los eventos de
 *    plataforma tienen dónde guardarse.
 *
 * 2. Se agrega chain_scope, columna generada = COALESCE(company_id, 0).
 *    El unique original era (company_id, company_sequence), pero en MySQL
 *    un UNIQUE no restringe filas con NULL: dos eventos de plataforma con
 *    el mismo sequence habrían pasado sin error, rompiendo la cadena.
 *    chain_scope hace que los eventos de plataforma formen su propia
 *    cadena numerada, protegida por el mismo unique.
 *
 *    El verificador de integridad recorre N+1 cadenas: una por empresa,
 *    más la de plataforma (chain_scope = 0).
 *
 * NOTA: el algoritmo de cálculo del log_hash NO se porta tal cual — el
 * original cubría 5 de 20 campos. Se corrige en AuditLogService (Etapa 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()
                ->comment('Null para eventos de plataforma: login fallido, acción de manager, sistema');

            // Columna generada. Va DENTRO del CREATE TABLE y no en un ALTER
            // posterior: agregar una columna STORED a una tabla que ya tiene
            // FK obliga a MySQL a reconstruir la tabla, y la recreación de la
            // FK falla con error 1215.
            $table->unsignedBigInteger('chain_scope')
                ->storedAs('coalesce(company_id, 0)')
                ->comment('0 = cadena de plataforma; >0 = cadena de la empresa');

            $table->unsignedBigInteger('company_sequence');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Null para eventos de sistema');

            $table->enum('origin', ['web', 'api', 'system', 'cron']);
            $table->string('action', 100);
            $table->string('entity_type', 60)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('affected_complaint_id')->nullable();
            $table->enum('result', ['success', 'error', 'blocked']);

            $table->string('ip_hash', 64)->nullable()->comment('HMAC-SHA256 con IP_HASH_KEY');
            $table->text('ip_enc')->nullable()->comment('CIFRADO');
            $table->string('user_agent_hash', 64)->nullable();
            $table->text('user_agent_enc')->nullable()->comment('CIFRADO');

            // Ampliado de char(36) a varchar(80): el original guardaba
            // 'anon:' + sha256 (69 chars) en una columna de 36, y el INSERT
            // fallaba en silencio para toda denuncia anónima (H-005).
            $table->string('request_id', 80)->nullable();
            $table->string('session_id', 80)->nullable();
            $table->string('correlation_id', 80)->nullable();

            $table->text('detail_enc')->nullable()->comment('CIFRADO');
            $table->string('previous_hash', 64)->nullable()->comment('Hash del registro anterior de la cadena');
            $table->string('log_hash', 64)->comment('SHA-256 de este registro, incluyendo previous_hash');

            $table->text('external_timestamp_token')->nullable()->comment('Token TSA para certificación legal');
            $table->text('signed_hash')->nullable();
            $table->string('signature_provider', 100)->nullable();

            $table->dateTime('created_at')->useCurrent();

            $table->unique(['chain_scope', 'company_sequence'], 'uq_audit_logs_chain');

            $table->index(['created_at'], 'idx_audit_logs_timeline');
            $table->index(['affected_complaint_id', 'created_at'], 'idx_audit_affected_complaint');
            $table->index(['company_id', 'created_at'], 'idx_audit_logs_company_created');
            $table->index(['company_id', 'action', 'created_at'], 'idx_audit_logs_company_action_created');
            $table->index(['company_id', 'result', 'created_at'], 'idx_audit_logs_company_result_created');
            $table->index(['user_id', 'created_at'], 'idx_audit_logs_user_created');
            $table->index(['entity_type', 'entity_id'], 'idx_audit_logs_entity');

            $table->foreign('company_id', 'fk_audit_logs_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
