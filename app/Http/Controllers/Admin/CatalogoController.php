<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Pregunta;
use App\Models\Vinculo;
use App\Services\ServicioCatalogo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Catalogo global del sistema. Solo superadmin (lo restringe la ruta).
 *
 * Es la biblioteca: aca se definen las opciones que despues cada empresa
 * elige desde Configuracion del Canal.
 */
class CatalogoController extends Controller
{
    /** tipo => [clase, etiqueta singular, tiene descripcion, tiene orden] */
    private const TIPOS = [
        'categorias' => [Categoria::class, 'categoría', true, false],
        'relaciones' => [Vinculo::class, 'relación', true, false],
        'areas' => [Area::class, 'área', true, true],
        'cargos' => [Cargo::class, 'cargo', true, true],
    ];

    public function __construct(
        private readonly ServicioCatalogo $servicio,
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'categorias');

        if (! array_key_exists($tab, self::TIPOS)) {
            $tab = 'categorias';
        }

        $busqueda = trim((string) $request->query('q'));

        [$clase] = self::TIPOS[$tab];

        $items = $clase::query()
            ->when($busqueda, fn (Builder $b, $v) => $b->where(fn (Builder $q) => $q
                ->where('name_es', 'like', '%' . $v . '%')
                ->orWhere('code', 'like', '%' . $v . '%')))
            ->when($tab === 'categorias', fn (Builder $b) => $b
                ->withCount(['preguntas as preguntas_count' => fn (Builder $q) => $q
                    ->whereNull('company_id')->where('is_active', true)]))
            ->orderBy($tab === 'areas' || $tab === 'cargos' ? 'display_order' : 'name_es')
            ->get();

        return view('admin.catalogo.index', [
            'tab' => $tab,
            'items' => $items,
            'busqueda' => $busqueda,
            'etiqueta' => self::TIPOS[$tab][1],
            'tieneOrden' => self::TIPOS[$tab][3],
            'sugerencias' => $clase::orderBy('name_es')
                ->get(['name_es', 'code'])
                ->map(fn ($i) => $i->name_es . ' (' . $i->code . ')'),
        ]);
    }

    // -- Alta y edicion de items ---------------------------------

    public function crear(Request $request, string $tipo): RedirectResponse
    {
        $config = $this->configDe($tipo);

        $datos = $this->validarItem($request, $config, null);

        $this->servicio->crearItem($config[0], $datos, $this->nombreEntidad($tipo));

        return redirect()
            ->route('admin.catalogo.index', ['tab' => $tipo])
            ->with('estado', ucfirst($config[1]) . ' creada.');
    }

    public function actualizar(Request $request, string $tipo, int $id): RedirectResponse
    {
        $config = $this->configDe($tipo);

        /** @var \Illuminate\Database\Eloquent\Model $item */
        $item = $config[0]::findOrFail($id);

        $datos = $this->validarItem($request, $config, $item->id);

        // El codigo es permanente: cambiarlo rompe la trazabilidad de las
        // denuncias ya vinculadas a este item. Se muestra, no se edita.
        unset($datos['code']);

        $this->servicio->actualizarItem($item, $datos, $this->nombreEntidad($tipo));

        return redirect()
            ->route('admin.catalogo.index', ['tab' => $tipo])
            ->with('estado', ucfirst($config[1]) . ' actualizada.');
    }

    // -- Cuestionario base ---------------------------------------

    public function preguntas(Categoria $categoria): View
    {
        $preguntas = Pregunta::where('category_id', $categoria->id)
            ->whereNull('company_id')
            ->where('is_active', true)
            ->orderBy('question_order')
            ->get();

        $empresasAfectadas = Pregunta::where('category_id', $categoria->id)
            ->whereNotNull('company_id')
            ->where('is_active', true)
            ->whereNotNull('catalog_question_id')
            ->distinct('company_id')
            ->count('company_id');

        return view('admin.catalogo.preguntas', [
            'categoria' => $categoria,
            'preguntas' => $preguntas,
            'empresasAfectadas' => $empresasAfectadas,
        ]);
    }

    public function agregarPregunta(Request $request, Categoria $categoria): RedirectResponse
    {
        $datos = $request->validate([
            'question_text_es' => ['required', 'string', 'max:2000'],
        ]);

        $this->servicio->agregarPregunta($categoria, $datos['question_text_es']);

        return redirect()
            ->route('admin.catalogo.preguntas', $categoria)
            ->with('estado', 'Pregunta agregada y propagada a las empresas vinculadas.');
    }

    public function editarPregunta(Request $request, Categoria $categoria, Pregunta $pregunta): RedirectResponse
    {
        abort_unless($pregunta->category_id === $categoria->id && $pregunta->company_id === null, 404);

        $datos = $request->validate([
            'question_text_es' => ['required', 'string', 'max:2000'],
        ]);

        $this->servicio->editarPregunta($pregunta, $datos['question_text_es']);

        return redirect()
            ->route('admin.catalogo.preguntas', $categoria)
            ->with('estado', 'Pregunta actualizada. Las empresas vinculadas recibieron una versión nueva.');
    }

    public function desactivarPregunta(Categoria $categoria, Pregunta $pregunta): RedirectResponse
    {
        abort_unless($pregunta->category_id === $categoria->id && $pregunta->company_id === null, 404);

        $this->servicio->desactivarPregunta($pregunta);

        return redirect()
            ->route('admin.catalogo.preguntas', $categoria)
            ->with('estado', 'Pregunta retirada del catálogo.');
    }

    // -- Internos ------------------------------------------------

    private function configDe(string $tipo): array
    {
        abort_unless(array_key_exists($tipo, self::TIPOS), 404);

        return self::TIPOS[$tipo];
    }

    private function validarItem(Request $request, array $config, ?int $ignorar): array
    {
        [$clase, , $tieneDescripcion, $tieneOrden] = $config;

        $tabla = (new $clase)->getTable();

        $reglas = [
            'name_es' => ['required', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($ignorar === null) {
            $reglas['code'] = [
                'required', 'string', 'max:50',
                'regex:/^[a-zA-Z0-9_\-]+$/',
                Rule::unique($tabla, 'code'),
            ];
        }

        if ($tieneDescripcion) {
            $reglas['description_es'] = ['nullable', 'string', 'max:2000'];
        }

        if ($tieneOrden) {
            $reglas['display_order'] = ['nullable', 'integer', 'min:0', 'max:32767'];
        }

        $datos = $request->validate($reglas, [
            'code.regex' => 'El código solo admite letras, números, guiones medios y guiones bajos.',
            'code.unique' => 'Ya existe un ítem con ese código.',
        ]);

        $datos['is_active'] = $request->boolean('is_active');

        return $datos;
    }

    private function nombreEntidad(string $tipo): string
    {
        return match ($tipo) {
            'categorias' => 'complaint_category',
            'relaciones' => 'reporter_relationship',
            'areas' => 'reported_area',
            'cargos' => 'reported_position',
        };
    }
}
