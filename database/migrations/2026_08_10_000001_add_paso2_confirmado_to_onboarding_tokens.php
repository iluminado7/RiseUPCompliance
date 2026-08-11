<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El paso 2 del formulario (sucursales adicionales) puede quedar vacio
 * legitimamente: una empresa con una sola sede no agrega ninguna. Sin una
 * marca explicita no hay forma de distinguir "todavia no paso por el paso
 * 2" de "paso y no agrego nada".
 *
 * El sistema original resolvia esto con $_SESSION['onboarding_p2_done_N'],
 * asi que cerrar el navegador hacia perder el paso y la persona tenia que
 * volver a recorrerlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_tokens', function (Blueprint $table) {
            $table->boolean('paso2_confirmado')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_tokens', function (Blueprint $table) {
            $table->dropColumn('paso2_confirmado');
        });
    }
};
