<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat entre denunciante e investigador.
 *
 * content está cifrado con clave derivada de complaints.encryption_salt.
 * En el sistema original el encryption_key_id era el literal
 * 'key-dev-placeholder' y el contenido iba en claro (deuda listada en §7
 * del brief). Acá el cifrado es real desde el principio.
 *
 * is_deleted_by_sender: el analista puede retirar un mensaje solo si el
 * denunciante todavía no lo leyó. original_content guarda la copia de
 * auditoría — cifrada igual, y nunca se muestra al denunciante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('complaint_id');
            $table->enum('sender_type', ['analyst', 'reporter', 'system']);
            $table->unsignedBigInteger('sender_user_id')->nullable()
                ->comment('Null si el emisor es el denunciante o el sistema');

            $table->text('content')->comment('CIFRADO — crypto-shredding vía complaints.encryption_salt');
            $table->string('encryption_key_id', 100)
                ->comment('ID de la clave maestra usada. El salt vive en complaints');

            $table->boolean('is_read_by_reporter')->default(false);
            $table->boolean('is_deleted_by_sender')->default(false)
                ->comment('Solo permitido si is_read_by_reporter = 0');
            $table->dateTime('deleted_by_sender_at')->nullable();
            $table->text('original_content')->nullable()
                ->comment('CIFRADO — copia de auditoría previa al borrado, nunca visible al denunciante');

            $table->dateTime('created_at')->useCurrent();

            $table->index(['complaint_id', 'created_at'], 'idx_cm_complaint_created');
            $table->index(['complaint_id', 'is_read_by_reporter'], 'idx_cm_complaint_read');
            $table->index(['company_id', 'complaint_id', 'created_at'], 'idx_cm_company_complaint_created');

            $table->foreign('company_id', 'fk_complaint_messages_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_cm_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('sender_user_id', 'fk_complaint_messages_sender')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('complaint_message_reads', function (Blueprint $table) {
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->dateTime('read_at')->useCurrent();

            $table->primary(['message_id', 'user_id']);
            $table->index(['user_id', 'read_at'], 'idx_cmr_user_read');

            $table->foreign('message_id', 'fk_complaint_message_reads_message')
                ->references('id')->on('complaint_messages')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('user_id', 'fk_complaint_message_reads_user')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_message_reads');
        Schema::dropIfExists('complaint_messages');
    }
};
