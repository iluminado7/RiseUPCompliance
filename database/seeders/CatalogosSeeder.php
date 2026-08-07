<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos globales — datos reales portados del schema `whistleblowing`.
 *
 * Los IDs se fijan explícitamente porque son referenciados por FKs
 * en las tablas pivote (company_categories, company_areas, etc.).
 *
 * NOTA: el rol `reporter` (id 5) NO se incluye. Fue eliminado del
 * diseño: el denunciante nunca es usuario de la plataforma.
 */
class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'id' => 1,
                'name' => 'gestor',
                'description' => 'Gestiona denuncias asignadas, puede cambiar estados y agregar notas',
                'hierarchy_level' => 3,
                'permissions_json' => json_encode([
                    'notas' => true,
                    'ver_asignadas' => true,
                    'cambiar_estado' => true,
                ]),
            ],
            [
                'id' => 2,
                'name' => 'superadmin',
                'description' => 'Acceso total al sistema, gestiona empresas y usuarios globales',
                'hierarchy_level' => 1,
                'permissions_json' => json_encode([
                    'notas' => true,
                    'asignar' => true,
                    'ver_todo' => true,
                    'cambiar_estado' => true,
                    'gestionar_empresas' => true,
                    'gestionar_usuarios' => true,
                ]),
            ],
            [
                'id' => 3,
                'name' => 'admin_principal',
                'description' => 'Acceso completo dentro de su empresa, gestiona analistas y denuncias',
                'hierarchy_level' => 2,
                'permissions_json' => json_encode([
                    'notas' => true,
                    'asignar' => true,
                    'ver_empresa' => true,
                    'cambiar_estado' => true,
                    'gestionar_usuarios' => true,
                ]),
            ],
            [
                'id' => 4,
                'name' => 'investigador_externo',
                'description' => 'Solo lectura sobre denuncias asignadas explícitamente',
                'hierarchy_level' => 4,
                'permissions_json' => json_encode([
                    'ver_asignadas' => true,
                ]),
            ],
        ]);

        DB::table('complaint_categories')->insert([
            ['id' => 1,  'code' => 'fraude',              'name_es' => 'Fraude',                  'description_es' => 'Fraude, robo o gastos irregulares'],
            ['id' => 2,  'code' => 'acoso',               'name_es' => 'Acoso',                   'description_es' => 'Acoso, discriminación y/o malos tratos'],
            ['id' => 3,  'code' => 'corrupcion',          'name_es' => 'Corrupción',              'description_es' => 'Actos de corrupción o sobornos'],
            ['id' => 4,  'code' => 'conflicto_intereses', 'name_es' => 'Conflicto de intereses',  'description_es' => 'Intereses personales en decisiones laborales'],
            ['id' => 5,  'code' => 'adulteracion',        'name_es' => 'Adulteración',            'description_es' => 'Manipulación de datos o documentos'],
            ['id' => 6,  'code' => 'mal_desempeno',       'name_es' => 'Mal desempeño',           'description_es' => 'Abuso de poder o mala conducta'],
            ['id' => 7,  'code' => 'robo_informacion',    'name_es' => 'Robo de información',     'description_es' => 'Uso indebido de información'],
            ['id' => 8,  'code' => 'mal_uso_bienes',      'name_es' => 'Mal uso de bienes',       'description_es' => 'Uso indebido de recursos'],
            ['id' => 9,  'code' => 'mejora_procesos',     'name_es' => 'Mejora de procesos',      'description_es' => 'Sugerencias o mejoras'],
            ['id' => 10, 'code' => 'reporte_libre',       'name_es' => 'Reporte libre',           'description_es' => 'Denuncia sin categoría específica'],
        ]);

        DB::table('reported_areas')->insert([
            ['id' => 1,  'code' => 'administracion',    'name_es' => 'Administración',    'display_order' => 1],
            ['id' => 2,  'code' => 'atencion_cliente',  'name_es' => 'Atención al Cliente', 'display_order' => 2],
            ['id' => 3,  'code' => 'auditoria',         'name_es' => 'Auditoría Interna', 'display_order' => 3],
            ['id' => 4,  'code' => 'compras',           'name_es' => 'Compras',           'display_order' => 4],
            ['id' => 5,  'code' => 'rrhh',              'name_es' => 'Recursos Humanos',  'display_order' => 5],
            ['id' => 6,  'code' => 'finanzas',          'name_es' => 'Finanzas',          'display_order' => 6],
            ['id' => 7,  'code' => 'operaciones',       'name_es' => 'Operaciones',       'display_order' => 7],
            ['id' => 8,  'code' => 'ventas',            'name_es' => 'Ventas',            'display_order' => 8],
            ['id' => 9,  'code' => 'it',                'name_es' => 'IT',                'display_order' => 9],
            ['id' => 10, 'code' => 'seguridad',         'name_es' => 'Seguridad',         'display_order' => 10],
            ['id' => 11, 'code' => 'logistica',         'name_es' => 'Logística',         'display_order' => 11],
            ['id' => 12, 'code' => 'tesoreria',         'name_es' => 'Tesorería',         'display_order' => 12],
            ['id' => 13, 'code' => 'otro',              'name_es' => 'Otro',              'display_order' => 99],
        ]);

        DB::table('reported_positions')->insert([
            ['id' => 1,  'code' => 'empleado',       'name_es' => 'Empleado',       'display_order' => 1],
            ['id' => 2,  'code' => 'supervisor',     'name_es' => 'Supervisor',     'display_order' => 2],
            ['id' => 3,  'code' => 'encargado',      'name_es' => 'Encargado',      'display_order' => 3],
            ['id' => 4,  'code' => 'gerente',        'name_es' => 'Gerente',        'display_order' => 4],
            ['id' => 5,  'code' => 'director',       'name_es' => 'Director',       'display_order' => 5],
            ['id' => 6,  'code' => 'auxiliar',       'name_es' => 'Auxiliar',       'display_order' => 6],
            ['id' => 7,  'code' => 'operador',       'name_es' => 'Operador',       'display_order' => 7],
            ['id' => 8,  'code' => 'analista',       'name_es' => 'Analista',       'display_order' => 8],
            ['id' => 9,  'code' => 'tercerizado',    'name_es' => 'Tercerizado',    'display_order' => 9],
            ['id' => 10, 'code' => 'vicepresidente', 'name_es' => 'Vicepresidente', 'display_order' => 10],
            ['id' => 11, 'code' => 'otro',           'name_es' => 'Otro',           'display_order' => 99],
            ['id' => 12, 'code' => 'jefe',           'name_es' => 'Jefe',           'display_order' => 100],
        ]);

        DB::table('reporter_relationships')->insert([
            ['id' => 1, 'code' => 'empleado',    'name_es' => 'Empleado'],
            ['id' => 2, 'code' => 'ex_empleado', 'name_es' => 'Ex-empleado'],
            ['id' => 3, 'code' => 'cliente',     'name_es' => 'Cliente'],
            ['id' => 4, 'code' => 'proveedor',   'name_es' => 'Proveedor'],
            ['id' => 5, 'code' => 'otro',        'name_es' => 'Otro'],
        ]);
    }
}
