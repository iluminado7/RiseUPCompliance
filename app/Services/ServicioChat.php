<?php

namespace App\Services;

use App\Models\Denuncia;
use App\Models\EventoDenuncia;
use App\Models\Mensaje;
use App\Models\NotificacionDenuncia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Chat entre el investigador y el denunciante.
 *
 * Dos correcciones respecto del original:
 *
 * 1. CIFRADO REAL.
 *    El original insertaba el contenido en texto plano con
 *    encryption_key_id = 'key-dev-placeholder'. Era la deuda listada en
 *    7 del brief. Aca el contenido se cifra con la clave derivada del
 *    salt de la denuncia, asi que la purga por retencion tambien lo
 *    alcanza.
 *
 * 2. SIN htmlspecialchars AL GUARDAR (H-014).
 *    El original escapaba antes de insertar, lo que produce doble
 *    escapado y rompe cualquier salida que no sea HTML -- un export a PDF
 *    o a JSON mostraria &amp;quot; en vez de comillas. Se guarda crudo y
 *    se escapa al renderizar, que es donde corresponde.
 */
class ServicioChat
{
    public function __construct(
        private readonly ServicioCifrado $cifrado,
        private readonly ServicioAuditoria $auditoria,
    ) {}

    public function enviar(Denuncia $denuncia, string $contenido): Mensaje
    {
        return DB::transaction(function () use ($denuncia, $contenido) {
            $mensaje = new Mensaje([
                'company_id' => $denuncia->company_id,
                'complaint_id' => $denuncia->id,
                'sender_type' => 'analyst',
                'sender_user_id' => auth()->id(),
                'encryption_key_id' => $this->cifrado->claveActivaId(),
            ]);

            $mensaje->save();

            // El AAD incluye el id del mensaje, asi que hay que cifrar
            // despues del insert: ata el ciphertext a esta fila y solo a
            // esta. Mover el valor a otro mensaje rompe el descifrado.
            $mensaje->content = $this->cifrado->cifrar(
                $contenido,
                $denuncia->encryption_salt,
                'complaint_messages.content:' . $mensaje->id
            );
            $mensaje->save();

            EventoDenuncia::create([
                'company_id' => $denuncia->company_id,
                'complaint_id' => $denuncia->id,
                'user_id' => auth()->id(),
                'event_type' => 'message_sent',
                'payload' => ['message_id' => $mensaje->id],
            ]);

            $this->auditoria->registrar([
                'company_id' => $denuncia->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'chat.message_sent',
                'entity_type' => 'complaint_message',
                'entity_id' => $mensaje->id,
                'affected_complaint_id' => $denuncia->id,
                'result' => 'success',
            ]);

            // El aviso al denunciante NO lleva el contenido: viaja por
            // email o queda para cuando consulte su codigo, y en ninguno
            // de los dos casos debe filtrar el texto del mensaje.
            NotificacionDenuncia::create([
                'company_id' => $denuncia->company_id,
                'complaint_id' => $denuncia->id,
                'type' => 'new_message',
                'channel' => 'tracking_code',
                'status' => 'pending',
            ]);

            $denuncia->forceFill(['last_action_at' => now()])->save();

            return $mensaje;
        });
    }

    /**
     * Mensajes descifrados para mostrar.
     *
     * Un mensaje que no se pueda descifrar no rompe la conversacion
     * entera: se muestra un aviso y el error queda en el log.
     */
    public function conversacion(Denuncia $denuncia)
    {
        return $denuncia->mensajes()
            ->visiblesParaDenunciante()
            ->with('emisor:id,first_name,last_name')
            ->get()
            ->map(function (Mensaje $mensaje) use ($denuncia) {
                try {
                    $texto = $this->cifrado->descifrar(
                        $mensaje->content,
                        $denuncia->encryption_salt,
                        'complaint_messages.content:' . $mensaje->id
                    );
                } catch (Throwable $e) {
                    Log::warning('[CHAT] No se pudo descifrar un mensaje', [
                        'mensaje' => $mensaje->id,
                        'denuncia' => $denuncia->id,
                        'error' => $e->getMessage(),
                    ]);
                    $texto = null;
                }

                return [
                    'id' => $mensaje->id,
                    'es_analista' => $mensaje->sender_type === 'analyst',
                    'autor' => $mensaje->emisor?->nombreCompleto() ?? 'Denunciante',
                    'texto' => $texto,
                    'leido' => (bool) $mensaje->is_read_by_reporter,
                    'fecha' => $mensaje->created_at,
                ];
            });
    }
}
