<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name', 200);
            $table->string('internal_code', 50)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_headquarter')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('default_language', 10)->nullable();
            $table->string('timezone', 60)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Unique compuesto (id, company_id): permite que otras tablas declaren
            // FKs compuestas que garantizan a nivel de base que una sucursal
            // referenciada pertenece a la misma empresa. Es integridad multi-tenant
            // aplicada por MySQL, no por la aplicación.
            $table->unique(['id', 'company_id'], 'uq_branches_id_company');

            $table->unique(['company_id', 'internal_code'], 'uq_branches_company_code');
            $table->index(['company_id', 'is_active', 'name'], 'idx_branches_selection');
            $table->index(['company_id', 'is_active'], 'idx_branches_company_active');
            $table->index(['company_id', 'is_headquarter'], 'idx_branches_company_hq');

            $table->foreign('company_id', 'fk_branches_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
