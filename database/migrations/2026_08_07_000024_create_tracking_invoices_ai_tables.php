<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tracking_sessions — cada consulta al canal público de seguimiento.
 *
 * Es la tabla más delicada del sistema desde el punto de vista del
 * anonimato: relaciona una IP con una denuncia. El ip_hash original era
 * SHA-256 sin clave, es decir reversible por fuerza bruta sobre el
 * espacio IPv4 completo. Acá es HMAC con IP_HASH_KEY, que vive solo en
 * el entorno: un dump de la base ya no alcanza para recuperar la IP.
 *
 * company_id y complaint_id son nullable a propósito: un intento con un
 * código inválido no corresponde a ninguna denuncia, y se registra igual
 * para detectar enumeración.
 *
 * invoices — facturación mensual por empresa (AFIP: CAE y vencimiento).
 *
 * ai_processings — trazabilidad de procesamientos con modelos de lenguaje.
 * input_hash guarda solo el SHA-256 de la entrada: nunca el texto de la
 * denuncia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()
                ->comment('Null cuando el código no corresponde a ninguna empresa');
            $table->unsignedBigInteger('complaint_id')->nullable();
            $table->string('tracking_code_hash', 255)->comment('SHA-256 — el texto plano nunca se guarda');
            $table->string('ip_hash', 64)->comment('HMAC-SHA256 con IP_HASH_KEY — no reversible sin la clave');
            $table->text('ip_enc')->comment('CIFRADO');
            $table->string('user_agent_hash', 64)->nullable();
            $table->text('user_agent_enc')->nullable()->comment('CIFRADO');
            $table->enum('result', ['success', 'invalid_code', 'blocked', 'rate_limit']);
            $table->dateTime('created_at')->useCurrent();

            $table->index(['tracking_code_hash', 'created_at'], 'idx_ts_code_created');
            $table->index(['ip_hash', 'created_at'], 'idx_ts_ip_created');
            $table->index(['company_id', 'complaint_id', 'created_at'], 'idx_ts_company_complaint_created');
            $table->index('complaint_id', 'idx_ts_complaint');

            $table->foreign('company_id', 'fk_tracking_sessions_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign('complaint_id', 'fk_tracking_sessions_complaint')
                ->references('id')->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->smallInteger('billing_month');
            $table->smallInteger('billing_year');
            $table->decimal('subtotal_amount', 12, 2)->nullable();
            $table->decimal('tax_amount', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->string('afip_cae', 20)->nullable();
            $table->date('afip_cae_expiry')->nullable();
            $table->string('invoice_number', 20)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->text('concept')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('pdf_url', 500)->nullable();
            $table->string('pdf_hash', 64)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['company_id', 'billing_year', 'billing_month'], 'uq_invoices_company_year_month');
            $table->index(['company_id', 'payment_status'], 'idx_invoices_company_status');
            $table->index(['company_id', 'issue_date'], 'idx_invoices_company_issue');

            $table->foreign('company_id', 'fk_invoices_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::create('ai_processings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('complaint_id');
            $table->enum('processing_type', ['classification', 'summary', 'risk_analysis', 'translation']);
            $table->enum('status', ['pending', 'processing', 'completed', 'error'])->default('pending');
            $table->string('model', 100);
            $table->string('provider', 60);
            $table->string('provider_request_id', 100)->nullable();
            $table->string('processing_region', 60)->nullable();
            $table->string('input_hash', 64)
                ->comment('SHA-256 de la entrada — para deduplicación. NUNCA guardar la entrada cruda');
            $table->json('output')->nullable();
            $table->integer('input_tokens')->nullable();
            $table->integer('output_tokens')->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->boolean('contains_pii')->default(false);
            $table->boolean('input_redacted')->default(true);
            $table->string('prompt_version', 30)->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['complaint_id', 'processing_type'], 'idx_ai_complaint_type');
            $table->index(['company_id', 'status', 'created_at'], 'idx_ai_company_status_created');
            $table->index(['company_id', 'complaint_id', 'processing_type'], 'idx_ai_company_complaint_type');

            $table->foreign('company_id', 'fk_ai_processings_company')
                ->references('id')->on('companies')
                ->restrictOnDelete()->cascadeOnUpdate();

            $table->foreign(['complaint_id', 'company_id'], 'fk_ai_complaint_company')
                ->references(['id', 'company_id'])->on('complaints')
                ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_processings');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('tracking_sessions');
    }
};
