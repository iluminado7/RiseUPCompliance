<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\DocumentoLegal;
use App\Models\Empresa;
use App\Models\Pregunta;
use Illuminate\Support\Facades\DB;

/**
 * Configuracion del canal de una empresa.
 *
 * Todo lo de aca cambia lo que ve el denunciante en el formulario publico,
 * asi que queda auditado en la cadena de esa empresa.
 */
class ServicioConfiguracionCanal
{
    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    /**
     * Sincroniza categorias y relaciones habilitadas.
     *
     * Al habilitar una categoria que la empresa no tenia configurada, se
     * importa el cuestionario del catalogo automaticamente: sin preguntas
     * el formulario publico solo pediria los datos generales.
     *
     * @param  array<int>  $categorias
     * @param  array<int>  $relaciones
     */
    public function guardarCategoriasYRelaciones(Empresa $empresa, array $categorias, array $relaciones): void
    {
        DB::transaction(function () use ($empresa, $categorias, $relaciones) {
            $this->sincronizarPivote('company_categories', 'category_id', $empresa->id, $categorias);
            $this->sincronizarPivote('company_relationships', 'relationship_id', $empresa->id, $relaciones);

            foreach ($categorias as $categoriaId) {
                $this->importarSiNoTiene($empresa, (int) $categoriaId);
            }

            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'channel.categories_updated',
                'entity_type' => 'company',
                'entity_id' => $empresa->id,
                'result' => 'success',
                'detail' => sprintf(
                    'Canal de %s · %d categoría(s), %d relación(es)',
                    $empresa->name,
                    count($categorias),
                    count($relaciones)
                ),
            ]);
        });
    }

    /**
     * @param  array<int>  $areas
     * @param  array<int>  $cargos
     */
    public function guardarAreasYCargos(Empresa $empresa, array $areas, array $cargos): void
    {
        DB::transaction(function () use ($empresa, $areas, $cargos) {
            $this->sincronizarPivote('company_areas', 'area_id', $empresa->id, $areas);
            $this->sincronizarPivote('company_positions', 'position_id', $empresa->id, $cargos);

            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'channel.areas_updated',
                'entity_type' => 'company',
                'entity_id' => $empresa->id,
                'result' => 'success',
                'detail' => sprintf(
                    'Canal de %s · %d área(s), %d cargo(s)',
                    $empresa->name,
                    count($areas),
                    count($cargos)
                ),
            ]);
        });
    }

    /**
     * Importa el cuestionario del catalogo, reemplazando lo que haya.
     *
     * Todo en una transaccion. El original desactivaba las versiones
     * anteriores y despues insertaba sin transaccion: si el INSERT fallaba
     * a mitad, la empresa quedaba sin cuestionario activo y su formulario
     * publico dejaba de mostrar preguntas.
     */
    public function importarDelCatalogo(Empresa $empresa, Categoria $categoria): int
    {
        return DB::transaction(function () use ($empresa, $categoria) {
            $base = Pregunta::where('category_id', $categoria->id)
                ->whereNull('company_id')
                ->where('is_active', true)
                ->orderBy('question_order')
                ->get();

            if ($base->isEmpty()) {
                return 0;
            }

            $version = $this->desactivarVersionVigente($empresa->id, $categoria->id);

            foreach ($base as $indice => $pregunta) {
                Pregunta::create([
                    'category_id' => $categoria->id,
                    'company_id' => $empresa->id,
                    // El vinculo al catalogo se conserva: es lo que hace
                    // que esta pregunta siga recibiendo las correcciones
                    // globales. El original no lo guardaba.
                    'catalog_question_id' => $pregunta->id,
                    'version' => $version,
                    'question_order' => $indice + 1,
                    'question_text_es' => $pregunta->question_text_es,
                    'is_active' => true,
                ]);
            }

            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'questionnaire.imported',
                'entity_type' => 'complaint_category',
                'entity_id' => $categoria->id,
                'result' => 'success',
                'detail' => "Cuestionario de {$categoria->name_es} importado del catálogo · v{$version}",
            ]);

            return $base->count();
        });
    }

    /**
     * Guarda una version nueva del cuestionario de esta empresa.
     *
     * @param  array<array{texto:string, catalogo_id:?int}>  $preguntas
     */
    public function guardarVersion(Empresa $empresa, Categoria $categoria, array $preguntas): int
    {
        return DB::transaction(function () use ($empresa, $categoria, $preguntas) {
            $version = $this->desactivarVersionVigente($empresa->id, $categoria->id);

            $orden = 1;

            foreach ($preguntas as $pregunta) {
                $texto = trim($pregunta['texto'] ?? '');

                if ($texto === '') {
                    continue;
                }

                Pregunta::create([
                    'category_id' => $categoria->id,
                    'company_id' => $empresa->id,
                    'catalog_question_id' => $pregunta['catalogo_id'] ?: null,
                    'version' => $version,
                    'question_order' => $orden++,
                    'question_text_es' => $texto,
                    'is_active' => true,
                ]);
            }

            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'questionnaire.version_created',
                'entity_type' => 'complaint_category',
                'entity_id' => $categoria->id,
                'result' => 'success',
                'detail' => "Cuestionario de {$categoria->name_es} v{$version} · edición manual",
            ]);

            return $version;
        });
    }

    /**
     * Publica una version nueva del aviso de privacidad.
     *
     * -- POR QUE ESTO NO VA EN UN JSON --
     *
     * El original lo guardaba en companies.public_configuration, un campo
     * que se sobrescribe. Cambiar el aviso hacia desaparecer el texto
     * anterior, y las denuncias que lo habian aceptado quedaban apuntando
     * a nada.
     *
     * legal_document_versions existe exactamente para esto, y
     * complaints.privacy_notice_version_id la referencia: es lo que
     * permite demostrar, para cada denuncia, que texto vio esa persona.
     *
     * El contenido vive en public_configuration porque no hay columna para
     * el cuerpo del documento; lo que versiona la tabla es el hash y la
     * fecha de publicacion. Si mas adelante hace falta recuperar textos
     * viejos, habria que agregar una columna de contenido.
     */
    public function publicarAvisoPrivacidad(Empresa $empresa, string $texto): ?DocumentoLegal
    {
        return DB::transaction(function () use ($empresa, $texto) {
            $hash = hash('sha256', $texto);

            $vigente = DocumentoLegal::where('company_id', $empresa->id)
                ->where('document_type', 'privacy_notice')
                ->where('is_active', true)
                ->first();

            // Sin cambios: no se crea una versión por guardar dos veces lo
            // mismo.
            if ($vigente && $vigente->content_hash === $hash) {
                return null;
            }

            if ($vigente) {
                $vigente->is_active = false;
                $vigente->save();
            }

            $numero = (int) DocumentoLegal::where('company_id', $empresa->id)
                ->where('document_type', 'privacy_notice')
                ->count() + 1;

            $documento = DocumentoLegal::create([
                'company_id' => $empresa->id,
                'document_type' => 'privacy_notice',
                'version' => 'v' . $numero,
                'content_hash' => $hash,
                'published_at' => now(),
                'is_active' => true,
            ]);

            $config = $empresa->public_configuration ?? [];
            $config['privacy_notice_text'] = $texto;
            $empresa->public_configuration = $config;
            $empresa->save();

            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'channel.privacy_notice_published',
                'entity_type' => 'legal_document_version',
                'entity_id' => $documento->id,
                'result' => 'success',
                'detail' => "Aviso de privacidad {$documento->version} publicado para {$empresa->name}",
            ]);

            return $documento;
        });
    }

    /** @param  array<string>  $emails */
    public function guardarEmailsAlerta(Empresa $empresa, array $emails): void
    {
        DB::transaction(function () use ($empresa, $emails) {
            $config = $empresa->public_configuration ?? [];
            // Array y no string crudo: el original guardaba el texto tal
            // como venia del formulario, sin separar ni validar.
            $config['notification_emails'] = array_values($emails);
            $empresa->public_configuration = $config;
            $empresa->save();

            $this->auditoria->registrar([
                'company_id' => $empresa->id,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'channel.notification_emails_updated',
                'entity_type' => 'company',
                'entity_id' => $empresa->id,
                'result' => 'success',
                'detail' => count($emails) . ' destinatario(s) de alerta',
            ]);
        });
    }

    // -- Internos ------------------------------------------------

    /**
     * Desactiva la version vigente y devuelve el numero de la nueva.
     *
     * El lock evita que dos ediciones simultaneas generen la misma
     * version y choquen contra el unique.
     */
    private function desactivarVersionVigente(int $empresaId, int $categoriaId): int
    {
        $version = (Pregunta::where('category_id', $categoriaId)
            ->where('company_id', $empresaId)
            ->lockForUpdate()
            ->max('version') ?? 0) + 1;

        Pregunta::where('category_id', $categoriaId)
            ->where('company_id', $empresaId)
            ->where('is_active', true)
            ->update(['is_active' => false, 'valid_to' => now()]);

        return $version;
    }

    /** @param  array<int>  $ids */
    private function sincronizarPivote(string $tabla, string $columna, int $empresaId, array $ids): void
    {
        DB::table($tabla)->where('company_id', $empresaId)->delete();

        if (empty($ids)) {
            return;
        }

        $filas = [];

        foreach (array_values($ids) as $indice => $id) {
            $filas[] = [
                'company_id' => $empresaId,
                $columna => (int) $id,
                'display_order' => $indice + 1,
                'is_active' => true,
            ];
        }

        DB::table($tabla)->insert($filas);
    }

    private function importarSiNoTiene(Empresa $empresa, int $categoriaId): void
    {
        $tiene = Pregunta::where('category_id', $categoriaId)
            ->where('company_id', $empresa->id)
            ->where('is_active', true)
            ->exists();

        if ($tiene) {
            return;
        }

        $categoria = Categoria::find($categoriaId);

        if ($categoria) {
            $this->importarDelCatalogo($empresa, $categoria);
        }
    }
}
