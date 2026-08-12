<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Factura;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Gestion de facturas.
 *
 * PENDIENTE: la generacion automatica. Depende de definiciones
 * comerciales que todavia no existen -- que se cobra, cuando se emite, y
 * como juegan company_fiscal_data.uses_global_price y custom_amount, que
 * el schema ya tiene previstos.
 *
 * Todo lo de aca queda auditado: una factura es un documento con efectos
 * legales y fiscales, y quien la creo o la marco pagada tiene que poder
 * establecerse.
 */
class ServicioFacturacion
{
    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    public function crear(Empresa $empresa, array $datos): Factura
    {
        return DB::transaction(function () use ($empresa, $datos) {
            $existe = Factura::withoutGlobalScopes()
                ->where('company_id', $empresa->id)
                ->where('billing_year', $datos['billing_year'])
                ->where('billing_month', $datos['billing_month'])
                ->exists();

            if ($existe) {
                throw new RuntimeException(
                    'Ya existe una factura de esa empresa para ese período.'
                );
            }

            $factura = Factura::create($datos + [
                'company_id' => $empresa->id,
                'payment_status' => 'pending',
            ]);

            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'invoice.created',
                'entity_type' => 'invoice',
                'entity_id' => $factura->id,
                'result' => 'success',
                'detail' => sprintf(
                    'Factura %02d/%d de %s por %s %s',
                    $factura->billing_month,
                    $factura->billing_year,
                    $empresa->name,
                    $factura->currency_code,
                    $factura->total_amount
                ),
            ]);

            return $factura;
        });
    }

    public function actualizar(Factura $factura, array $datos): void
    {
        DB::transaction(function () use ($factura, $datos) {
            $antes = $factura->only(array_keys($datos));

            $factura->fill($datos)->save();

            $cambios = array_keys(array_diff_assoc(
                array_map(fn ($v) => (string) $v, $datos),
                array_map(fn ($v) => (string) $v, $antes)
            ));

            $this->auditoria->registrar([
                'company_id' => $factura->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'invoice.updated',
                'entity_type' => 'invoice',
                'entity_id' => $factura->id,
                'result' => 'success',
                'detail' => 'Factura editada · campos: '
                    . ($cambios ? implode(', ', $cambios) : 'ninguno'),
            ]);
        });
    }

    /**
     * Registra el pago de una factura.
     *
     * Es la operacion con mas consecuencias de esta pantalla: marca como
     * saldada una deuda. El registro va dentro de la transaccion y con
     * registrar(), que lanza -- si no se puede dejar traza, el pago no se
     * asienta (6.2 del brief).
     */
    public function registrarPago(Factura $factura, string $fecha): void
    {
        DB::transaction(function () use ($factura, $fecha) {
            if ($factura->payment_status === 'paid') {
                return;
            }

            $factura->payment_status = 'paid';
            $factura->payment_date = $fecha;
            $factura->save();

            $this->auditoria->registrar([
                'company_id' => $factura->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'invoice.paid',
                'entity_type' => 'invoice',
                'entity_id' => $factura->id,
                'result' => 'success',
                'detail' => sprintf(
                    'Pago registrado · factura %02d/%d · %s',
                    $factura->billing_month,
                    $factura->billing_year,
                    $fecha
                ),
            ]);
        });
    }

    public function anular(Factura $factura, string $motivo): void
    {
        DB::transaction(function () use ($factura, $motivo) {
            $factura->payment_status = 'cancelled';
            $factura->save();

            $this->auditoria->registrar([
                'company_id' => $factura->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'invoice.cancelled',
                'entity_type' => 'invoice',
                'entity_id' => $factura->id,
                'result' => 'success',
                'detail' => sprintf(
                    'Factura %02d/%d anulada · motivo: %s',
                    $factura->billing_month,
                    $factura->billing_year,
                    $motivo
                ),
            ]);
        });
    }

    /**
     * Marca como vencidas las facturas pendientes cuyo vencimiento ya paso.
     *
     * Se llama al abrir la pantalla. No hay proceso automatico todavia:
     * cuando exista el scheduler, esto va a un comando diario.
     */
    public function actualizarVencidas(?int $empresaId = null): int
    {
        return Factura::withoutGlobalScopes()
            ->when($empresaId, fn ($q, $id) => $q->where('company_id', $id))
            ->where('payment_status', 'pending')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['payment_status' => 'overdue']);
    }
}
