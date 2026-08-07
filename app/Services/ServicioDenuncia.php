<?php

namespace App\Services;

use App\Enums\EstadoDenuncia;
use App\Enums\PrioridadDenuncia;
use App\Models\Asignacion;
use App\Models\Denuncia;
use App\Models\EventoDenuncia;
use App\Models\HistorialEstado;
use App\Models\NotificacionUsuario;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Operaciones de dominio sobre denuncias.
 *
 * Todas van dentro de una transaccion, con el registro de auditoria
 * ADENTRO y con registrar() (que lanza), no registrarSeguro(). Es la
 * convencion de 6.2 del brief: si no se puede dejar traza del cambio, el
 * cambio no ocurre.
 */
class ServicioDenuncia
{
    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    /**
     * Marca la denuncia como vista al abrirla por primera vez.
     *
     * -- POR QUE ESTO ESTABA ROTO --
     *
     * ver_denuncia.php hacia el UPDATE a 'seen' y despues un INSERT en
     * complaint_status_history con columnas que no existen (old_status,
     * new_status, changed_at; la tabla tiene from_status, to_status,
     * created_at, y ademas company_id NOT NULL que el INSERT no mencionaba).
     *
     * El INSERT fallaba, el catch hacia rollBack() y el UPDATE se revertia
     * con el. Pero el codigo actualizaba igual el array en memoria, asi que
     * la pantalla mostraba "Vista" sobre una base que seguia diciendo 'new'.
     *
     * Consecuencia: ninguna transicion quedo registrada, y como el reporte
     * de plazo incumplido mide "24 h sin pasar de new a seen", toda
     * denuncia figuraba incumplida a perpetuidad.
     */
    public function marcarComoVista(Denuncia $denuncia): bool
    {
        if ($denuncia->status !== EstadoDenuncia::Nuevo) {
            return false;
        }

        return DB::transaction(function () use ($denuncia) {
            $fresca = Denuncia::withoutGlobalScopes()->lockForUpdate()->find($denuncia->id);

            if (! $fresca || $fresca->status !== EstadoDenuncia::Nuevo) {
                return false;
            }

            $this->aplicarEstado($fresca, EstadoDenuncia::Vista);
            $fresca->save();

            HistorialEstado::create([
                'company_id' => $fresca->company_id,
                'complaint_id' => $fresca->id,
                'from_status' => EstadoDenuncia::Nuevo->value,
                'to_status' => EstadoDenuncia::Vista->value,
                'changed_by_user_id' => auth()->id(),
                'reason' => 'Marcada automaticamente al abrirse',
            ]);

            $this->auditoria->registrar([
                'company_id' => $fresca->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'complaint.seen',
                'entity_type' => 'complaint',
                'entity_id' => $fresca->id,
                'affected_complaint_id' => $fresca->id,
                'result' => 'success',
                'detail' => 'Denuncia ' . $fresca->internal_code . ' marcada como vista',
            ]);

            $denuncia->setRawAttributes($fresca->getAttributes(), true);

            return true;
        });
    }

