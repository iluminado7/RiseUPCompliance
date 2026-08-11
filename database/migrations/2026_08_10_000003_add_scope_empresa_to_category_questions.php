<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Columna generada scope_empresa en category_questions.
 *
 * El unique original era (category_id, company_id, question_order,
 * version), pero las preguntas del catalogo maestro llevan
 * company_id = NULL y en MySQL un UNIQUE no restringe filas con NULL: se
 * podian acumular filas identicas del catalogo sin que la base protestara.
 *
 * Mismo caso que audit_logs.company_id, misma solucion:
 * scope_empresa = COALESCE(company_id, 0) le da al catalogo maestro su
 * propio espacio protegido, igual que la cadena de plataforma.
 *
 * NOTA SOBRE LA FK: MySQL no permite ON UPDATE CASCADE en una clave
 * foranea sobre la columna base de una columna generada STORED, asi que
 * hay que soltar la FK, agregar la columna y recrearla sin la cascada.
 * companies.id es un auto_increment que nunca cambia, asi que esa cascada
 * era inoperante de todos modos.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE category_questions DROP FOREIGN KEY fk_category_questions_company');
        DB::statement('ALTER TABLE category_questions DROP INDEX uq_category_questions_scope');

        DB::statement("
            ALTER TABLE category_questions
            ADD COLUMN scope_empresa BIGINT UNSIGNED
                AS (COALESCE(company_id, 0)) STORED
                COMMENT '0 = catalogo maestro; >0 = preguntas propias de esa empresa'
            AFTER company_id
        ");

        DB::statement('
            ALTER TABLE category_questions
            ADD UNIQUE KEY uq_category_questions_scope
                (category_id, scope_empresa, question_order, version)
        ');

        DB::statement('
            ALTER TABLE category_questions
            ADD CONSTRAINT fk_category_questions_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE RESTRICT
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE category_questions DROP FOREIGN KEY fk_category_questions_company');
        DB::statement('ALTER TABLE category_questions DROP INDEX uq_category_questions_scope');
        DB::statement('ALTER TABLE category_questions DROP COLUMN scope_empresa');

        DB::statement('
            ALTER TABLE category_questions
            ADD UNIQUE KEY uq_category_questions_scope
                (category_id, company_id, question_order, version)
        ');

        DB::statement('
            ALTER TABLE category_questions
            ADD CONSTRAINT fk_category_questions_company
            FOREIGN KEY (company_id) REFERENCES companies(id)
            ON DELETE RESTRICT ON UPDATE CASCADE
        ');
    }
};
