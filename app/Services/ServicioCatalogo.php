<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Pregunta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Catalogo global: categorias, areas, cargos, relaciones y el cuestionario
 * base de cada categoria.
 *
 * Todo lo de aca impacta a TODAS las empresas, asi que cada operacion
 * queda auditada. En el sistema original ninguna lo estaba: se podia
 * cambiar el formulario de todos los clientes sin dejar rastro.
 */
class ServicioCatalogo
{
    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    // -- Items simples (categoria, area, cargo, relacion) ---------

    public function crearItem(string $clase, array $datos, string $tipo): Model
    {
        return DB::transaction(function () use ($clase, $datos, $tipo) {
            /** @var Model $item */
            $item = $clase::create($datos);

            $this->auditoria->registrar([
                'company_id' => null,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => "catalog.{$tipo}_created",
                'entity_type' => $tipo,
                'entity_id' => $item->id,
                'result' => 'success',
                'detail' => "Catálogo · alta de {$tipo}: {$datos['name_es']} ({$datos['code']})",
            ]);

            return $item;
        });
    }

    public function actualizarItem(Model $item, array $datos, string $tipo): void
    {
        DB::transaction(function () use ($item, $datos, $tipo) {
            $antes = $item->only(array_keys($datos));

            $item->fill($datos)->save();

            $cambios = array_keys(array_diff_assoc($datos, $antes));

            $this->auditoria->registrar([
                'company_id' => null,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => "catalog.{$tipo}_updated",
                'entity_type' => $tipo,
                'entity_id' => $item->id,
                'result' => 'success',
                'detail' => "Catálogo · edición de {$tipo} {$item->code} · campos: "
                    . ($cambios ? implode(', ', $cambios) : 'ninguno'),
            ]);
        });
    }

    // -- Cuestionario base ----------------------------------------

    /**
     * Agrega una pregunta al catalogo base de una categoria.
     *
     * La propagacion a las empresas ocurre despues: quien ya tenga un
     * cuestionario armado recibe una version nueva con la pregunta al
     * final.
     */
    public function agregarPregunta(Categoria $categoria, string $texto): Pregunta
    {
        return DB::transaction(function () use ($categoria, $texto) {
            $orden = Pregunta::where('category_id', $categoria->id)
                ->whereNull('company_id')
                ->where('is_active', true)
                ->lockForUpdate()
                ->max('question_order') ?? 0;

            $version = Pregunta::where('category_id', $categoria->id)
                ->whereNull('company_id')
                ->max('version') ?? 1;

            $pregunta = Pregunta::create([
                'category_id' => $categoria->id,
                'company_id' => null,
                'catalog_question_id' => null,
                'version' => $version,
                'question_order' => $orden + 1,
                'question_text_es' => $texto,
                'is_active' => true,
            ]);

            $this->propagarAEmpresas($categoria, "Pregunta agregada al catálogo");

            $this->auditoria->registrar([
                'company_id' => null,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'catalog.question_created',
                'entity_type' => 'category_question',
                'entity_id' => $pregunta->id,
                'result' => 'success',
                'detail' => "Catálogo · pregunta agregada a {$categoria->name_es}",
            ]);

            return $pregunta;
        });
    }

    /**
     * Edita una pregunta del catalogo.
     *
     * question_text_es es INMUTABLE: no se reescribe, se desactiva la
     * fila y se inserta una nueva conservando el orden. El original hacia
     * UPDATE sobre la columna, pese a la documentacion del schema.
     */
    public function editarPregunta(Pregunta $pregunta, string $texto): Pregunta
    {
        return DB::transaction(function () use ($pregunta, $texto) {
            $categoria = $pregunta->categoria;

            $pregunta->forceFill([
                'is_active' => false,
                'valid_to' => now(),
            ])->save();

            $nueva = Pregunta::create([
                'category_id' => $pregunta->category_id,
                'company_id' => null,
                'catalog_question_id' => null,
                // Version nueva: el unique ahora si protege el catalogo,
                // asi que repetir (categoria, orden, version) fallaria.
                'version' => $pregunta->version + 1,
                'question_order' => $pregunta->question_order,
                'question_text_es' => $texto,
                'is_active' => true,
            ]);

            // Las empresas que tenian vinculada la pregunta vieja pasan a
            // apuntar a la nueva.
            Pregunta::whereNotNull('company_id')
                ->where('catalog_question_id', $pregunta->id)
                ->where('is_active', true)
                ->update(['catalog_question_id' => $nueva->id]);

            $this->propagarAEmpresas($categoria, 'Pregunta editada en el catálogo');

            $this->auditoria->registrar([
                'company_id' => null,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'catalog.question_updated',
                'entity_type' => 'category_question',
                'entity_id' => $nueva->id,
                'result' => 'success',
                'detail' => "Catálogo · pregunta editada en {$categoria->name_es}",
            ]);

            return $nueva;
        });
    }

