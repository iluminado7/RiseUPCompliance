<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoDenuncia;
use App\Enums\PrioridadDenuncia;
use App\Http\Controllers\Controller;
use App\Models\Denuncia;
use App\Models\NotaInterna;
use App\Services\ServicioChat;
use App\Services\ServicioDenuncia;
use App\Services\ServicioNota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

/**
 * Acciones POST sobre una denuncia.
 *
 * El original resolvia todo con un solo endpoint y un campo `accion` que
 * ramificaba en ocho ifs. Aca cada accion es una ruta con su propia
 * validacion y su propio chequeo de Policy, que es lo que permite que el
 * middleware de autorizacion haga su trabajo.
 */
class AccionDenunciaController extends Controller
{
    public function __construct(
        private readonly ServicioDenuncia $denuncias,
        private readonly ServicioNota $notas,
        private readonly ServicioChat $chat,
    ) {}

    public function cambiarEstado(Request $request, Denuncia $denuncia): RedirectResponse
    {
        $this->authorize('cambiarEstado', $denuncia);

        $datos = $request->validate([
            'nuevo_estado' => ['required', Rule::enum(EstadoDenuncia::class)],
            'razon' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->denuncias->cambiarEstado(
                $denuncia,
                EstadoDenuncia::from($datos['nuevo_estado']),
                $datos['razon'] ?? null
            );
        } catch (RuntimeException $e) {
            return $this->volver($request, $denuncia)->with('error', $e->getMessage());
        }

        return $this->volver($request, $denuncia)->with('estado', 'Estado actualizado.');
    }

    public function cambiarPrioridad(Request $request, Denuncia $denuncia): RedirectResponse
    {
        $this->authorize('asignar', $denuncia);

        $datos = $request->validate([
            'nueva_prioridad' => ['required', Rule::enum(PrioridadDenuncia::class)],
        ]);

        $this->denuncias->cambiarPrioridad(
            $denuncia,
            PrioridadDenuncia::from($datos['nueva_prioridad'])
        );

        return $this->volver($request, $denuncia)->with('estado', 'Prioridad actualizada.');
    }

    public function asignar(Request $request, Denuncia $denuncia): RedirectResponse
    {
        $this->authorize('asignar', $denuncia);

        $datos = $request->validate([
            'analista_ids' => ['nullable', 'array'],
            'analista_ids.*' => ['integer'],
            'razon_asignacion' => ['nullable', 'string', 'max:500'],
        ]);

        // Solo se aceptan analistas de la lista habilitada para ESTA
        // denuncia. Sin esto, un id inyectado en el POST asignaria a
        // cualquier usuario del sistema, incluso de otra empresa.
        $habilitados = $this->denuncias->analistasDisponibles($denuncia)->pluck('id')->all();
        $solicitados = array_intersect($datos['analista_ids'] ?? [], $habilitados);

        $this->denuncias->sincronizarAsignaciones(
            $denuncia,
            $solicitados,
            $datos['razon_asignacion'] ?? null
        );

        return $this->volver($request, $denuncia)->with('estado', 'Asignación actualizada.');
    }

    public function crearNota(Request $request, Denuncia $denuncia): RedirectResponse
    {
        $this->authorize('crearNota', $denuncia);

        $datos = $request->validate([
            'contenido' => ['required', 'string', 'max:5000'],
            'es_prioritaria' => ['nullable', 'boolean'],
        ]);

        $this->notas->crear($denuncia, $datos['contenido'], $request->boolean('es_prioritaria'));

        return $this->volver($request, $denuncia, 'notas')->with('estado', 'Nota guardada.');
    }

    public function editarNota(Request $request, Denuncia $denuncia, NotaInterna $nota): RedirectResponse
    {
        $this->verificarPertenencia($denuncia, $nota);
        $this->authorize('update', $nota);

        $datos = $request->validate([
            'contenido' => ['required', 'string', 'max:5000'],
            'es_prioritaria' => ['nullable', 'boolean'],
        ]);

        $this->notas->editar($nota, $datos['contenido'], $request->boolean('es_prioritaria'));

        return $this->volver($request, $denuncia, 'notas')->with('estado', 'Nota actualizada.');
    }

    public function eliminarNota(Request $request, Denuncia $denuncia, NotaInterna $nota): RedirectResponse
    {
        $this->verificarPertenencia($denuncia, $nota);
        $this->authorize('delete', $nota);

        $this->notas->eliminar($nota);

        return $this->volver($request, $denuncia, 'notas')->with('estado', 'Nota eliminada.');
    }

    public function enviarMensaje(Request $request, Denuncia $denuncia): RedirectResponse
    {
        $this->authorize('chatear', $denuncia);

        $datos = $request->validate([
            'contenido' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $this->chat->enviar($denuncia, $datos['contenido']);
        } catch (Throwable $e) {
            Log::error('[CHAT] No se pudo enviar el mensaje', [
                'denuncia' => $denuncia->id,
                'error' => $e->getMessage(),
            ]);

            return $this->volver($request, $denuncia, 'chat')
                ->with('error', 'No se pudo enviar el mensaje.');
        }

        return $this->volver($request, $denuncia, 'chat')->with('estado', 'Mensaje enviado.');
    }

    /**
     * La nota tiene que ser de esta denuncia.
     *
     * Sin esto, el route model binding aceptaria el id de una nota de otra
     * denuncia -- y como NotaInterna lleva TenantScope, seria de la misma
     * empresa pero de otro caso.
     */
    private function verificarPertenencia(Denuncia $denuncia, NotaInterna $nota): void
    {
        abort_unless($nota->complaint_id === $denuncia->id, 404);
    }

    private function volver(Request $request, Denuncia $denuncia, string $tab = 'info'): RedirectResponse
    {
        $parametros = array_filter([
            'tab' => $tab === 'info' ? null : $tab,
            'volver' => $request->input('volver') ?: null,
        ]);

        return redirect()->to(
            route('admin.denuncias.show', $denuncia)
            . ($parametros ? '?' . http_build_query($parametros) : '')
        );
    }
}
