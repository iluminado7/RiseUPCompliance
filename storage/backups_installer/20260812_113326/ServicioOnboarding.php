<?php

namespace App\Services;

use App\Enums\EstadoEmpresa;
use App\Models\DatosFiscales;
use App\Models\Empresa;
use App\Models\Onboarding\TokenOnboarding;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Alta de empresas por invitacion.
 *
 * El superadmin genera un token con vencimiento de 48 horas y se lo envia
 * a la empresa. El formulario publico guarda los datos en las cinco tablas
 * espejo, y recien al confirmar se crean los registros reales.
 */
class ServicioOnboarding
{
    private const HORAS_VIGENCIA = 48;

    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    public function generarToken(): TokenOnboarding
    {
        return DB::transaction(function () {
            $token = TokenOnboarding::create([
                'token' => bin2hex(random_bytes(32)),
                'created_by' => auth()->id(),
                'expires_at' => now()->addHours(self::HORAS_VIGENCIA),
                'status' => 'pending',
            ]);

            $this->auditoria->registrar([
                'company_id' => null,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'onboarding.token_created',
                'entity_type' => 'onboarding_token',
                'entity_id' => $token->id,
                'result' => 'success',
                'detail' => 'Link de onboarding generado · vence ' . $token->expires_at->format('d/m/Y H:i'),
            ]);

            return $token;
        });
    }

    public function revocar(TokenOnboarding $token): void
    {
        DB::transaction(function () use ($token) {
            if ($token->status !== 'pending') {
                return;
            }

            $token->status = 'expired';
            $token->save();

            $this->auditoria->registrar([
                'company_id' => null,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'onboarding.token_revoked',
                'entity_type' => 'onboarding_token',
                'entity_id' => $token->id,
                'result' => 'success',
            ]);
        });
    }