    /**
     * Cambia el estado, validando la transicion.
     *
     * El sistema anterior no tenia maquina de estados: cualquier estado de
     * una lista blanca saltaba a cualquier otro sin validacion. Y encima
     * habia dos implementaciones incompatibles -- cambiar_estado.php
     * escribia solo public_status con una lista que incluia 'rejected'
     * (inexistente) y omitia 'seen'.
     *
     * @throws RuntimeException si la transicion no es valida.
     */
    public function cambiarEstado(Denuncia $denuncia, EstadoDenuncia $nuevo, ?string $razon = null): void
    {
        DB::transaction(function () use ($denuncia, $nuevo, $razon) {
            $fresca = Denuncia::withoutGlobalScopes()->lockForUpdate()->findOrFail($denuncia->id);
            $anterior = $fresca->status;

            if ($anterior === $nuevo) {
                return;
            }

            if (! $anterior->puedePasarA($nuevo)) {
                throw new RuntimeException(
                    "No se puede pasar de {$anterior->etiqueta()} a {$nuevo->etiqueta()}."
                );
            }

            $esReapertura = $anterior->esReapertura($nuevo);

            $this->aplicarEstado($fresca, $nuevo);

            // Al reabrir se limpian las marcas de cierre: si quedaran,
            // los reportes de tiempo promedio de resolucion mentirian.
            if ($esReapertura) {
                $fresca->resolved_at = null;
                $fresca->closed_at = null;
                $fresca->archived_at = null;
            }

            $fresca->save();

            HistorialEstado::create([
                'company_id' => $fresca->company_id,
                'complaint_id' => $fresca->id,
                'from_status' => $anterior->value,
                'to_status' => $nuevo->value,
                'changed_by_user_id' => auth()->id(),
                'reason' => $razon ?: null,
            ]);

            $this->auditoria->registrar([
                'company_id' => $fresca->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'complaint.status_changed',
                'entity_type' => 'complaint',
                'entity_id' => $fresca->id,
                'affected_complaint_id' => $fresca->id,
                'result' => 'success',
                'detail' => sprintf(
                    'Estado de %s: %s -> %s%s',
                    $fresca->internal_code,
                    $anterior->value,
                    $nuevo->value,
                    $esReapertura ? ' (reapertura)' : ''
                ),
            ]);

            $denuncia->setRawAttributes($fresca->getAttributes(), true);
        });
    }

    public function cambiarPrioridad(Denuncia $denuncia, PrioridadDenuncia $nueva): void
    {
        DB::transaction(function () use ($denuncia, $nueva) {
            $fresca = Denuncia::withoutGlobalScopes()->lockForUpdate()->findOrFail($denuncia->id);
            $anterior = $fresca->priority;

            if ($anterior === $nueva) {
                return;
            }

            $fresca->priority = $nueva;
            $fresca->last_action_at = now();
            $fresca->save();

            EventoDenuncia::create([
                'company_id' => $fresca->company_id,
                'complaint_id' => $fresca->id,
                'user_id' => auth()->id(),
                'event_type' => 'priority_changed',
                'payload' => ['from' => $anterior->value, 'to' => $nueva->value],
            ]);

            $this->auditoria->registrar([
                'company_id' => $fresca->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'complaint.priority_changed',
                'entity_type' => 'complaint',
                'entity_id' => $fresca->id,
                'affected_complaint_id' => $fresca->id,
                'result' => 'success',
                'detail' => "Prioridad de {$fresca->internal_code}: {$anterior->value} -> {$nueva->value}",
            ]);

            $denuncia->setRawAttributes($fresca->getAttributes(), true);
        });
    }

