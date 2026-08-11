<?php

namespace App\Services;

use App\Enums\EstadoEmpresa;
use App\Models\DatosFiscales;
use App\Models\Empresa;
use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Alta, edicion y baja operativa de empresas.
 *
 * Las tres operaciones quedan auditadas. En administracion.php ninguna
 * llamaba a registrarAudit: crear una empresa, cambiarle el slug del canal
 * publico o desactivarla no dejaba ningun rastro en audit_logs.
 */
class ServicioEmpresa
{
    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    /**
     * Crea la empresa junto con su sede central y sus datos fiscales.
     *
     * El manager_id sale de users.manager_id del superadmin que la crea.
     * Es la relacion contraintuitiva del schema: companies.manager_id
     * apunta a tenant_managers, NO a users.
     */
    public function crear(array $datos, ?array $fiscales, array $sede): Empresa
    {
        $managerId = auth()->user()->manager_id;

        if (! $managerId) {
            throw new RuntimeException(
                'Tu usuario no tiene un responsable interno vinculado. '
                . 'Sin eso no se puede crear la empresa.'
            );
        }

        return DB::transaction(function () use ($datos, $fiscales, $sede, $managerId) {
            $empresa = Empresa::create($datos + [
                'manager_id' => $managerId,
                'status' => EstadoEmpresa::Activa->value,
            ]);

            Sucursal::create([
                'company_id' => $empresa->id,
                'name' => $sede['nombre'],
                'internal_code' => $sede['codigo'] ?: null,
                'address' => $sede['direccion'] ?: null,
                'is_headquarter' => true,
                'is_active' => true,
            ]);

            DatosFiscales::create(($fiscales ?? []) + ['company_id' => $empresa->id]);

            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'company.created',
                'entity_type' => 'company',
                'entity_id' => $empresa->id,
                'result' => 'success',
                'detail' => "Alta de empresa {$empresa->name} (slug: {$empresa->slug})",
            ]);

            return $empresa;
        });
    }

    public function actualizar(Empresa $empresa, array $datos, array $fiscales): void
    {
        DB::transaction(function () use ($empresa, $datos, $fiscales) {
            $antes = $empresa->only(array_keys($datos));

            $empresa->fill($datos)->save();

            // updateOrCreate en lugar del SELECT + IF del original: una
            // empresa puede no tener fila de datos fiscales todavia.
            DatosFiscales::updateOrCreate(
                ['company_id' => $empresa->id],
                $fiscales
            );

            $cambios = array_keys(array_diff_assoc($datos, $antes));

            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'company.updated',
                'entity_type' => 'company',
                'entity_id' => $empresa->id,
                'result' => 'success',
                'detail' => "Edición de {$empresa->name} · campos: "
                    . ($cambios ? implode(', ', $cambios) : 'solo datos fiscales'),
            ]);
        });
    }

    /**
     * Cambia el estado operativo.
     *
     * Es destructiva sin ser un borrado: desactivar deja a todos los
     * usuarios de la empresa afuera del panel en el siguiente request y
     * cierra su canal publico. Por eso el registro va DENTRO de la
     * transaccion y con registrar(), que lanza: si no se puede dejar
     * traza, el cambio no ocurre (6.2 del brief).
     */
    public function cambiarEstado(Empresa $empresa, EstadoEmpresa $nuevo): void
    {
        DB::transaction(function () use ($empresa, $nuevo) {
            $anterior = $empresa->status;

            if ($anterior === $nuevo) {
                return;
            }

            $empresa->status = $nuevo;
            $empresa->save();

            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'company.status_changed',
                'entity_type' => 'company',
                'entity_id' => $empresa->id,
                'result' => 'success',
                'detail' => "Estado de {$empresa->name}: {$anterior->value} -> {$nuevo->value}",
            ]);
        });
    }
}
