<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users — usuarios del panel de gestión.
 *
 * IMPORTANTE: esta tabla tiene DOS tipos de usuario, distinguidos por
 * cuál de los dos vínculos está poblado:
 *
 *   - Usuario de empresa:  company_id NOT NULL, manager_id NULL
 *   - Manager de plataforma: manager_id NOT NULL, company_id NULL
 *
 * Por eso ambas columnas son nullable. El Global Scope de tenant tiene
 * que contemplar el segundo caso: un usuario sin company_id no es un
 * error, es un manager que atraviesa el scope.
 *
 * El denunciante NO está en esta tabla y nunca debe estarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id')->nullable()
                ->comment('Null cuando es usuario de empresa');
            $table->unsignedBigInteger('company_id')->nullable()
                ->comment('Null cuando es manager de plataforma');
            $table->unsignedBigInteger('role_id');

            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255);
            $table->text('phone')->nullable()->comment('CIFRADO');
            $table->string('password_hash', 255);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('preferred_language', 10)->nullable();
            $table->string('timezone', 60)->nullable();

            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret_enc')->nullable()
                ->comment('CIFRADO — secreto TOTP, se descifra para validar 2FA');

            $table->smallInteger('failed_attempts')->default(0);
            $table->dateTime('locked_until')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->string('last_login_ip_hash', 64)->nullable()->comment('HMAC-SHA256 de la IP');
            $table->text('last_login_ip_enc')->nullable()->comment('CIFRADO');
            $table->string('recovery_code_hash', 255)->nullable();
            $table->dateTime('recovery_expires_at')->nullable();
            $table->dateTime('password_changed_at')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->dateTime('last_failed_login_at')->nullable();

            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('email', 'uq_users_email');

            // Unique compuesto para FKs multi-tenant (mismo patrón que branches).
            $table->unique(['id', 'company_id'], 'uq_users_id_company');

            $table->index(['company_id', 'role_id', 'status'], 'idx_users_company_role_status');
            $table->index(['manager_id', 'status'], 'idx_users_manager_status');
            $table->index('locked_until', 'idx_users_locked_until');

            $table->foreign('company_id', 'fk_users_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('manager_id', 'fk_users_manager')
                ->references('id')->on('tenant_managers')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('role_id', 'fk_users_role')
                ->references('id')->on('roles')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
