<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificacionUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Campana de notificaciones de la topbar.
 *
 * Reemplaza a notificaciones_handler.php. Dos diferencias:
 *
 * - El sidebar original ejecutaba la consulta de notificaciones en CADA
 *   carga de página, aunque nadie abriera la campana. Acá el contador es
 *   un COUNT liviano y la lista se pide solo al abrir.
 *
 * - El JOIN a complaints era INNER, así que las notificaciones de
 *   onboarding (complaint_id NULL) nunca aparecían pese a tener su propio
 *   tipo en el enum. Acá el vínculo es opcional.
 */
class NotificacionController extends Controller
{
    private const ETIQUETAS = [
        'new_complaint' => '🆕 Nueva denuncia recibida',
        'new_message' => '💬 Nuevo mensaje del denunciante',
        'assigned' => '📋 Caso asignado a vos',
        'reassigned' => '🔄 Reasignación de caso',
        'status_changed' => '🔔 Cambio de estado en un caso',
        'export_ready' => '📦 Exportación lista para descargar',
        'sin_revision' => '⚠️ Denuncia sin revisión hace más de 7 días',
        'onboarding_completed' => '🏢 Nuevo onboarding completado',
    ];

    public function contar(): JsonResponse
    {
        return response()->json([
            'total' => NotificacionUsuario::where('user_id', auth()->id())
                ->noLeidas()
                ->count(),
        ]);
    }

    public function listar(): JsonResponse
    {
        $notificaciones = NotificacionUsuario::with(['denuncia:id,internal_code,company_id', 'denuncia.empresa:id,name'])
            ->where('user_id', auth()->id())
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (NotificacionUsuario $n) => [
                'id' => $n->id,
                'titulo' => self::ETIQUETAS[$n->type] ?? $n->type,
                'referencia' => $this->referencia($n),
                'empresa' => $n->denuncia?->empresa?->name,
                'fecha' => $n->created_at->format('d/m H:i'),
                'leida' => $n->read_at !== null,
                'url' => $this->url($n),
            ]);

        return response()->json(['notificaciones' => $notificaciones]);
    }

    public function leer(Request $request): JsonResponse
    {
        $datos = $request->validate(['id' => ['required', 'integer']]);

        NotificacionUsuario::where('user_id', auth()->id())
            ->where('id', $datos['id'])
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'status' => 'read']);

        return response()->json(['ok' => true]);
    }

    public function leerTodas(): JsonResponse
    {
        NotificacionUsuario::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'status' => 'read']);

        return response()->json(['ok' => true]);
    }

    private function referencia(NotificacionUsuario $n): ?string
    {
        if ($n->type === 'onboarding_completed') {
            return $n->metadata['empresa'] ?? 'Nueva empresa';
        }

        return $n->denuncia?->internal_code;
    }

    private function url(NotificacionUsuario $n): ?string
    {
        if ($n->type === 'onboarding_completed') {
            $token = $n->metadata['token_id'] ?? null;

            return route('admin.onboarding.index', $token ? ['revisar' => $token] : []);
        }

        return $n->complaint_id
            ? route('admin.denuncias.show', $n->complaint_id)
            : null;
    }
}
