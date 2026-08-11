<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Onboarding\DatosFiscalesOnboarding;
use App\Models\Onboarding\EmpresaOnboarding;
use App\Models\Onboarding\SucursalOnboarding;
use App\Models\Onboarding\TokenOnboarding;
use App\Models\Onboarding\UsuarioOnboarding;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Borrador de alta que carga la empresa desde el formulario publico.
 *
 * Escribe en las cinco tablas espejo. Nada de esto toca las tablas reales
 * hasta que el superadmin confirma.
 */
class ServicioBorradorOnboarding
{
    /** Paso en el que esta el borrador, derivado de la base. */
    public function pasoActual(TokenOnboarding $token): int
    {
        $borrador = $token->empresa;

        if (! $borrador) {
            return 1;
        }

        if (! $borrador->sucursales()->where('is_headquarter', true)->exists()) {
            return 1;
        }

        // Paso 2 confirmado = existe al menos un usuario, o el borrador ya
        // paso por ahi. El original usaba $_SESSION para esto, asi que
        // cerrar el navegador hacia perder el paso.
        if (! $borrador->usuarios()->exists()) {
            return $token->paso2_confirmado ? 3 : 2;
        }

        return 4;
    }

    public function guardarPaso1(TokenOnboarding $token, array $datos): void
    {
        DB::transaction(function () use ($token, $datos) {
            $borrador = EmpresaOnboarding::updateOrCreate(
                ['token_id' => $token->id],
                [
                    'name' => $datos['nombre'],
                    'email' => $datos['email'],
                    'slug' => $this->slugDisponible($datos['nombre'], $token->id),
                    'default_language' => 'es',
                    'timezone' => 'America/Argentina/Buenos_Aires',
                    'retention_complaints_days' => 3660,
                    'retention_files_days' => 3660,
                    'retention_logs_days' => 365,
                ]
            );

            SucursalOnboarding::updateOrCreate(
                ['token_id' => $token->id, 'is_headquarter' => true],
                [
                    'company_onboarding_id' => $borrador->id,
                    'name' => $datos['sede_nombre'],
                    'address' => $datos['sede_direccion'] ?: null,
                    'display_order' => 1,
                ]
            );

            DatosFiscalesOnboarding::updateOrCreate(
                ['token_id' => $token->id],
                [
                    'company_onboarding_id' => $borrador->id,
                    'tax_id' => $datos['tax_id'] ?: null,
                    'legal_name' => $datos['legal_name'] ?: null,
                    'vat_status' => $datos['vat_status'] ?: null,
                    'fiscal_address' => $datos['fiscal_address'] ?: null,
                    'billing_emails' => $datos['billing_emails'] ?: null,
                    'uses_global_price' => true,
                    'billing_day' => (int) now()->format('j'),
                    'preferred_payment' => 'bank_transfer',
                ]
            );
        });
    }

    /** @param  array<array{nombre:string, direccion:?string}>  $sucursales */
    public function guardarPaso2(TokenOnboarding $token, array $sucursales): void
    {
        DB::transaction(function () use ($token, $sucursales) {
            $borrador = $token->empresa;

            // Se reemplazan todas las adicionales: la sede central no se toca.
            SucursalOnboarding::where('token_id', $token->id)
                ->where('is_headquarter', false)
                ->delete();

            $orden = 2;

            foreach ($sucursales as $sucursal) {
                if (empty($sucursal['nombre'])) {
                    continue;
                }

                SucursalOnboarding::create([
                    'token_id' => $token->id,
                    'company_onboarding_id' => $borrador->id,
                    'name' => $sucursal['nombre'],
                    'address' => $sucursal['direccion'] ?: null,
                    'is_headquarter' => false,
                    'display_order' => $orden++,
                ]);
            }

            // Se marca en la base y no en la sesion: cerrar el navegador
            // no debe hacer perder el paso.
            $token->paso2_confirmado = true;
            $token->save();
        });
    }

    /** @param  array<array{nombre:string, apellido:string, email:string, password:string, rol:string}>  $usuarios */
    public function guardarPaso3(TokenOnboarding $token, array $usuarios): void
    {
        DB::transaction(function () use ($token, $usuarios) {
            $borrador = $token->empresa;

            UsuarioOnboarding::where('token_id', $token->id)->delete();

            foreach ($usuarios as $usuario) {
                UsuarioOnboarding::create([
                    'token_id' => $token->id,
                    'company_onboarding_id' => $borrador->id,
                    'first_name' => $usuario['nombre'],
                    'last_name' => $usuario['apellido'],
                    'email' => mb_strtolower($usuario['email']),
                    'password_hash' => Hash::make($usuario['password']),
                    'role' => $usuario['rol'],
                ]);
            }
        });
    }

    /**
     * Cierra el borrador y avisa a los superadmins.
     *
     * El token pasa a 'completed': la empresa termino y ahora falta que el
     * superadmin revise y confirme. Recien en la confirmacion se crean los
     * registros reales y se borran los espejo.
     */
    public function confirmarEnvio(TokenOnboarding $token): void
    {
        DB::transaction(function () use ($token) {
            $token->status = 'completed';
            $token->used_at = now();
            $token->save();

            $nombreEmpresa = $token->empresa?->name ?? 'Nueva empresa';

            $superadmins = Usuario::whereHas('rol', fn ($q) => $q->where('name', 'superadmin'))
                ->where('status', 'active')
                ->pluck('id');

            foreach ($superadmins as $idSuperadmin) {
                DB::table('user_notifications')->insert([
                    'company_id' => null,
                    'user_id' => $idSuperadmin,
                    'complaint_id' => null,
                    'type' => 'onboarding_completed',
                    'status' => 'pending',
                    'metadata' => json_encode([
                        'empresa' => $nombreEmpresa,
                        'token_id' => $token->id,
                    ]),
                    'created_at' => now(),
                ]);
            }
        });
    }

    /** Vuelve a un paso anterior, descartando lo que venia despues. */
    public function volverA(TokenOnboarding $token, int $paso): void
    {
        DB::transaction(function () use ($token, $paso) {
            if ($paso <= 1) {
                $token->empresa?->delete();  // cascada: sucursales, usuarios, fiscales
                $token->paso2_confirmado = false;
                $token->save();

                return;
            }

            if ($paso === 2) {
                UsuarioOnboarding::where('token_id', $token->id)->delete();
                SucursalOnboarding::where('token_id', $token->id)
                    ->where('is_headquarter', false)
                    ->delete();
                $token->paso2_confirmado = false;
                $token->save();

                return;
            }

            UsuarioOnboarding::where('token_id', $token->id)->delete();
        });
    }

    /**
     * Slug libre, derivado del nombre.
     *
     * Se verifica contra companies Y companies_onboarding. El original
     * miraba solo el borrador, asi que una empresa nueva cuyo nombre
     * generaba el mismo slug que una empresa existente pasaba el
     * formulario sin problema y el alta explotaba despues, cuando el
     * superadmin confirmaba, con un error de base.
     */
    private function slugDisponible(string $nombre, int $tokenId): string
    {
        $base = Str::slug($nombre) ?: 'empresa';
        $slug = $base;
        $sufijo = 1;

        while ($this->slugOcupado($slug, $tokenId)) {
            $slug = $base . '-' . (++$sufijo);
        }

        return mb_substr($slug, 0, 100);
    }

    private function slugOcupado(string $slug, int $tokenId): bool
    {
        if (Empresa::where('slug', $slug)->exists()) {
            return true;
        }

        return EmpresaOnboarding::where('slug', $slug)
            ->where('token_id', '!=', $tokenId)
            ->exists();
    }
}
