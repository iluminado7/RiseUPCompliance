<?php

namespace App\Services;

use App\Models\Sucursal;
use Illuminate\Support\Facades\DB;

class ServicioSucursal
{
    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    public function crear(int $empresaId, array $datos): Sucursal
    {
        return DB::transaction(function () use ($empresaId, $datos) {
            if (! empty($datos['is_headquarter'])) {
                $this->desmarcarSedeCentral($empresaId);
            }

            $sucursal = Sucursal::create($datos + [
                'company_id' => $empresaId,
                'is_active' => true,
            ]);

            $this->auditoria->registrar([
                'company_id' => $empresaId,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'branch.created',
                'entity_type' => 'branch',
                'entity_id' => $sucursal->id,
                'result' => 'success',
                'detail' => "Alta de sucursal {$sucursal->name}",
            ]);

            return $sucursal;
        });
    }

    public function actualizar(Sucursal $sucursal, array $datos): void
    {
        DB::transaction(function () use ($sucursal, $datos) {
            // Una sola sede central por empresa. El original resolvia esto
            // con un UPDATE previo fuera de transaccion: si el INSERT
            // fallaba despues, la empresa quedaba sin ninguna sede central.
            if (! empty($datos['is_headquarter']) && ! $sucursal->is_headquarter) {
                $this->desmarcarSedeCentral($sucursal->company_id, $sucursal->id);
            }

            $sucursal->fill($datos)->save();

            $this->auditoria->registrar([
                'company_id' => $sucursal->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'branch.updated',
                'entity_type' => 'branch',
                'entity_id' => $sucursal->id,
                'result' => 'success',
                'detail' => "Edición de sucursal {$sucursal->name}",
            ]);
        });
    }

    private function desmarcarSedeCentral(int $empresaId, ?int $excepto = null): void
    {
        Sucursal::withoutGlobalScopes()
            ->where('company_id', $empresaId)
            ->when($excepto, fn ($q, $id) => $q->where('id', '!=', $id))
            ->update(['is_headquarter' => false]);
    }
}
