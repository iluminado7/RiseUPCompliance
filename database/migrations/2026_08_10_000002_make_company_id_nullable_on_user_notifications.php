<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * user_notifications.company_id pasa a nullable.
 *
 * El enum de `type` incluye 'onboarding_completed', un evento que por
 * definición ocurre ANTES de que la empresa exista. Con la columna
 * NOT NULL esa notificación era imposible de insertar.
 *
 * El sistema anterior lo intentaba igual y envolvía el INSERT en un
 * try/catch que solo escribía al error_log, así que ninguna notificación
 * de onboarding llegó nunca a un superadmin.
 *
 * Mismo caso que audit_logs.company_id: un evento de plataforma en una
 * tabla que asume empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hay que soltar la FK antes de cambiar la columna, y recrearla
        // después: MySQL no permite modificar una columna con FK activa.
        DB::statement('ALTER TABLE user_notifications DROP FOREIGN KEY fk_user_notifications_company');
        DB::statement('ALTER TABLE user_notifications MODIFY company_id BIGINT UNSIGNED NULL');
        DB::statement('
            ALTER TABLE user_notifications
            ADD CONSTRAINT fk_user_notifications_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE RESTRICT ON UPDATE CASCADE
        ');
    }

    public function down(): void
    {
        DB::statement('DELETE FROM user_notifications WHERE company_id IS NULL');
        DB::statement('ALTER TABLE user_notifications DROP FOREIGN KEY fk_user_notifications_company');
        DB::statement('ALTER TABLE user_notifications MODIFY company_id BIGINT UNSIGNED NOT NULL');
        DB::statement('
            ALTER TABLE user_notifications
            ADD CONSTRAINT fk_user_notifications_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE RESTRICT ON UPDATE CASCADE
        ');
    }
};