    /**
     * Confirma el alta: copia las tablas espejo a las reales.
     *
     * @param  array  $ediciones  Correcciones que hizo el superadmin al revisar.
     *
     * @throws RuntimeException
     */
    public function confirmarAlta(TokenOnboarding $token, array $ediciones = []): Empresa
    {
        $token->load(['empresa.sucursales', 'empresa.usuarios', 'empresa.datosFiscales']);

        $borrador = $token->empresa;

        if (! $borrador) {
            throw new RuntimeException('La empresa todavía no completó el formulario.');
        }

        if ($borrador->sucursales->isEmpty()) {
            throw new RuntimeException('El alta no tiene ninguna sucursal cargada.');
        }

        if ($borrador->usuarios->isEmpty()) {
            throw new RuntimeException('El alta no tiene ningún usuario cargado.');
        }

        if ($token->status !== 'completed') {
            throw new RuntimeException('La empresa todavía no envió el formulario.');
        }

        $managerId = $this->resolverManager();

        return DB::transaction(function () use ($token, $borrador, $ediciones, $managerId) {
            $empresa = Empresa::create([
                'manager_id' => $managerId,
                'name' => $ediciones['name'] ?? $borrador->name,
                'email' => $ediciones['email'] ?? $borrador->email,
                'slug' => $ediciones['slug'] ?? $borrador->slug,
                'status' => EstadoEmpresa::Activa->value,
                'default_language' => $ediciones['default_language'] ?? $borrador->default_language,
                'timezone' => $ediciones['timezone'] ?? $borrador->timezone,
                'complaints_retention_days' => $ediciones['complaints_retention_days'] ?? $borrador->retention_complaints_days,
                'files_retention_days' => $ediciones['files_retention_days'] ?? $borrador->retention_files_days,
                'logs_retention_days' => $ediciones['logs_retention_days'] ?? $borrador->retention_logs_days,
            ]);

            foreach ($borrador->sucursales as $sucursalBorrador) {
                Sucursal::create([
                    'company_id' => $empresa->id,
                    'name' => $sucursalBorrador->name,
                    'internal_code' => $sucursalBorrador->internal_code ?: null,
                    'address' => $sucursalBorrador->address ?: null,
                    'is_headquarter' => $sucursalBorrador->is_headquarter,
                    'is_active' => true,
                ]);
            }

            if ($fiscalesBorrador = $borrador->datosFiscales) {
                DatosFiscales::create([
                    'company_id' => $empresa->id,
                    'tax_id' => $fiscalesBorrador->tax_id,
                    'legal_name' => $fiscalesBorrador->legal_name,
                    'vat_status' => $fiscalesBorrador->vat_status,
                    'fiscal_address' => $fiscalesBorrador->fiscal_address,
                    'billing_emails' => $fiscalesBorrador->billing_emails,
                    'uses_global_price' => $fiscalesBorrador->uses_global_price,
                    'custom_amount' => $fiscalesBorrador->custom_amount,
                    'billing_day' => $fiscalesBorrador->billing_day,
                    'preferred_payment' => $fiscalesBorrador->preferred_payment,
                ]);
            }

            $roles = Rol::pluck('id', 'name');
            $sucursalesIds = Sucursal::withoutGlobalScopes()
                ->where('company_id', $empresa->id)
                ->pluck('id')
                ->all();

            foreach ($borrador->usuarios as $usuarioBorrador) {
                $usuario = new Usuario([
                    'company_id' => $empresa->id,
                    'role_id' => $roles[$usuarioBorrador->role] ?? $roles['gestor'],
                    'first_name' => $usuarioBorrador->first_name,
                    'last_name' => $usuarioBorrador->last_name,
                    'email' => mb_strtolower($usuarioBorrador->email),
                ]);

                // La contrasena que la persona cargo en el formulario se
                // conserva, pero se marca para cambio obligatorio: viajo
                // por un formulario web que completo otra persona de la
                // empresa, y hasta que la cambie no es solo suya.
                $usuario->password_hash = $usuarioBorrador->password_hash;
                $usuario->status = 'active';
                $usuario->must_change_password = true;
                $usuario->save();

                // El admin principal recibe todas las sucursales.
                if ($usuarioBorrador->role === 'admin_principal') {
                    $usuario->sucursales()->sync($sucursalesIds);
                }
            }

            $token->status = 'completed';
            $token->used_at = now();
            $token->save();

            // El registro va DENTRO de la transaccion. En el original iba
            // despues del commit: si fallaba, el alta quedaba sin traza.
            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'company.onboarding_confirmed',
                'entity_type' => 'company',
                'entity_id' => $empresa->id,
                'result' => 'success',
                'detail' => sprintf(
                    'Alta confirmada desde onboarding · %s · %d sucursal(es), %d usuario(s)',
                    $empresa->name,
                    $borrador->sucursales->count(),
                    $borrador->usuarios->count()
                ),
            ]);

            // Los datos espejo se borran: contienen hashes de contrasena y
            // ya cumplieron su funcion. El FK en cascada desde
            // companies_onboarding se lleva sucursales, usuarios y fiscales.
            $borrador->delete();

            return $empresa;
        });
    }

    /**
     * Responsable interno al que se asocia la empresa nueva.
     *
     * companies.manager_id apunta a tenant_managers, NO a users: es la
     * trampa del schema. Sale de users.manager_id del superadmin.
     *
     * El original, si no lo encontraba, creaba un tenant_manager al vuelo
     * con el email del superadmin y actualizaba su users.manager_id --
     * dentro de la transaccion del alta y con cuatro error_log de debug.
     * Crear entidades de plataforma como efecto colateral de un alta de
     * empresa es difícil de auditar: aca falla con un mensaje claro y el
     * vinculo se arregla desde Administracion.
     */
    private function resolverManager(): int
    {
        $managerId = auth()->user()->manager_id;

        if (! $managerId) {
            throw new RuntimeException(
                'Tu usuario no tiene un responsable interno vinculado. '
                . 'Sin eso no se puede dar de alta la empresa.'
            );
        }

        return (int) $managerId;
    }
}
