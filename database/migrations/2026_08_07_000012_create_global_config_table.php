<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_config', function (Blueprint $table) {
            $table->id();

            // 'key' es palabra reservada en MySQL. Laravel la escapa
            // automáticamente, pero cualquier SQL crudo necesita backticks.
            $table->string('key', 100);

            $table->text('value')->nullable();
            $table->text('value_enc')->nullable()->comment('CIFRADO — solo cuando is_secret = 1');
            $table->enum('value_type', ['string', 'integer', 'decimal', 'boolean', 'json']);
            $table->boolean('is_secret')->default(false);
            $table->integer('version')->default(1);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('updated_by_manager_id')->nullable()
                ->comment('Solo los tenant_managers pueden modificar esta tabla');

            // Portado literal: la tabla original no tiene created_at.
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('key', 'uq_global_config_key');

            $table->foreign('updated_by_manager_id', 'fk_global_config_manager')
                ->references('id')->on('tenant_managers')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_config');
    }
};
