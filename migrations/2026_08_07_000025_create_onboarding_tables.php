<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alta de empresas por invitación (onboarding).
 *
 * El superadmin genera un token con vencimiento de 48 horas y se lo envía
 * a la empresa. El formulario público guarda los datos en estas tablas
 * espejo, y recién al "Confirmar alta" se crean los registros reales en
 * companies / branches / users / company_fiscal_data.
 *
 * Se portan tal cual (§2.2). Existen porque en PHP puro no había forma
 * limpia de persistir un formulario multi-paso a medio completar; en
 * Laravel podría resolverse con una sola tabla de borradores. Ese
 * rediseño se propone aparte, no se decide en este port.
 *
 * DIVERGENCIA HEREDADA: los defaults de retención no coinciden con los de
 * `companies` (3660 días acá contra 365 allá, para denuncias y archivos).
 * Un alta por onboarding queda con diez años de retención y una creada a
 * mano con uno. Es una decisión de negocio pendiente, no un bug de port.
 *
 * CAMBIO: onboarding_tokens.created_by no tenía FK. Se agrega hacia users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64);
            $table->unsignedBigInteger('created_by')->comment('Superadmin que generó la invitación');
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->enum('status', ['pending', 'completed', 'expired'])->default('pending');
            $table->dateTime('created_at')->useCurrent();

            $table->unique('token', 'uq_onboarding_tokens_token');
            $table->index('status', 'idx_onboarding_tokens_status');

            $table->foreign('created_by', 'fk_onboarding_tokens_created_by')
                ->references('id')->on('users')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('companies_onboarding', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_id');
            $table->string('name', 200);
            $table->string('email', 255);
            $table->string('slug', 100);
            $table->string('default_language', 10)->default('es');
            $table->string('timezone', 60)->default('America/Argentina/Buenos_Aires');
            $table->smallInteger('retention_complaints_days')->default(3660);
            $table->smallInteger('retention_files_days')->default(3660);
            $table->smallInteger('retention_logs_days')->default(365);
            $table->dateTime('created_at')->useCurrent();

            $table->unique('token_id', 'uq_companies_onboarding_token');

            $table->foreign('token_id', 'fk_co_token')
                ->references('id')->on('onboarding_tokens')
                ->cascadeOnDelete();
        });

        Schema::create('branches_onboarding', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_id');
            $table->unsignedBigInteger('company_onboarding_id');
            $table->string('name', 200);
            $table->string('internal_code', 50)->nullable();
            $table->string('address', 300)->nullable();
            $table->boolean('is_headquarter')->default(false);
            $table->unsignedTinyInteger('display_order')->default(1);
            $table->dateTime('created_at')->useCurrent();

            $table->index('token_id', 'idx_bo_token');
            $table->index('company_onboarding_id', 'idx_bo_company');

            $table->foreign('token_id', 'fk_bo_token')
                ->references('id')->on('onboarding_tokens')
                ->cascadeOnDelete();

            $table->foreign('company_onboarding_id', 'fk_bo_company')
                ->references('id')->on('companies_onboarding')
                ->cascadeOnDelete();
        });

        Schema::create('users_onboarding', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_id');
            $table->unsignedBigInteger('company_onboarding_id');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255);
            $table->string('password_hash', 255);
            $table->enum('role', ['admin_principal', 'gestor']);
            $table->dateTime('created_at')->useCurrent();

            $table->index('token_id', 'idx_uo_token');
            $table->index('company_onboarding_id', 'idx_uo_company');

            $table->foreign('token_id', 'fk_uo_token')
                ->references('id')->on('onboarding_tokens')
                ->cascadeOnDelete();

            $table->foreign('company_onboarding_id', 'fk_uo_company')
                ->references('id')->on('companies_onboarding')
                ->cascadeOnDelete();
        });

        Schema::create('company_fiscal_data_onboarding', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_id');
            $table->unsignedBigInteger('company_onboarding_id');
            $table->string('tax_id', 13)->nullable()->comment('CUIT');
            $table->string('legal_name', 300)->nullable();
            $table->enum('vat_status', ['RI', 'Monotax', 'Exempt', 'FinalConsumer'])->nullable();
            $table->text('fiscal_address')->nullable();
            $table->json('billing_emails')->nullable();
            $table->boolean('uses_global_price')->default(true);
            $table->decimal('custom_amount', 12, 2)->nullable();
            $table->smallInteger('billing_day')->default(1);
            $table->enum('preferred_payment', ['bank_transfer', 'debit', 'check'])->default('bank_transfer');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('token_id', 'uq_cfdo_token');
            $table->index('company_onboarding_id', 'idx_cfdo_company');

            $table->foreign('token_id', 'fk_cfdo_token')
                ->references('id')->on('onboarding_tokens')
                ->cascadeOnDelete();

            $table->foreign('company_onboarding_id', 'fk_cfdo_company')
                ->references('id')->on('companies_onboarding')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_fiscal_data_onboarding');
        Schema::dropIfExists('users_onboarding');
        Schema::dropIfExists('branches_onboarding');
        Schema::dropIfExists('companies_onboarding');
        Schema::dropIfExists('onboarding_tokens');
    }
};
