<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * complaint_notifications — avisos hacia el DENUNCIANTE.
 *   recipient_enc va cifrado porque puede ser un email, que en una
 *   denuncia no anónima identifica a la persona.
 *
 * user_notifications — avisos hacia los usuarios del panel.
 *   Estos no necesitan cifrado: el destinatario es un usuario conocido
 *   del sistema, no una identidad a proteger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()
                ->comment('Null para eventos de plataforma: onboarding_completed ocurre antes de que exista la empresa');
            $table->unsignedBigInteger('complaint_id');
            $table->enum('type', ['chat_enabled', 'status_updated', 'new_message']);
            $table->enum('channel', ['email', 'tracking_code']);
            $table->text('recipient_enc')->nullable()
                ->comment('CIFRADO — email o código de seguimiento del destinatario');
            $table->enum('status', ['pending', 'sent', 'error'])->default('pending');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index(['complaint_id', 'status'], 'idx_cn_complaint_status');
            $table->index(['company_id', 'complaint_id', 'status'], 'idx_cn_company_complaint_status');

            $table->foreign('company_id', 'fk_complaint_notifications_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_cn_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('complaint_id')->nullable();
            $table->enum('type', [
                'new_complaint', 'new_message', 'assigned', 'reassigned',
                'status_changed', 'export_ready', 'sin_revision', 'onboarding_completed',
            ]);
            $table->enum('status', ['pending', 'delivered', 'read', 'error'])->default('pending');
            $table->json('metadata')->nullable()
                ->comment('Datos adicionales según el tipo, ej: {empresa, token_id}');
            $table->dateTime('read_at')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index(['company_id', 'user_id', 'status'], 'idx_un_company_user_status');
            $table->index(['complaint_id', 'created_at'], 'idx_un_complaint_created');
            $table->index('user_id', 'idx_un_user');

            $table->foreign('company_id', 'fk_user_notifications_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_un_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('user_id', 'fk_user_notifications_user')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('complaint_notifications');
    }
};
