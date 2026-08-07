<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial, asignaciones y eventos de la denuncia.
 *
 * NOTA H-010: complaint_assignments y complaints.assigned_to_user_id pueden
 * desincronizarse — la auditoría lo marcó y el schema todavía tiene las dos.
 * En la Etapa 5 hay que elegir una fuente de verdad; la recomendación es
 * que complaint_assignments (con ended_at NULL = activa) sea la autoridad y
 * complaints.assigned_to_user_id se mantenga solo como denormalización para
 * los índices de listado, escrita siempre dentro de la misma transacción.
 *
 * from_status / to_status son varchar y no enum, tal como el original: el
 * historial tiene que poder conservar estados que ya no existan en el enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('complaint_id');
            $table->string('from_status', 30)->nullable()->comment('Null en la primera transición');
            $table->string('to_status', 30);
            $table->unsignedBigInteger('changed_by_user_id')->nullable()
                ->comment('Null si lo cambió el sistema');
            $table->text('reason')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index(['company_id', 'complaint_id', 'created_at'], 'idx_csh_company_complaint_created');
            $table->index('complaint_id', 'idx_csh_complaint');

            $table->foreign('company_id', 'fk_complaint_status_history_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_csh_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('changed_by_user_id', 'fk_csh_changed_by')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('complaint_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('assigned_to_user_id');
            $table->unsignedBigInteger('assigned_by_user_id')->nullable()
                ->comment('Null si la asignación fue automática');
            $table->text('reason')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('ended_at')->nullable()->comment('Null = asignación vigente');

            $table->index(['company_id', 'complaint_id', 'created_at'], 'idx_ca_company_complaint_created');
            $table->index(['company_id', 'assigned_to_user_id', 'ended_at'], 'idx_ca_company_user_ended');
            $table->index('complaint_id', 'idx_ca_complaint');
            $table->index('assigned_to_user_id', 'idx_ca_assigned_to');

            $table->foreign('company_id', 'fk_complaint_assignments_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_ca_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('assigned_to_user_id', 'fk_complaint_assignments_assigned_to')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('assigned_by_user_id', 'fk_complaint_assignments_assigned_by')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('complaint_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('user_id')->nullable()
                ->comment('Null para eventos de sistema o del denunciante');
            $table->enum('event_type', [
                'received', 'assigned', 'status_changed', 'note_added', 'note_edited',
                'note_deleted', 'closed', 'chat_enabled', 'message_sent', 'file_uploaded',
                'file_downloaded', 'export_generated', 'message_deleted', 'priority_changed',
            ]);
            $table->json('payload')->nullable()
                ->comment('JSON plano — nunca debe contener datos personales, solo IDs');
            $table->string('request_id', 80)->nullable();
            $table->string('source_ip_hash', 64)->nullable()->comment('HMAC-SHA256 con IP_HASH_KEY');
            $table->text('source_ip_enc')->nullable()->comment('CIFRADO');
            $table->dateTime('created_at')->useCurrent();

            $table->index(['complaint_id', 'created_at'], 'idx_ce_complaint_created');
            $table->index(['company_id', 'event_type', 'created_at'], 'idx_ce_company_type_created');
            $table->index(['complaint_id', 'event_type', 'created_at'], 'idx_ce_complaint_type_created');

            $table->foreign('company_id', 'fk_complaint_events_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_ce_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('user_id', 'fk_complaint_events_user')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_events');
        Schema::dropIfExists('complaint_assignments');
        Schema::dropIfExists('complaint_status_history');
    }
};
