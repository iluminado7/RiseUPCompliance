<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * password_reset_tokens — portado del schema propio, NO el de Laravel.
 *
 * Laravel usa una tabla con el email como PK y sin expiración explícita.
 * Este schema guarda el hash del token, su vencimiento y si ya se usó,
 * que es lo que la auditoría evaluó como correcto (token de 256 bits,
 * hasheado, 30 minutos, un solo uso).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token_hash', 64);
            $table->dateTime('expires_at');
            $table->boolean('used')->default(false);
            $table->dateTime('created_at')->useCurrent();

            $table->index('token_hash', 'idx_password_reset_token');
            $table->index('user_id', 'idx_password_reset_user');

            $table->foreign('user_id', 'fk_password_reset_tokens_user')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });

        Schema::create('user_branches', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');

            $table->primary(['user_id', 'branch_id']);
            $table->index('branch_id', 'idx_user_branches_branch');

            $table->foreign('user_id', 'fk_user_branches_user')
                ->references('id')->on('users')
                ->cascadeOnDelete();
            $table->foreign('branch_id', 'fk_user_branches_branch')
                ->references('id')->on('branches')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branches');
        Schema::dropIfExists('password_reset_tokens');
    }
};
