<?php

namespace App\Services;

use App\Enums\EstadoDenuncia;
use App\Models\Archivo;
use App\Models\Categoria;
use App\Models\Denuncia;
use App\Models\Denunciante;
use App\Models\DocumentoLegal;
use App\Models\Empresa;
use App\Models\EventoDenuncia;
use App\Models\NotificacionUsuario;
use App\Models\Pregunta;
use App\Models\Respuesta;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Alta de una denuncia desde el canal publico.
 *
 * Todo ocurre en una transaccion: la denuncia, sus respuestas, los datos
 * del denunciante y el adjunto entran juntos o no entra nada. Una denuncia
 * a medias es peor que ninguna.
 */
class ServicioDenunciaPublica
{
    public function __construct(
        private readonly ServicioCodigoSeguimiento $codigos,
        private readonly ServicioCifrado $cifrado,
        private readonly ServicioMetadatos $metadatos,
        private readonly ServicioAuditoria $auditoria,
        private readonly HashIp $hashIp,
    ) {}

    /**
     * @return string El codigo de seguimiento en texto plano. Es la UNICA
     *                vez que existe: la base guarda solo su hash.
     */
    public function crear(Empresa $empresa, array $datos, ?UploadedFile $adjunto): string
    {
        $tracking = $this->codigos->generarTracking();

        DB::transaction(function () use ($empresa, $datos, $adjunto, $tracking) {
            $sucursal = $datos['branch_id']
                ? Sucursal::withoutGlobalScopes()->find($datos['branch_id'])
                : null;

            $categoria = Categoria::findOrFail($datos['category_id']);
            $salt = $this->cifrado->generarSalt();

            $denuncia = new Denuncia([
                'company_id' => $empresa->id,
                'internal_code' => $this->codigos->generarInterno($empresa, $sucursal),
                'is_anonymous' => $datos['es_anonima'],
                'intake_channel' => 'web',
                'submission_status' => 'confirmed',
                'category_id' => $categoria->id,
                'relationship_id' => $datos['relationship_id'],
                'relationship_other_text' => $datos['relationship_other'],
                'branch_id' => $sucursal?->id,
                'reported_area_id' => $datos['area_id'],
                'reported_area_other' => $datos['area_other'],
                'reported_position_id' => $datos['position_id'],
                'reported_position_other' => $datos['position_other'],
                'incident_date' => $datos['incident_date'],
                'priority' => 'medium',
                'status' => EstadoDenuncia::Nuevo,
                'public_status' => EstadoDenuncia::Nuevo->estadoPublico(),
            ]);

            $denuncia->tracking_code_hash = $this->codigos->hashear($tracking);
            $denuncia->encryption_salt = $salt;
            $denuncia->last_action_at = now();

            // Aviso de privacidad: queda registrado QUE version acepto esta
            // persona, para poder demostrarlo despues.
            $aviso = DocumentoLegal::where('company_id', $empresa->id)
                ->where('document_type', 'privacy_notice')
                ->where('is_active', true)
                ->first();

            if ($aviso) {
                $denuncia->privacy_notice_accepted = true;
                $denuncia->privacy_notice_version_id = $aviso->id;
                $denuncia->privacy_notice_accepted_at = now();
            }

            $denuncia->save();

            $this->guardarDenunciado($denuncia, $datos, $salt);
            $this->guardarDenunciante($denuncia, $datos, $salt);
            $this->guardarRespuestas($denuncia, $categoria, $datos['respuestas'] ?? [], $salt);

            if ($adjunto) {
                $this->guardarAdjunto($denuncia, $adjunto);
            }

            EventoDenuncia::create([
                'company_id' => $empresa->id,
                'complaint_id' => $denuncia->id,
                'user_id' => null,
                'event_type' => 'received',
                'payload' => ['canal' => 'web'],
                'source_ip_hash' => $this->hashIp->hash(request()->ip()),
                'source_ip_enc' => $this->cifrado->cifrar(request()->ip()),
            ]);

            $this->avisarAlEquipo($denuncia, $empresa);

            // El registro va dentro de la transaccion. `es_anonimo` hace
            // que el session_id se hashee, para que no se pueda correlacionar
            // la actividad del mismo denunciante a lo largo del tiempo (H-005).
            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => null,
                'origin' => 'web',
                'action' => 'complaint.created',
                'entity_type' => 'complaint',
                'entity_id' => $denuncia->id,
                'affected_complaint_id' => $denuncia->id,
                'result' => 'success',
                'es_anonimo' => true,
                'detail' => "Denuncia {$denuncia->internal_code} recibida por el canal web",
            ]);
        });

        return $tracking;
    }

    // -- Internos ------------------------------------------------

    private function guardarDenunciado(Denuncia $denuncia, array $datos, string $salt): void
    {
        $denuncia->forceFill([
            'reported_first_name_enc' => $this->cifrado->cifrar(
                $datos['denunciado_nombre'],
                $salt,
                'complaints.reported_first_name_enc'
            ),
            'reported_last_name_enc' => $this->cifrado->cifrar(
                $datos['denunciado_apellido'],
                $salt,
                'complaints.reported_last_name_enc'
            ),
        ])->save();
    }

    /**
     * Datos del denunciante. Solo si NO es anonima.
     *
     * En una denuncia anonima no se guarda ni una fila vacia: la ausencia
     * de registro es mas fuerte que un registro con nulos.
     */
    private function guardarDenunciante(Denuncia $denuncia, array $datos, string $salt): void
    {
        if ($datos['es_anonima']) {
            return;
        }

        $reportero = new Denunciante([
            'company_id' => $denuncia->company_id,
            'complaint_id' => $denuncia->id,
            'encryption_key_id' => $this->cifrado->claveActivaId(),
            'encryption_version' => 1,
        ]);

        $reportero->save();

        $contexto = fn (string $campo) => "complaint_reporters.{$campo}:{$reportero->id}";

        $reportero->forceFill([
            'first_name_enc' => $this->cifrado->cifrar($datos['nombre'], $salt, $contexto('first_name_enc')),
            'last_name_enc' => $this->cifrado->cifrar($datos['apellido'], $salt, $contexto('last_name_enc')),
            'gender_enc' => $this->cifrado->cifrar($datos['genero'], $salt, $contexto('gender_enc')),
            'email_enc' => $this->cifrado->cifrar($datos['email'], $salt, $contexto('email_enc')),
            'national_id_enc' => $this->cifrado->cifrar($datos['documento'], $salt, $contexto('national_id_enc')),
            'phone_enc' => $this->cifrado->cifrar($datos['telefono'], $salt, $contexto('phone_enc')),
        ])->save();
    }

    /**
     * Respuestas del cuestionario.
     *
     * H-003: la columna se llama answer_encrypted y guardaba texto plano.
     * Ahora se cifra de verdad, con la clave derivada del salt.
     *
     * question_text guarda una copia del enunciado: si el cuestionario
     * cambia despues, esta denuncia conserva lo que efectivamente se le
     * pregunto a esta persona.
     */
    private function guardarRespuestas(Denuncia $denuncia, Categoria $categoria, array $respuestas, string $salt): void
    {
        $preguntas = Pregunta::where('category_id', $categoria->id)
            ->where(fn ($q) => $q->where('company_id', $denuncia->company_id)->orWhereNull('company_id'))
            ->where('is_active', true)
            ->orderByRaw('company_id IS NULL')  // las de la empresa primero
            ->orderBy('question_order')
            ->get()
            ->unique('question_order')
            ->values();

        foreach ($preguntas as $indice => $pregunta) {
            $texto = trim($respuestas[$indice] ?? '');

            if ($texto === '') {
                continue;
            }

            $respuesta = new Respuesta([
                'company_id' => $denuncia->company_id,
                'complaint_id' => $denuncia->id,
                'category_question_id' => $pregunta->id,
                'question_order' => $pregunta->question_order,
                'question_text' => $pregunta->question_text_es,
            ]);

            $respuesta->save();

            $respuesta->forceFill([
                'answer_encrypted' => $this->cifrado->cifrar(
                    $texto,
                    $salt,
                    'complaint_answers.answer_encrypted:' . $respuesta->id
                ),
            ])->save();
        }
    }

    private function guardarAdjunto(Denuncia $denuncia, UploadedFile $adjunto): void
    {
        // El tipo se detecta de los bytes, no de la extension ni del
        // Content-Type que declara el navegador: los dos los controla quien
        // sube el archivo.
        $mime = $adjunto->getMimeType();

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            default => 'bin',
        };

        $nombre = bin2hex(random_bytes(16)) . '.' . $extension;
        $carpeta = 'denuncias/' . $denuncia->company_id;
        $rutaRelativa = $carpeta . '/' . $nombre;

        Storage::disk('local')->makeDirectory($carpeta);
        $rutaCompleta = Storage::disk('local')->path($rutaRelativa);

        $resumen = $this->metadatos->depurar($adjunto, $rutaCompleta, $mime);

        Archivo::create([
            'company_id' => $denuncia->company_id,
            'complaint_id' => $denuncia->id,
            'original_name' => mb_substr($adjunto->getClientOriginalName(), 0, 500),
            'storage_name' => $nombre,
            'storage_bucket' => 'local',
            'storage_path' => $rutaRelativa,
            'sha256_hash' => hash_file('sha256', $rutaCompleta),
            'size_bytes' => filesize($rutaCompleta),
            'declared_mime' => mb_substr((string) $adjunto->getClientMimeType(), 0, 100),
            'detected_mime' => $mime,
            'detected_extension' => $extension,
            'clean_metadata' => $resumen,

            // Un archivo que pasó por el depurador queda disponible; uno
            // que no, bloqueado.
            //
            // El depurador de imágenes no solo borra el EXIF: al decodificar
            // y volver a codificar con GD, cualquier payload embebido se
            // pierde. Una imagen depurada es segura por construcción, no
            // por confianza.
            //
            // PDF y Word no pasan por ahí (H-016 requiere exiftool o
            // Ghostscript), así que siguen bloqueados hasta que exista el
            // escaneo. El panel muestra el motivo.
            'file_status' => $resumen['depurado'] ? 'available' : 'pending',
            'scan_result' => $resumen['depurado'] ? 'clean' : 'pending',
            'available_for_analyst' => (bool) $resumen['depurado'],
            'uploaded_by_type' => 'reporter',
            'uploaded_by_user_id' => null,
        ]);
    }

    /** Aviso al equipo de la empresa que hay una denuncia nueva. */
    private function avisarAlEquipo(Denuncia $denuncia, Empresa $empresa): void
    {
        $destinatarios = Usuario::where('company_id', $empresa->id)
            ->where('status', 'active')
            ->whereHas('rol', fn ($q) => $q->whereIn('name', ['admin_principal', 'gestor']))
            ->pluck('id');

        foreach ($destinatarios as $idUsuario) {
            NotificacionUsuario::create([
                'company_id' => $empresa->id,
                'user_id' => $idUsuario,
                'complaint_id' => $denuncia->id,
                'type' => 'new_complaint',
                'status' => 'pending',
            ]);
        }

        // PENDIENTE: el envio por mail a las direcciones configuradas en
        // Configuracion del Canal. Es la deuda de 7 del brief; requiere
        // Resend con el dominio verificado.
    }
}
