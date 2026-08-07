<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * complaints — tabla núcleo del dominio.
 *
 * ── CAMBIOS RESPECTO DEL SCHEMA ORIGINAL ──
 *
 * 1. Se elimina la columna `user_id`. Pertenecía al rol `reporter`, que
 *    se descartó: el denunciante nunca es usuario de la plataforma.
 *
 * 2. Se agrega `encryption_salt`. Es lo que hace posible el crypto-shredding
 *    que el schema original prometía pero no podía cumplir: había un solo
 *    encryption_key_id global, así que destruir la clave habría borrado
 *    TODAS las denuncias, no una.
 *
 *    Con un salt por denuncia, las claves de los datos personales de esta
 *    denuncia (denunciante, respuestas, denunciado, mensajes) se derivan de
 *    la clave maestra + este salt. Purgar por retención es:
 *
 *        UPDATE complaints SET encryption_salt = NULL, purged_at = NOW()
 *
 *    NULL significa purgada: los datos quedan cifrados sin clave posible y
 *    las filas permanecen, para que la traza no muestre huecos.
 *
 * 3. FK compuesta sobre (branch_id, company_id): MySQL garantiza que la
 *    sucursal referenciada pertenece a la misma empresa que la denuncia.
 *
 * 4. Se agrega FK sobre relationship_id, que en el original no tenía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('internal_code', 50);
            $table->boolean('is_anonymous')->default(true);
            $table->enum('intake_channel', ['web', 'email', 'whatsapp', 'phone', 'in_person', 'api']);
            $table->enum('submission_status', ['draft', 'submitted', 'confirmed'])->default('draft');

            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('relationship_id')->nullable();
            $table->string('relationship_other_text', 100)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->unsignedBigInteger('reported_area_id')->nullable();
            $table->string('reported_area_other', 200)->nullable();
            $table->unsignedBigInteger('reported_position_id')->nullable();
            $table->string('reported_position_other', 200)->nullable();
            $table->date('incident_date')->nullable()->comment('Fecha del hecho reportada por el denunciante');

            $table->text('reported_first_name_enc')->nullable()->comment('CIFRADO — crypto-shredding vía encryption_salt');
            $table->text('reported_last_name_enc')->nullable()->comment('CIFRADO — crypto-shredding vía encryption_salt');

            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', ['new', 'seen', 'in_progress', 'under_review', 'resolved', 'closed', 'archived'])
                ->default('new');
            $table->string('public_status', 50)->default('Recibida')
                ->comment('Estado que ve el denunciante — desacoplado del status interno');
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();

            $table->string('tracking_code_hash', 255)->comment('SHA-256 — el texto plano NUNCA se guarda');

            // Salt de derivación de claves. NULL = denuncia purgada.
            $table->string('encryption_salt', 64)->nullable()
                ->comment('Salt hex para derivar claves de esta denuncia. NULL = purgada (crypto-shredding)');

            $table->boolean('chat_enabled')->default(false);
            $table->dateTime('chat_enabled_at')->nullable();
            $table->unsignedBigInteger('chat_enabled_by_user_id')->nullable();

            $table->boolean('privacy_notice_accepted')->default(false);
            $table->unsignedBigInteger('privacy_notice_version_id')->nullable();
            $table->dateTime('privacy_notice_accepted_at')->nullable();

            $table->boolean('captcha_validated')->default(false);
            $table->dateTime('captcha_validated_at')->nullable();
            $table->string('captcha_provider', 50)->nullable();

            $table->dateTime('last_action_at')->useCurrent();
            $table->dateTime('first_response_due_at')->nullable();
            $table->dateTime('resolution_due_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('archived_at')->nullable();
            $table->dateTime('purged_at')->nullable()
                ->comment('Lo setea SOLO la purga automática por retención, nunca a mano');

            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['company_id', 'internal_code'], 'uq_complaints_company_code');
            $table->unique(['id', 'company_id'], 'uq_complaints_id_company');
            $table->unique('tracking_code_hash', 'uq_complaints_tracking_hash');

            $table->index(['company_id', 'status'], 'idx_complaints_company_status');
            $table->index(['company_id', 'created_at'], 'idx_complaints_company_created');
            $table->index(['company_id', 'assigned_to_user_id', 'status'], 'idx_complaints_company_user_status');
            $table->index(['company_id', 'priority', 'status'], 'idx_complaints_company_priority_status');
            $table->index(['company_id', 'category_id', 'created_at'], 'idx_complaints_company_category_created');
            $table->index(['company_id', 'last_action_at'], 'idx_complaints_company_last_action');
            $table->index(['company_id', 'branch_id'], 'idx_complaints_company_branch');
            $table->index(['company_id', 'incident_date'], 'idx_complaints_company_incident_date');
            $table->index('assigned_to_user_id', 'idx_complaints_assigned_user');
            $table->index(['status', 'created_at'], 'idx_global_status_created');
            $table->index(['priority', 'created_at'], 'idx_global_priority_created');
            $table->index('internal_code', 'idx_internal_code_search');

            $table->foreign('company_id', 'fk_complaints_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('category_id', 'fk_complaints_category')
                ->references('id')->on('complaint_categories')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('relationship_id', 'fk_complaints_relationship')
                ->references('id')->on('reporter_relationships')
                ->nullOnDelete()->cascadeOnUpdate();

            // FK compuesta: la sucursal debe pertenecer a la misma empresa.
            $table->foreign(['branch_id', 'company_id'], 'fk_complaints_branch_company')
                ->references(['id', 'company_id'])->on('branches')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('reported_area_id', 'fk_complaints_area')
                ->references('id')->on('reported_areas')
                ->nullOnDelete();

            $table->foreign('reported_position_id', 'fk_complaints_position')
                ->references('id')->on('reported_positions')
                ->nullOnDelete();

            $table->foreign('privacy_notice_version_id', 'fk_complaints_privacy_notice')
                ->references('id')->on('legal_document_versions')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('assigned_to_user_id', 'fk_complaints_assigned_to')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('chat_enabled_by_user_id', 'fk_complaints_chat_enabled_by')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
