<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Pregunta;
use App\Services\ServicioBorradorDenuncia;
use App\Services\ServicioDenunciaPublica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Canal publico de denuncias, en siete pasos.
 *
 * SIN SESION AUTENTICADA. Quien entra aca no tiene ni va a tener usuario
 * en el sistema: ligarlo al login destruiria el anonimato, que es la
 * propuesta de valor del producto (2.1 del brief).
 *
 * La empresa se resuelve SIEMPRE desde el slug de la URL. Es la excepcion
 * documentada del TenantScope: sin sesion no hay de donde sacar el tenant.
 *
 * Cada paso valida y guarda en sesion. Con los pasos en el navegador --
 * como hacia index.php -- todo lo escrito queda en el DOM hasta cerrar la
 * pestana, y en una computadora compartida eso importa.
 */
class CanalController extends Controller
{
    private const PASOS = [
        1 => 'Anonimato',
        2 => 'Dónde ocurrió',
        3 => 'A quién denunciás',
        4 => 'Qué pasó',
        5 => 'Detalles',
        6 => 'Evidencia',
        7 => 'Revisar y enviar',
    ];

    public function __construct(
        private readonly ServicioDenunciaPublica $servicio,
        private readonly ServicioBorradorDenuncia $borrador,
    ) {}

    /**
     * Entrada al canal de una empresa.
     *
     * Va directo al formulario. La presentación del sistema está en la
     * portada de GoHarv; repetirla por empresa sería un paso más entre la
     * decisión de denunciar y el formulario.
     *
     * Se resuelve la empresa igual, para que un slug inexistente o de una
     * empresa con el canal cerrado devuelva 404 acá y no en el paso 1.
     */
    public function inicio(string $slug): RedirectResponse
    {
        $this->resolverEmpresa($slug);

        return redirect()->route('canal.paso', [$slug, 1]);
    }

    public function paso(Request $request, string $slug, int $paso): View|RedirectResponse
    {
        $empresa = $this->resolverEmpresa($slug);

        $permitido = $this->borrador->pasoPermitido($empresa, $paso);

        if ($permitido !== $paso) {
            return redirect()->route('canal.paso', [$slug, $permitido]);
        }

        return view('publico.canal.paso', [
            'empresa' => $empresa,
            'paso' => $paso,
            'etiquetas' => self::PASOS,
            'guardado' => $this->borrador->paso($empresa, $paso),
            'completo' => $this->borrador->completo($empresa),
            'adjunto' => $this->borrador->adjunto($empresa),
        ] + $this->opciones($empresa, $paso));
    }

    public function guardar(Request $request, string $slug, int $paso): RedirectResponse
    {
        $empresa = $this->resolverEmpresa($slug);

        abort_unless($paso >= 1 && $paso <= ServicioBorradorDenuncia::PASOS, 404);

        // Campo trampa: los formularios automatizados completan todo lo que
        // encuentran. Un humano no lo ve, así que si viene lleno no es uno.
        if ($request->filled('website')) {
            Log::info('[CANAL] Envío descartado por honeypot', ['empresa' => $empresa->slug]);

            return redirect()->route('canal.paso', [$slug, 1]);
        }

        $datos = $this->validarPaso($request, $empresa, $paso);

        if ($paso === 6 && $request->hasFile('archivo')) {
            $this->borrador->guardarAdjunto($empresa, $request->file('archivo'));
        }

        if ($paso === 6 && $request->boolean('quitar_archivo')) {
            $this->borrador->borrarAdjunto($empresa);
        }

        $this->borrador->guardarPaso($empresa, $paso, $datos);

        if ($paso === ServicioBorradorDenuncia::PASOS) {
            return $this->enviar($empresa);
        }

        return redirect()->route('canal.paso', [$slug, $paso + 1]);
    }

    public function volver(string $slug, int $paso): RedirectResponse
    {
        $this->resolverEmpresa($slug);

        return redirect()->route('canal.paso', [$slug, max(1, $paso - 1)]);
    }

    public function abandonar(string $slug): RedirectResponse
    {
        $empresa = $this->resolverEmpresa($slug);

        $this->borrador->descartar($empresa);

        return redirect()->route('canal.paso', [$slug, 1])
            ->with('estado', 'Descartamos lo que habías cargado.');
    }

    public function confirmacion(string $slug): View|RedirectResponse
    {
        $empresa = $this->resolverEmpresa($slug);

        $tracking = session('tracking_code');

        if (! $tracking) {
            return redirect()->route('canal.paso', [$slug, 1]);
        }

        return view('publico.canal.confirmacion', [
            'empresa' => $empresa,
            'tracking' => $tracking,
        ]);
    }

    // -- Internos ------------------------------------------------

