<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name_es', 150);
            $table->text('description_es')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('code', 'uq_complaint_categories_code');
            $table->index('is_active', 'idx_complaint_categories_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_categories');
    }
};
