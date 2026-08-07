<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use App\Models\Denuncia;
use App\Services\ServicioAuditoria;
use App\Services\ServicioChat;
use App\Services\ServicioCifrado;
use App\Services\ServicioDenuncia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Vista de detalle de una denuncia.
 *
 * El aislamiento entre empresas lo resuelve TenantScope: una denuncia de
 * otra empresa no existe para este usuario, asi que el route model
 * binding devuelve 404 -- que es lo que pide 6.1 del brief. Un 403
 * confirmaria que el recurso existe.
 */
class DetalleDenunciaController extends Controller
{
    public function __construct(
        private readonly ServicioDenuncia $servicio,
        private readonly ServicioCifrado $cifrado,
        private readonly ServicioAuditoria $auditoria,
        private readonly ServicioChat $chat,
    ) {}

    public function show(Request $request, Denuncia $denuncia): View
    {
        $this->authorize('view', $denuncia);

        $this->servicio->marcarComoVista($denuncia);

        $denuncia->load([
            'empresa:id,name',
            'sucursal:id,name',
            'categoria:id,name_es',
            'vinculo:id,name_es',
            'area:id,name_es',
            'cargo:id,name_es',
            'denunciante',
            'respuestas',
            'asignaciones' => fn ($q) => $q->whereNull('ended_at')->with('asignadoA.rol'),
        ]);

        $usuario = $request->user();
        $puedeVerDenunciante = $usuario->can('verDenunciante', $denuncia);
        $puedeVerNotas = $usuario->can('verNotas', $denuncia);
        $puedeVerChat = $usuario->can('verChat', $denuncia);
        $tab = $this->tabValido($request, $puedeVerNotas, $puedeVerChat);

        return view('admin.denuncias.show', [
            'denuncia' => $denuncia,
            'tab' => $tab,
            'volver' => $this->urlVolver($request),
            'volverCrudo' => $request->query('volver'),
            'descifrados' => $this->descifrar($denuncia, $puedeVerDenunciante),
            'respuestas' => $this->respuestasDescifradas($denuncia),
            'archivo' => $this->archivo($denuncia),
            'historial' => $this->historial($denuncia),
            'puedeVerDenunciante' => $puedeVerDenunciante,
            'puedeVerNotas' => $puedeVerNotas,
            'puedeVerChat' => $puedeVerChat,
            'puedeCambiarEstado' => $usuario->can('cambiarEstado', $denuncia),
            'puedeAsignar' => $usuario->can('asignar', $denuncia),
            'puedeChatear' => $usuario->can('chatear', $denuncia),
            'puedeCrearNota' => $usuario->can('crearNota', $denuncia),
            'transiciones' => $denuncia->status->transicionesValidas(),
            'analistas' => $usuario->can('asignar', $denuncia)
                ? $this->servicio->analistasDisponibles($denuncia)
                : collect(),
            'asignadosIds' => $denuncia->asignaciones->pluck('assigned_to_user_id')->all(),
            'notas' => $puedeVerNotas ? $this->notas($denuncia) : collect(),
            'mensajes' => $puedeVerChat ? $this->chat->conversacion($denuncia) : collect(),
        ]);
    }

    private function notas(Denuncia $denuncia)
    {
        return $denuncia->notas()
            ->with(['autor:id,first_name,last_name', 'editadaPor:id,first_name,last_name', 'eliminadaPor:id,first_name,last_name'])
            ->orderByDesc('is_priority')
            ->orderByDesc('created_at')
            ->get();
    }

