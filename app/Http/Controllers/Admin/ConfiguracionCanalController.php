<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\DocumentoLegal;
use App\Models\Empresa;
use App\Models\Pregunta;
use App\Models\Vinculo;
use App\Services\ServicioConfiguracionCanal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configuracion del canal por empresa. Solo superadmin.
 *
 * Cada empresa tiene su configuracion independiente: lo que se cambia en
 * una no afecta a las demas.
 */
class ConfiguracionCanalController extends Controller
{
    private const TABS = ['categorias', 'areas', 'cuestionario', 'legal'];

    public function __construct(
        private readonly ServicioConfiguracionCanal $servicio,
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'categorias');

        if (! in_array($tab, self::TABS, true)) {
            $tab = 'categorias';
        }

        $empresa = $request->query('empresa_id')
            ? Empresa::find($request->query('empresa_id'))
            : null;

        $datos = [
            'empresas' => Empresa::orderBy('name')->get(['id', 'name', 'slug', 'status']),
            'empresa' => $empresa,
            'tab' => $tab,
        ];

        if (! $empresa) {
            return view('admin.configuracion.index', $datos);
        }

        $datos += match ($tab) {
            'categorias' => [
                'categorias' => Categoria::activas()->orderBy('name_es')->get(),
                'relaciones' => Vinculo::activos()->orderBy('name_es')->get(),
                'seleccionCategorias' => $empresa->categorias->pluck('id')->all(),
                'seleccionRelaciones' => $empresa->vinculos->pluck('id')->all(),
            ],
            'areas' => [
                'areas' => Area::activas()->get(),
                'cargos' => Cargo::activos()->get(),
                'seleccionAreas' => $empresa->areas->pluck('id')->all(),
                'seleccionCargos' => $empresa->cargos->pluck('id')->all(),
            ],
            'cuestionario' => $this->datosCuestionario($request, $empresa),
            'legal' => $this->datosLegal($empresa),
        };

