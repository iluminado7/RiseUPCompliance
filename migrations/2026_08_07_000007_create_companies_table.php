<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id');
            $table->string('name', 200);
            $table->string('email', 255);
            $table->string('slug', 100);
            $table->enum('status', ['active', 'suspended', 'deactivated'])->default('active');
            $table->json('public_configuration')->nullable();
            $table->text('sensitive_configuration_enc')->nullable()
                ->comment('CIFRADO — secretos del tenant: API keys, webhooks, certs SSO');
            $table->string('default_language', 10)->default('es');

            // Portado con corrección: el schema original tenía 'UTC-3', que no es
            // un identificador de zona horaria válido. Se unifica con el formato
            // IANA que ya usa companies_onboarding.
            $table->string('timezone', 60)->default('America/Argentina/Buenos_Aires');

            $table->smallInteger('complaints_retention_days')->default(365);
            $table->smallInteger('logs_retention_days')->default(730);
            $table->smallInteger('files_retention_days')->default(365);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('slug', 'uq_companies_slug');
            $table->index(['manager_id', 'status'], 'idx_companies_manager_status');

            $table->foreign('manager_id', 'fk_companies_manager')
                ->references('id')->on('tenant_managers')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