    /**
     * Sincroniza las asignaciones activas con la lista recibida.
     *
     * -- H-010 --
     *
     * complaint_assignments es la fuente de verdad;
     * complaints.assigned_to_user_id es denormalizacion para los indices
     * de listado. La clave es que las dos se escriben en la MISMA
     * transaccion: por eso las lecturas (Policy, listado, dashboard) usan
     * solo la tabla, sin el UNION de respaldo que el original necesitaba
     * ni la funcion repararSincronizacionAsignacion(), que ademas nunca
     * funciono porque ordenaba por una columna inexistente (assigned_at).
     *
     * @param  array<int>  $idsUsuarios
     */
    public function sincronizarAsignaciones(Denuncia $denuncia, array $idsUsuarios, ?string $razon = null): void
    {
        $idsUsuarios = array_values(array_unique(array_map('intval', $idsUsuarios)));

        DB::transaction(function () use ($denuncia, $idsUsuarios, $razon) {
            $vigentes = Asignacion::where('complaint_id', $denuncia->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->pluck('assigned_to_user_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $aQuitar = array_diff($vigentes, $idsUsuarios);
            $aAgregar = array_diff($idsUsuarios, $vigentes);

            if (! $aQuitar && ! $aAgregar) {
                return;
            }

            if ($aQuitar) {
                Asignacion::where('complaint_id', $denuncia->id)
                    ->whereNull('ended_at')
                    ->whereIn('assigned_to_user_id', $aQuitar)
                    ->update(['ended_at' => now()]);
            }

            foreach ($aAgregar as $idUsuario) {
                Asignacion::create([
                    'company_id' => $denuncia->company_id,
                    'complaint_id' => $denuncia->id,
                    'assigned_to_user_id' => $idUsuario,
                    'assigned_by_user_id' => auth()->id(),
                    'reason' => $razon ?: null,
                ]);

                NotificacionUsuario::create([
                    'company_id' => $denuncia->company_id,
                    'complaint_id' => $denuncia->id,
                    'user_id' => $idUsuario,
                    'type' => 'assigned',
                    'status' => 'pending',
                ]);
            }

            // Denormalizacion, en la misma transaccion.
            Denuncia::withoutGlobalScopes()
                ->where('id', $denuncia->id)
                ->update([
                    'assigned_to_user_id' => $idsUsuarios[0] ?? null,
                    'last_action_at' => now(),
                ]);

            $nombres = Usuario::whereIn('id', array_merge($aAgregar, $aQuitar))
                ->get(['id', 'first_name', 'last_name'])
                ->mapWithKeys(fn ($u) => [$u->id => $u->nombreCompleto()]);

            EventoDenuncia::create([
                'company_id' => $denuncia->company_id,
                'complaint_id' => $denuncia->id,
                'user_id' => auth()->id(),
                'event_type' => 'assigned',
                'payload' => [
                    'asignados' => array_values(array_map(fn ($id) => $nombres[$id] ?? "ID:$id", $aAgregar)),
                    'desasignados' => array_values(array_map(fn ($id) => $nombres[$id] ?? "ID:$id", $aQuitar)),
                ],
            ]);

            $this->auditoria->registrar([
                'company_id' => $denuncia->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'complaint.assigned',
                'entity_type' => 'complaint',
                'entity_id' => $denuncia->id,
                'affected_complaint_id' => $denuncia->id,
                'result' => 'success',
                'detail' => sprintf(
                    'Asignacion de %s · +[%s] -[%s]',
                    $denuncia->internal_code,
                    implode(', ', $aAgregar),
                    implode(', ', $aQuitar)
                ),
            ]);
        });
    }

    /**
     * Analistas que pueden recibir esta denuncia.
     *
     * Gestores: de la misma empresa, y si la denuncia tiene sucursal, con
     * esa sucursal asignada -- o sin ninguna sucursal asignada, que
     * significa "ve todas". Investigadores externos: sin restriccion de
     * empresa ni sucursal. Portado literal.
     */
    public function analistasDisponibles(Denuncia $denuncia)
    {
        $gestores = Usuario::with('rol')
            ->where('status', 'active')
            ->where('company_id', $denuncia->company_id)
            ->whereHas('rol', fn ($q) => $q->where('name', 'gestor'))
            ->when($denuncia->branch_id, fn ($q) => $q->where(function ($sub) use ($denuncia) {
                $sub->whereHas('sucursales', fn ($s) => $s->where('branches.id', $denuncia->branch_id))
                    ->orWhereDoesntHave('sucursales');
            }))
            ->orderBy('first_name')
            ->get();

        $investigadores = Usuario::with('rol')
            ->where('status', 'active')
            ->whereHas('rol', fn ($q) => $q->where('name', 'investigador_externo'))
            ->orderBy('first_name')
            ->get();

        return $gestores->concat($investigadores);
    }

    /** Aplica el estado interno y su reflejo publico, mas los sellos de tiempo. */
    private function aplicarEstado(Denuncia $denuncia, EstadoDenuncia $nuevo): void
    {
        $denuncia->status = $nuevo;
        $denuncia->public_status = $nuevo->estadoPublico();
        $denuncia->last_action_at = now();

        match ($nuevo) {
            EstadoDenuncia::Resuelta => $denuncia->resolved_at = now(),
            EstadoDenuncia::Cerrada => $denuncia->closed_at = now(),
            EstadoDenuncia::Archivada => $denuncia->archived_at = now(),
            default => null,
        };
    }
}