    private function enviar(Empresa $empresa): RedirectResponse
    {
        $datos = $this->borrador->completo($empresa);
        $adjunto = $this->borrador->adjunto($empresa);

        try {
            $tracking = $this->servicio->crear(
                $empresa,
                $this->normalizar($datos),
                $adjunto ? $this->recuperarAdjunto($adjunto) : null
            );
        } catch (RuntimeException $e) {
            return redirect()->route('canal.paso', [$empresa->slug, 6])
                ->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('[CANAL] No se pudo registrar la denuncia', [
                'empresa' => $empresa->slug,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('canal.paso', [$empresa->slug, 7])
                ->with('error', 'No se pudo registrar la denuncia. Intentá de nuevo en unos minutos.');
        }

        // El borrador se descarta apenas la denuncia entra: los datos
        // personales no deben sobrevivir al envío en la sesión.
        $this->borrador->descartar($empresa);

        return redirect()
            ->route('canal.confirmacion', $empresa->slug)
            ->with('tracking_code', $tracking);
    }

    /** Reconstruye un UploadedFile desde el temporal del borrador. */
    private function recuperarAdjunto(array $adjunto): UploadedFile
    {
        $ruta = storage_path('app/' . $adjunto['ruta']);

        abort_unless(is_file($ruta), 422, 'El archivo adjunto ya no está disponible.');

        return new UploadedFile(
            $ruta,
            $adjunto['nombre_original'],
            $adjunto['mime_declarado'],
            null,
            true  // ya está en disco, no vino de un upload de este request
        );
    }

    private function normalizar(array $datos): array
    {
        $esAnonima = ($datos['anonimo'] ?? 'si') === 'si';

        return [
            'es_anonima' => $esAnonima,
            'branch_id' => $datos['branch_id'] ?? null,
            'category_id' => $datos['category_id'],
            'relationship_id' => $esAnonima ? null : ($datos['relationship_id'] ?? null),
            'relationship_other' => null,
            'area_id' => $datos['area_id'] ?? null,
            'area_other' => null,
            'position_id' => $datos['position_id'] ?? null,
            'position_other' => null,
            'denunciado_nombre' => $datos['denunciado_nombre'],
            'denunciado_apellido' => $datos['denunciado_apellido'],
            'incident_date' => $datos['incident_date'],
            'respuestas' => $datos['respuestas'] ?? [],
            'nombre' => $esAnonima ? null : ($datos['nombre'] ?? null),
            'apellido' => $esAnonima ? null : ($datos['apellido'] ?? null),
            'email' => $esAnonima ? null : ($datos['email'] ?? null),
            'telefono' => $esAnonima ? null : ($datos['telefono'] ?? null),
            'documento' => $esAnonima ? null : ($datos['documento'] ?? null),
            'genero' => $esAnonima ? null : ($datos['genero'] ?? null),
        ];
    }

    /**
     * Empresa del slug, solo si su canal esta abierto.
     *
     * Una empresa suspendida devuelve 404 y no un mensaje distinto: decir
     * "esta empresa existe pero su canal esta cerrado" confirma que es
     * cliente del sistema, y eso no es informacion publica.
     */
    private function resolverEmpresa(string $slug): Empresa
    {
        $empresa = Empresa::where('slug', $slug)->first();

        abort_unless($empresa && $empresa->canalPublicoAbierto(), 404);

        return $empresa;
    }

    /** Opciones que necesita cada paso para dibujarse. */
    private function opciones(Empresa $empresa, int $paso): array
    {
        return match ($paso) {
            1 => [
                'vinculos' => $empresa->vinculos()->wherePivot('is_active', true)
                    ->where('reporter_relationships.is_active', true)->get(),
            ],
            2 => [
                'sucursales' => $empresa->sucursales()->where('is_active', true)
                    ->orderByDesc('is_headquarter')->orderBy('name')->get(),
            ],
            3 => [
                'areas' => $empresa->areas()->wherePivot('is_active', true)
                    ->where('reported_areas.is_active', true)
                    ->orderBy('company_areas.display_order')->get(),
                'cargos' => $empresa->cargos()->wherePivot('is_active', true)
                    ->where('reported_positions.is_active', true)
                    ->orderBy('company_positions.display_order')->get(),
            ],
            4 => [
                'categorias' => $this->categorias($empresa),
            ],
            5 => [
                'preguntas' => $this->preguntasDeCategoria(
                    $empresa,
                    (int) ($this->borrador->completo($empresa)['category_id'] ?? 0)
                ),
            ],
            7 => [
                'resumen' => $this->resumen($empresa),
                'avisoPrivacidad' => $empresa->public_configuration['privacy_notice_text'] ?? null,
            ],
            default => [],
        };
    }

    private function categorias(Empresa $empresa)
    {
        return $empresa->categorias()
            ->wherePivot('is_active', true)
            ->where('complaint_categories.is_active', true)
            ->orderBy('name_es')
            ->get();
    }

    /** @return array<string> enunciados */
    private function preguntasDeCategoria(Empresa $empresa, int $categoriaId): array
    {
        if (! $categoriaId) {
            return [];
        }

        // Las propias de la empresa ganan sobre las del catálogo.
        $propias = Pregunta::where('category_id', $categoriaId)
            ->where('company_id', $empresa->id)
            ->where('is_active', true)
            ->orderBy('question_order')
            ->pluck('question_text_es');

        if ($propias->isNotEmpty()) {
            return $propias->all();
        }

        return Pregunta::where('category_id', $categoriaId)
            ->whereNull('company_id')
            ->where('is_active', true)
            ->orderBy('question_order')
            ->pluck('question_text_es')
            ->all();
    }

    /** Resumen legible para el último paso. */
    private function resumen(Empresa $empresa): array
    {
        $datos = $this->borrador->completo($empresa);
        $esAnonima = ($datos['anonimo'] ?? 'si') === 'si';

        $nombreDe = function (string $modelo, $id) {
            if (! $id) {
                return null;
            }

            $registro = $modelo::find($id);

            return $registro?->name_es ?? $registro?->name;
        };

        return [
            'anonimo' => $esAnonima,
            'contacto' => $esAnonima ? null : trim(($datos['nombre'] ?? '') . ' ' . ($datos['apellido'] ?? '')),
            'email' => $esAnonima ? null : ($datos['email'] ?? null),
            'sucursal' => $nombreDe(\App\Models\Sucursal::class, $datos['branch_id'] ?? null),
            'denunciado' => trim(($datos['denunciado_nombre'] ?? '') . ' ' . ($datos['denunciado_apellido'] ?? '')),
            'area' => $nombreDe(\App\Models\Area::class, $datos['area_id'] ?? null),
            'cargo' => $nombreDe(\App\Models\Cargo::class, $datos['position_id'] ?? null),
            'categoria' => $nombreDe(Categoria::class, $datos['category_id'] ?? null),
            'fecha' => $datos['incident_date'] ?? null,
            'respuestas' => array_filter($datos['respuestas'] ?? []),
        ];
    }

    private function validarPaso(Request $request, Empresa $empresa, int $paso): array
    {
        $completo = $this->borrador->completo($empresa);
        $esAnonima = ($completo['anonimo'] ?? $request->input('anonimo')) === 'si';

        $reglas = match ($paso) {
            1 => $this->reglasPaso1($request),
            2 => [
                'branch_id' => [
                    'nullable',
                    Rule::in($empresa->sucursales()->where('is_active', true)->pluck('id')),
                ],
            ],
            3 => [
                'denunciado_nombre' => ['required', 'string', 'max:50'],
                'denunciado_apellido' => ['required', 'string', 'max:50'],
                'area_id' => ['nullable', Rule::in($empresa->areas()->pluck('reported_areas.id'))],
                'position_id' => ['nullable', Rule::in($empresa->cargos()->pluck('reported_positions.id'))],
            ],
            4 => [
                'category_id' => [
                    'required',
                    Rule::in($empresa->categorias()->pluck('complaint_categories.id')),
                ],
            ],
            5 => [
                'incident_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'respuestas' => ['nullable', 'array', 'max:50'],
                'respuestas.*' => ['nullable', 'string', 'max:5000'],
            ],
            6 => [
                'archivo' => [
                    'nullable', 'file', 'max:25600',
                    // mimetypes valida el tipo REAL del archivo, no la extensión.
                    'mimetypes:image/jpeg,image/png,image/webp,application/pdf,'
                        . 'application/msword,'
                        . 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ],
                'quitar_archivo' => ['nullable', 'boolean'],
            ],
            7 => [
                'acepta_privacidad' => ['accepted'],
            ],
            default => [],
        };

        $mensajes = [
            'acepta_privacidad.accepted' => 'Tenés que aceptar el aviso de privacidad para enviar la denuncia.',
            'category_id.in' => 'La categoría seleccionada no está disponible en este canal.',
            'archivo.mimetypes' => 'El archivo tiene que ser una imagen, un PDF o un documento de Word.',
            'archivo.max' => 'El archivo no puede superar los 25 MB.',
            'incident_date.before_or_equal' => 'La fecha no puede ser posterior a hoy.',
        ];

        $validados = $request->validate($reglas, $mensajes);

        // El archivo se maneja aparte: no entra en la sesión.
        unset($validados['archivo'], $validados['quitar_archivo']);

        return $validados;
    }

    private function reglasPaso1(Request $request): array
    {
        $reglas = ['anonimo' => ['required', Rule::in(['si', 'no'])]];

        if ($request->input('anonimo') === 'no') {
            $reglas += [
                'nombre' => ['required', 'string', 'max:30'],
                'apellido' => ['required', 'string', 'max:30'],
                'email' => ['required', 'email', 'max:255'],
                'telefono' => ['nullable', 'string', 'max:20'],
                'documento' => ['nullable', 'string', 'max:20'],
                'genero' => ['nullable', 'string', 'max:50'],
                'relationship_id' => ['nullable', 'integer'],
            ];
        }

        return $reglas;
    }
}