        return view('admin.configuracion.index', $datos);
    }

    // -- Acciones ------------------------------------------------

    public function guardarCategorias(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validate([
            'categorias' => ['nullable', 'array'],
            'categorias.*' => ['integer', 'exists:complaint_categories,id'],
            'relaciones' => ['nullable', 'array'],
            'relaciones.*' => ['integer', 'exists:reporter_relationships,id'],
        ]);

        $this->servicio->guardarCategoriasYRelaciones(
            $empresa,
            $datos['categorias'] ?? [],
            $datos['relaciones'] ?? [],
        );

        return $this->volver($empresa, 'categorias', 'Categorías y relaciones guardadas.');
    }

    public function guardarAreas(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validate([
            'areas' => ['nullable', 'array'],
            'areas.*' => ['integer', 'exists:reported_areas,id'],
            'cargos' => ['nullable', 'array'],
            'cargos.*' => ['integer', 'exists:reported_positions,id'],
        ]);

        $this->servicio->guardarAreasYCargos(
            $empresa,
            $datos['areas'] ?? [],
            $datos['cargos'] ?? [],
        );

        return $this->volver($empresa, 'areas', 'Áreas y cargos guardados.');
    }

    public function importarCatalogo(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validate([
            'categoria_id' => ['required', 'integer', 'exists:complaint_categories,id'],
        ]);

        $categoria = Categoria::findOrFail($datos['categoria_id']);
        $cantidad = $this->servicio->importarDelCatalogo($empresa, $categoria);

        $mensaje = $cantidad > 0
            ? "Se importaron {$cantidad} preguntas del catálogo."
            : 'El catálogo no tiene preguntas para esta categoría.';

        return $this->volver($empresa, 'cuestionario', $mensaje, $categoria->id);
    }

    public function guardarCuestionario(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validate([
            'categoria_id' => ['required', 'integer', 'exists:complaint_categories,id'],
            'preguntas' => ['required', 'array', 'min:1', 'max:50'],
            'preguntas.*.texto' => ['required', 'string', 'max:2000'],
            'preguntas.*.catalogo_id' => ['nullable', 'integer'],
        ], [], ['preguntas.*.texto' => 'texto de la pregunta']);

        $categoria = Categoria::findOrFail($datos['categoria_id']);

        $version = $this->servicio->guardarVersion($empresa, $categoria, $datos['preguntas']);

        return $this->volver(
            $empresa,
            'cuestionario',
            "Versión {$version} del cuestionario guardada.",
            $categoria->id
        );
    }

    public function guardarLegal(Request $request, Empresa $empresa): RedirectResponse
    {
        $datos = $request->validate([
            'aviso_privacidad' => ['nullable', 'string', 'max:20000'],
            'emails_alerta' => ['nullable', 'string', 'max:2000'],
        ]);

        $emails = $this->emailsValidos($datos['emails_alerta'] ?? '');

        if ($emails === null) {
            return back()->withInput()->with(
                'error',
                'Alguna de las direcciones de correo no es válida. Revisá la lista.'
            );
        }

        $this->servicio->guardarEmailsAlerta($empresa, $emails);

        $mensaje = 'Configuración guardada.';

        if (! empty($datos['aviso_privacidad'])) {
            $documento = $this->servicio->publicarAvisoPrivacidad($empresa, $datos['aviso_privacidad']);

            $mensaje = $documento
                ? "Aviso de privacidad publicado como {$documento->version}."
                : 'Configuración guardada. El aviso de privacidad no cambió.';
        }

        return $this->volver($empresa, 'legal', $mensaje);
    }

    // -- Internos ------------------------------------------------

    private function datosCuestionario(Request $request, Empresa $empresa): array
    {
        $categoriaId = (int) $request->query('categoria_id');

        $habilitadas = $empresa->categorias()->orderBy('name_es')->get();

        $categoria = $categoriaId
            ? $habilitadas->firstWhere('id', $categoriaId)
            : null;

        $preguntas = collect();
        $version = 0;

        if ($categoria) {
            $preguntas = Pregunta::where('category_id', $categoria->id)
                ->where('company_id', $empresa->id)
                ->where('is_active', true)
                ->orderBy('question_order')
                ->get();

            $version = (int) Pregunta::where('category_id', $categoria->id)
                ->where('company_id', $empresa->id)
                ->max('version');
        }

        return [
            'categoriasHabilitadas' => $habilitadas,
            'categoriaSeleccionada' => $categoria,
            'preguntas' => $preguntas,
            'versionActual' => $version,
        ];
    }

    private function datosLegal(Empresa $empresa): array
    {
        $config = $empresa->public_configuration ?? [];

        $emails = $config['notification_emails'] ?? [];

        // El original guardaba esto como string crudo, así que puede haber
        // datos viejos en cualquiera de los dos formatos.
        if (is_string($emails)) {
            $emails = array_filter(array_map('trim', explode(',', $emails)));
        }

        return [
            'avisoPrivacidad' => $config['privacy_notice_text'] ?? '',
            'emailsAlerta' => implode(', ', $emails),
            'versionesAviso' => DocumentoLegal::where('company_id', $empresa->id)
                ->where('document_type', 'privacy_notice')
                ->orderByDesc('published_at')
                ->get(),
        ];
    }

    /**
     * Lista de emails validos, o null si alguno no lo es.
     *
     * @return array<string>|null
     */
    private function emailsValidos(string $texto): ?array
    {
        if (trim($texto) === '') {
            return [];
        }

        $emails = array_filter(array_map('trim', explode(',', $texto)));
        $validos = [];

        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            $validos[] = $email;
        }

        return array_values(array_unique($validos));
    }

    private function volver(Empresa $empresa, string $tab, string $mensaje, ?int $categoriaId = null): RedirectResponse
    {
        return redirect()
            ->route('admin.configuracion.index', array_filter([
                'empresa_id' => $empresa->id,
                'tab' => $tab,
                'categoria_id' => $categoriaId,
            ]))
            ->with('estado', $mensaje);
    }
}
