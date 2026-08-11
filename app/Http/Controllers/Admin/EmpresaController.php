<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoEmpresa;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarEmpresaRequest;
use App\Models\Empresa;
use App\Services\ServicioEmpresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Alta, edicion y baja operativa de empresas. Solo superadmin.
 *
 * -- SOBRE EL SLUG --
 *
 * El slug es la URL del canal publico: /{slug}. Cambiarlo invalida los
 * carteles, mails y enlaces que la empresa ya repartio entre su gente, y
 * deja sin destino los codigos de seguimiento que los denunciantes tienen
 * anotados en un papel.
 *
 * El formulario original lo dejaba editar como un campo mas. Aca se define
 * al crear y despues se muestra sin poder editarse. Si alguna vez hace
 * falta cambiarlo, que sea una operacion deliberada y no un descuido en un
 * formulario de veinte campos.
 */
class EmpresaController extends Controller
{
    public function __construct(
        private readonly ServicioEmpresa $servicio,
    ) {}

    public function create(): View
    {
        $this->authorize('create', Empresa::class);

        return view('admin.empresas.form', [
            'empresa' => null,
            'titulo' => 'Nueva empresa',
        ]);
    }

    public function store(GuardarEmpresaRequest $request): RedirectResponse
    {
        $this->authorize('create', Empresa::class);

        try {
            $empresa = $this->servicio->crear(
                $this->datosEmpresa($request),
                $this->datosFiscales($request),
                [
                    'nombre' => $request->input('sede_nombre'),
                    'codigo' => $request->input('sede_codigo'),
                    'direccion' => $request->input('sede_direccion'),
                ] + ['slug' => null]
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.administracion.index', ['tab' => 'empresas'])
            ->with('estado', "Empresa {$empresa->name} creada.");
    }

    public function edit(Empresa $empresa): View
    {
        $this->authorize('update', $empresa);

        $empresa->load('datosFiscales');

        return view('admin.empresas.form', [
            'empresa' => $empresa,
            'titulo' => 'Editar ' . $empresa->name,
        ]);
    }

    public function update(GuardarEmpresaRequest $request, Empresa $empresa): RedirectResponse
    {
        $this->authorize('update', $empresa);

        $this->servicio->actualizar(
            $empresa,
            $this->datosEmpresa($request) + ['status' => $request->input('status')],
            $this->datosFiscales($request)
        );

        return redirect()
            ->route('admin.administracion.index', ['tab' => 'empresas'])
            ->with('estado', "Empresa {$empresa->name} actualizada.");
    }

    public function cambiarEstado(Request $request, Empresa $empresa): RedirectResponse
    {
        $this->authorize('cambiarEstado', $empresa);

        $datos = $request->validate([
            'status' => ['required', Rule::enum(EstadoEmpresa::class)],
        ]);

        $this->servicio->cambiarEstado($empresa, EstadoEmpresa::from($datos['status']));

        return redirect()
            ->route('admin.administracion.index', ['tab' => 'empresas'])
            ->with('estado', "Estado de {$empresa->name} actualizado.");
    }

    // -- Internos ------------------------------------------------

    private function datosEmpresa(GuardarEmpresaRequest $request): array
    {
        $datos = $request->only([
            'name',
            'email',
            'default_language',
            'timezone',
            'complaints_retention_days',
            'files_retention_days',
            'logs_retention_days',
        ]);

        // El slug solo se acepta al crear. En la edicion, aunque venga en
        // el POST, se ignora.
        if ($request->route('empresa') === null) {
            $datos['slug'] = $request->input('slug');
        }

        return $datos;
    }

    private function datosFiscales(GuardarEmpresaRequest $request): array
    {
        $usaGlobal = $request->boolean('uses_global_price', true);

        return [
            'tax_id' => $request->input('tax_id') ?: null,
            'legal_name' => $request->input('legal_name') ?: null,
            'vat_status' => $request->input('vat_status') ?: null,
            'fiscal_address' => $request->input('fiscal_address') ?: null,
            'billing_emails' => $request->emailsFacturacion(),
            'preferred_payment' => $request->input('preferred_payment') ?: 'bank_transfer',
            'billing_day' => (int) ($request->input('billing_day') ?: 1),
            'uses_global_price' => $usaGlobal,
            'custom_amount' => $usaGlobal ? null : $request->input('custom_amount'),
        ];
    }
}
