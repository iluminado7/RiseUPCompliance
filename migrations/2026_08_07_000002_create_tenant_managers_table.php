<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_managers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 150);
            $table->string('email', 255);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('email', 'uq_tenant_managers_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_managers');
    }
};
