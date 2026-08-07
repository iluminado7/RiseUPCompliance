<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivotes de configuración por empresa.
 *
 * Cada empresa elige qué subconjunto de los catálogos globales ofrece en su
 * canal, con qué orden de aparición y cuáles están activos.
 *
 * NOTA DE PORT: en el schema original, company_areas y company_positions
 * tenían DOS claves foráneas sobre la misma columna, con reglas ON DELETE
 * contradictorias (una CASCADE, otra RESTRICT). Se crea una sola FK por
 * columna, con RESTRICT — que es la regla que efectivamente se aplicaba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('category_id');
            $table->smallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->primary(['company_id', 'category_id']);
            $table->index(['company_id', 'is_active', 'display_order'], 'idx_company_categories_active_order');

            $table->foreign('company_id', 'fk_company_categories_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('category_id', 'fk_company_categories_category')
                ->references('id')->on('complaint_categories')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('company_areas', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('area_id');
            $table->smallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->primary(['company_id', 'area_id']);
            $table->index(['company_id', 'is_active', 'display_order'], 'idx_company_areas_active_order');

            $table->foreign('company_id', 'fk_company_areas_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('area_id', 'fk_company_areas_area')
                ->references('id')->on('reported_areas')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('company_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('position_id');
            $table->smallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->primary(['company_id', 'position_id']);
            $table->index(['company_id', 'is_active', 'display_order'], 'idx_company_positions_active_order');

            $table->foreign('company_id', 'fk_company_positions_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('position_id', 'fk_company_positions_position')
                ->references('id')->on('reported_positions')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('company_relationships', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('relationship_id');
            $table->smallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->primary(['company_id', 'relationship_id']);
            $table->index(['company_id', 'is_active', 'display_order'], 'idx_company_relationships_active_order');

            $table->foreign('company_id', 'fk_company_relationships_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('relationship_id', 'fk_company_relationships_relationship')
                ->references('id')->on('reporter_relationships')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_relationships');
        Schema::dropIfExists('company_positions');
        Schema::dropIfExists('company_areas');
        Schema::dropIfExists('company_categories');
    }
};