    private function descifrar(Denuncia $denuncia, bool $incluirDenunciante): array
    {
        $salt = $denuncia->encryption_salt;
        $valores = [];

        $campos = [
            'denunciado_nombre' => ['complaints.reported_first_name_enc', $denuncia->reported_first_name_enc],
            'denunciado_apellido' => ['complaints.reported_last_name_enc', $denuncia->reported_last_name_enc],
        ];

        if ($incluirDenunciante && $denuncia->denunciante) {
            $reportero = $denuncia->denunciante;
            $campos += [
                'reportero_nombre' => ['complaint_reporters.first_name_enc', $reportero->first_name_enc],
                'reportero_apellido' => ['complaint_reporters.last_name_enc', $reportero->last_name_enc],
                'reportero_email' => ['complaint_reporters.email_enc', $reportero->email_enc],
                'reportero_telefono' => ['complaint_reporters.phone_enc', $reportero->phone_enc],
                'reportero_documento' => ['complaint_reporters.national_id_enc', $reportero->national_id_enc],
            ];
        }

        foreach ($campos as $clave => [$contexto, $cifrado]) {
            $valores[$clave] = $this->descifrarCampo($cifrado, $salt, $contexto, $denuncia->id);
        }

        return $valores;
    }

    private function respuestasDescifradas(Denuncia $denuncia)
    {
        return $denuncia->respuestas->map(fn ($respuesta) => [
            'pregunta' => $respuesta->question_text,
            'respuesta' => $this->descifrarCampo(
                $respuesta->answer_encrypted,
                $denuncia->encryption_salt,
                'complaint_answers.answer_encrypted:' . $respuesta->id,
                $denuncia->id
            ),
        ]);
    }

    private function descifrarCampo(?string $valor, ?string $salt, string $contexto, int $denunciaId): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return $this->cifrado->descifrar($valor, $salt, $contexto);
        } catch (Throwable $e) {
            Log::warning('[DETALLE DENUNCIA] No se pudo descifrar un campo', [
                'denuncia' => $denunciaId,
                'campo' => $contexto,
                'error' => $e->getMessage(),
            ]);

            return '—';
        }
    }

    private function archivo(Denuncia $denuncia): ?Archivo
    {
        $archivo = $denuncia->archivos()->whereNull('deleted_at')->first();

        if (! $archivo) {
            return null;
        }

        $this->auditoria->registrarSeguro([
            'company_id' => $denuncia->company_id,
            'user_id' => auth()->id(),
            'origin' => 'web',
            'action' => 'file.preview',
            'entity_type' => 'file',
            'entity_id' => $archivo->id,
            'affected_complaint_id' => $denuncia->id,
            'result' => 'success',
        ]);

        return $archivo;
    }

    private function historial(Denuncia $denuncia)
    {
        $estados = $denuncia->historialEstados()
            ->with('usuario:id,first_name,last_name')
            ->get()
            ->map(fn ($h) => [
                'tipo' => 'estado',
                'desde' => $h->from_status,
                'hasta' => $h->to_status,
                'razon' => $h->reason,
                'usuario' => $h->usuario?->nombreCompleto(),
                'fecha' => $h->created_at,
                'payload' => null,
            ]);

        $eventos = $denuncia->eventos()
            ->whereIn('event_type', ['priority_changed', 'assigned'])
            ->with('usuario:id,first_name,last_name')
            ->get()
            ->map(fn ($e) => [
                'tipo' => $e->event_type,
                'desde' => null,
                'hasta' => null,
                'razon' => null,
                'usuario' => $e->usuario?->nombreCompleto(),
                'fecha' => $e->created_at,
                'payload' => $e->payload,
            ]);

        return $estados->concat($eventos)->sortByDesc('fecha')->values();
    }

    private function tabValido(Request $request, bool $notas, bool $chat): string
    {
        $tab = $request->query('tab', 'info');

        $permitidas = ['info', 'historial'];

        if ($chat) {
            $permitidas[] = 'chat';
        }

        if ($notas) {
            $permitidas[] = 'notas';
        }

        return in_array($tab, $permitidas, true) ? $tab : 'info';
    }

    /**
     * URL de retorno al listado, con los filtros que traia.
     *
     * Se valida que apunte al listado propio: sin esto, un enlace con
     * ?volver=https://otrositio.com convierte esta pantalla en un redirect
     * abierto -- util para phishing, porque el enlace sale de un dominio
     * legitimo.
     */
    private function urlVolver(Request $request): string
    {
        $volver = $request->query('volver');

        if (! $volver || str_contains($volver, '://') || str_starts_with($volver, '//')) {
            return route('admin.denuncias.index');
        }

        return route('admin.denuncias.index') . '?' . ltrim($volver, '?&');
    }
}
