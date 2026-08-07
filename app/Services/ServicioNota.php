<?php

namespace App\Services;

use App\Models\Denuncia;
use App\Models\EventoDenuncia;
use App\Models\NotaInterna;
use Illuminate\Support\Facades\DB;

/**
 * Notas internas del equipo de investigacion.
 *
 * PENDIENTE: el contenido va en texto plano, portado tal cual. La
 * auditoria no lo marco (H-003 cubria solo las respuestas del
 * cuestionario), pero estas notas suelen tener mas detalle sensible que
 * la denuncia misma: hipotesis, nombres de testigos, resultados
 * parciales. Cifrarlas con el salt de la denuncia es una linea y conviene
 * hacerlo antes de tener datos reales.
 */
class ServicioNota
{
    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    public function crear(Denuncia $denuncia, string $contenido, bool $prioritaria): NotaInterna
    {
        return DB::transaction(function () use ($denuncia, $contenido, $prioritaria) {
            $nota = NotaInterna::create([
                'company_id' => $denuncia->company_id,
                'complaint_id' => $denuncia->id,
                'user_id' => auth()->id(),
                'content' => $contenido,
                'is_priority' => $prioritaria,
            ]);

            EventoDenuncia::create([
                'company_id' => $denuncia->company_id,
                'complaint_id' => $denuncia->id,
                'user_id' => auth()->id(),
                'event_type' => 'note_added',
                'payload' => ['note_id' => $nota->id],
            ]);

            $this->auditoria->registrar([
                'company_id' => $denuncia->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'note.created',
                'entity_type' => 'internal_note',
                'entity_id' => $nota->id,
                'affected_complaint_id' => $denuncia->id,
                'result' => 'success',
            ]);

            return $nota;
        });
    }

    public function editar(NotaInterna $nota, string $contenido, bool $prioritaria): void
    {
        DB::transaction(function () use ($nota, $contenido, $prioritaria) {
            // original_content guarda la PRIMERA version, no la anterior:
            // si se sobrescribiera en cada edicion, se perderia el texto
            // que se escribio originalmente.
            if ($nota->original_content === null) {
                $nota->original_content = $nota->content;
            }

            $nota->content = $contenido;
            $nota->is_priority = $prioritaria;
            $nota->edited_at = now();
            $nota->edited_by_user_id = auth()->id();
            $nota->save();

            EventoDenuncia::create([
                'company_id' => $nota->company_id,
                'complaint_id' => $nota->complaint_id,
                'user_id' => auth()->id(),
                'event_type' => 'note_edited',
                'payload' => ['note_id' => $nota->id],
            ]);

            $this->auditoria->registrar([
                'company_id' => $nota->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'note.edited',
                'entity_type' => 'internal_note',
                'entity_id' => $nota->id,
                'affected_complaint_id' => $nota->complaint_id,
                'result' => 'success',
            ]);
        });
    }

    /**
     * Borrado logico. La nota se conserva tachada, con quien la elimino.
     *
     * Es una operacion destructiva, asi que el registro va dentro de la
     * transaccion y sin catch: si falla registrar, no se borra (6.2).
     */
    public function eliminar(NotaInterna $nota): void
    {
        DB::transaction(function () use ($nota) {
            $nota->deleted_at = now();
            $nota->deleted_by_user_id = auth()->id();
            $nota->save();

            EventoDenuncia::create([
                'company_id' => $nota->company_id,
                'complaint_id' => $nota->complaint_id,
                'user_id' => auth()->id(),
                'event_type' => 'note_deleted',
                'payload' => ['note_id' => $nota->id],
            ]);

            $this->auditoria->registrar([
                'company_id' => $nota->company_id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'note.deleted',
                'entity_type' => 'internal_note',
                'entity_id' => $nota->id,
                'affected_complaint_id' => $nota->complaint_id,
                'result' => 'success',
            ]);
        });
    }
}
