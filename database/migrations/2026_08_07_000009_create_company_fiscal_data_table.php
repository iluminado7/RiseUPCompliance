<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_fiscal_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('tax_id', 13)->nullable()->comment('CUIT');
            $table->string('legal_name', 300)->nullable();
            $table->enum('vat_status', ['RI', 'Monotax', 'Exempt', 'FinalConsumer'])->nullable();
            $table->text('fiscal_address')->nullable();
            $table->json('billing_emails')->nullable()
                ->comment('Array de strings, ej: ["a@b.com","c@d.com"]');
            $table->boolean('uses_global_price')->default(true);
            $table->decimal('custom_amount', 12, 2)->nullable();
            $table->smallInteger('billing_day');
            $table->enum('preferred_payment', ['bank_transfer', 'debit', 'check']);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('company_id', 'uq_company_fiscal_data_company');

            $table->foreign('company_id', 'fk_company_fiscal_data_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_fiscal_data');
    }
};