    public function desactivarPregunta(Pregunta $pregunta): void
    {
        DB::transaction(function () use ($pregunta) {
            $categoria = $pregunta->categoria;

            $pregunta->forceFill([
                'is_active' => false,
                'valid_to' => now(),
            ])->save();

            $this->propagarAEmpresas($categoria, 'Pregunta retirada del catálogo');

            $this->auditoria->registrar([
                'company_id' => null,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'catalog.question_disabled',
                'entity_type' => 'category_question',
                'entity_id' => $pregunta->id,
                'result' => 'success',
                'detail' => "Catálogo · pregunta retirada de {$categoria->name_es}",
            ]);
        });
    }

    /**
     * Propaga los cambios del catalogo a las empresas vinculadas.
     *
     * -- QUE SE PROPAGA Y QUE NO --
     *
     * Solo las preguntas con catalog_question_id: las que la empresa
     * importo del catalogo. Las que agrego a mano (catalog_question_id
     * NULL) son suyas y se conservan tal cual.
     *
     * Cada empresa afectada recibe una VERSION NUEVA completa, no un
     * UPDATE en su lugar. Asi el historial refleja el cambio y las
     * denuncias viejas siguen vinculadas a la version con la que se
     * respondieron.
     *
     * Se salta a las empresas que no tienen cuestionario armado todavia:
     * esas van a importar del catalogo cuando les toque, y ahi reciben la
     * version actual.
     */
    private function propagarAEmpresas(Categoria $categoria, string $motivo): void
    {
        $empresasAfectadas = Pregunta::where('category_id', $categoria->id)
            ->whereNotNull('company_id')
            ->where('is_active', true)
            ->whereNotNull('catalog_question_id')
            ->distinct()
            ->pluck('company_id');

        if ($empresasAfectadas->isEmpty()) {
            return;
        }

        $base = $this->preguntasBaseVigentes($categoria);

        foreach ($empresasAfectadas as $empresaId) {
            $this->regenerarVersionEmpresa($categoria, (int) $empresaId, $base, $motivo);
        }
    }

    /** @return Collection<int, Pregunta> */
    private function preguntasBaseVigentes(Categoria $categoria): Collection
    {
        return Pregunta::where('category_id', $categoria->id)
            ->whereNull('company_id')
            ->where('is_active', true)
            ->orderBy('question_order')
            ->get();
    }

    private function regenerarVersionEmpresa(
        Categoria $categoria,
        int $empresaId,
        Collection $base,
        string $motivo,
    ): void {
        $vigentes = Pregunta::where('category_id', $categoria->id)
            ->where('company_id', $empresaId)
            ->where('is_active', true)
            ->orderBy('question_order')
            ->lockForUpdate()
            ->get();

        // Las propias de la empresa: sin vinculo al catalogo.
        $propias = $vigentes->whereNull('catalog_question_id')->values();

        $versionNueva = (Pregunta::where('category_id', $categoria->id)
            ->where('company_id', $empresaId)
            ->max('version') ?? 0) + 1;

        Pregunta::where('category_id', $categoria->id)
            ->where('company_id', $empresaId)
            ->where('is_active', true)
            ->update(['is_active' => false, 'valid_to' => now()]);

        $orden = 1;

        foreach ($base as $pregunta) {
            Pregunta::create([
                'category_id' => $categoria->id,
                'company_id' => $empresaId,
                'catalog_question_id' => $pregunta->id,
                'version' => $versionNueva,
                'question_order' => $orden++,
                'question_text_es' => $pregunta->question_text_es,
                'is_active' => true,
            ]);
        }

        foreach ($propias as $pregunta) {
            Pregunta::create([
                'category_id' => $categoria->id,
                'company_id' => $empresaId,
                'catalog_question_id' => null,
                'version' => $versionNueva,
                'question_order' => $orden++,
                'question_text_es' => $pregunta->question_text_es,
                'is_active' => true,
            ]);
        }

        $this->auditoria->registrar([
            'company_id' => $empresaId,
            'user_id' => auth()->id(),
            'origin' => 'web',
            'action' => 'questionnaire.version_created',
            'entity_type' => 'complaint_category',
            'entity_id' => $categoria->id,
            'result' => 'success',
            'detail' => "Cuestionario de {$categoria->name_es} v{$versionNueva} · {$motivo}",
        ]);
    }
}
