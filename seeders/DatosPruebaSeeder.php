<?php

namespace Database\Seeders;

use App\Enums\EstadoDenuncia;
use App\Models\Denuncia;
use App\Models\Empresa;
use App\Models\ManagerPlataforma;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Services\ServicioCifrado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de prueba para desarrollo local.
 *
 * DOS empresas a propósito: con una sola no se puede detectar una fuga
 * entre tenants, que es exactamente lo que hay que poder probar.
 *
 * NUNCA correr esto en producción. Las contraseñas son fijas y conocidas.
 */
class DatosPruebaSeeder extends Seeder
{
    private const CLAVE = 'Prueba1234!';

    public function run(ServicioCifrado $cifrado): void
    {
        if (app()->environment('production')) {
            $this->command->error('DatosPruebaSeeder no corre en producción.');

            return;
        }

        $manager = ManagerPlataforma::create([
            'full_name' => 'Manager de prueba',
            'email' => 'manager@goharvey.test',
        ]);

        // Superadmin: sin empresa, atraviesa el scope.
        Usuario::create([
            'manager_id' => $manager->id,
            'company_id' => null,
            'role_id' => 2, // superadmin
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'super@goharvey.test',
            'password_hash' => Hash::make(self::CLAVE),
            'status' => 'active',
        ]);

        foreach ([['Empresa A', 'empresa-a'], ['Empresa B', 'empresa-b']] as [$nombre, $slug]) {
            $this->crearEmpresa($manager->id, $nombre, $slug, $cifrado);
        }

        $this->command->info('Datos de prueba cargados. Contraseña de todos: ' . self::CLAVE);
    }

    private function crearEmpresa(int $managerId, string $nombre, string $slug, ServicioCifrado $cifrado): void
    {
        $empresa = Empresa::create([
            'manager_id' => $managerId,
            'name' => $nombre,
            'email' => "contacto@{$slug}.test",
            'slug' => $slug,
            'status' => 'active',
        ]);

        $sucursal = Sucursal::create([
            'company_id' => $empresa->id,
            'name' => 'Casa central',
            'internal_code' => 'CC',
            'is_headquarter' => true,
            'is_active' => true,
        ]);

        // Un usuario de cada rol de empresa.
        foreach ([
            [3, 'admin', 'Admin', 'Principal'],
            [1, 'gestor', 'Gestor', 'Uno'],
            [4, 'investigador', 'Investigador', 'Externo'],
        ] as [$rolId, $prefijo, $nombrePila, $apellido]) {
            Usuario::create([
                'company_id' => $empresa->id,
                'role_id' => $rolId,
                'first_name' => $nombrePila,
                'last_name' => $apellido,
                'email' => "{$prefijo}@{$slug}.test",
                'password_hash' => Hash::make(self::CLAVE),
                'status' => 'active',
            ]);
        }

        // Denuncias de ejemplo, una por estado inicial.
        foreach ([EstadoDenuncia::Nuevo, EstadoDenuncia::Vista, EstadoDenuncia::EnProgreso] as $i => $estado) {
            $codigo = strtoupper(substr($slug, -1)) . '-CC-'
                . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT) . '-'
                . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            Denuncia::create([
                'company_id' => $empresa->id,
                'internal_code' => $codigo,
                'is_anonymous' => $i % 2 === 0,
                'intake_channel' => 'web',
                'submission_status' => 'confirmed',
                'category_id' => 1,
                'relationship_id' => 1,
                'branch_id' => $sucursal->id,
                'priority' => 'medium',
                'status' => $estado,
                'public_status' => $estado->estadoPublico(),
            ])->forceFill([
                'tracking_code_hash' => hash('sha256', $codigo),
                'encryption_salt' => $cifrado->generarSalt(),
            ])->save();
        }
    }
}
