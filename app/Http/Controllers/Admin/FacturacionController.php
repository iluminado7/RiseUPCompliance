<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Factura;
use App\Services\ServicioFacturacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Facturacion.
 *
 * El superadmin administra todo; el admin_principal ve las facturas de su
 * empresa sin poder modificarlas.
 */
class FacturacionController extends Controller
{
    private const POR_PAGINA = 20;

    public function __construct(
        private readonly ServicioFacturacion $servicio,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Factura::class);

        $usuario = $request->user();
        $esSuperadmin = $usuario->nombreRol() === RolUsuario::Superadmin;

        // La empresa se resuelve ANTES de cargar nada. El original cargaba
        // los datos fiscales y las facturas de la empresa pedida y recién
        // después comprobaba que el admin_principal tuviera derecho a
        // verla: el redirect cortaba antes del HTML, pero cualquier cambio
        // de orden convertía eso en una fuga.
        $empresa = $this->resolverEmpresa($request, $esSuperadmin);

        $this->servicio->actualizarVencidas($empresa?->id);

        $filtros = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'paid', 'overdue', 'cancelled'])],
            'anio' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'vence_pronto' => ['nullable', 'boolean'],
        ]);

        return view('admin.facturacion.index', [
            'esSuperadmin' => $esSuperadmin,
            'empresa' => $empresa,
            'empresas' => $esSuperadmin
                ? Empresa::where('status', '!=', 'deactivated')->orderBy('name')->get(['id', 'name'])
                : collect(),
            'facturas' => $this->facturas($empresa, $esSuperadmin, $filtros),
            'filtros' => $filtros,
            'resumen' => $this->resumen($empresa, $esSuperadmin),
            'datosFiscales' => $empresa?->datosFiscales,
            'anios' => $this->aniosConFacturas($empresa),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $this->authorize('create', Factura::class);

        $datos = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'billing_month' => ['required', 'integer', 'min:1', 'max:12'],
            'billing_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'subtotal_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'tax_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'total_amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'currency_code' => ['required', Rule::in(['ARS', 'USD', 'EUR'])],
            'concept' => ['nullable', 'string', 'max:1000'],
            'issue_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:issue_date'],
            'invoice_number' => ['nullable', 'string', 'max:20'],
            'afip_cae' => ['nullable', 'string', 'max:20'],
            'afip_cae_expiry' => ['nullable', 'date_format:Y-m-d'],
        ], [], [
            'total_amount' => 'importe total',
            'issue_date' => 'fecha de emisión',
        ]);

        $empresa = Empresa::findOrFail($datos['company_id']);
        unset($datos['company_id']);

        try {
            $this->servicio->crear($empresa, $datos);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.facturacion.index', ['empresa_id' => $empresa->id])
            ->with('estado', 'Factura creada.');
    }

    public function actualizar(Request $request, Factura $factura): RedirectResponse
    {
        $this->authorize('update', $factura);

        $datos = $request->validate([
            'subtotal_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'concept' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'invoice_number' => ['nullable', 'string', 'max:20'],
            'afip_cae' => ['nullable', 'string', 'max:20'],
            'afip_cae_expiry' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $this->servicio->actualizar($factura, $datos);

        return redirect()
            ->route('admin.facturacion.index', ['empresa_id' => $factura->company_id])
            ->with('estado', 'Factura actualizada.');
    }

    public function pagar(Request $request, Factura $factura): RedirectResponse
    {
        $this->authorize('update', $factura);

        $datos = $request->validate([
            'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], [], ['payment_date' => 'fecha de pago']);

        $this->servicio->registrarPago($factura, $datos['payment_date']);

        return redirect()
            ->route('admin.facturacion.index', ['empresa_id' => $factura->company_id])
            ->with('estado', 'Pago registrado.');
    }

    public function anular(Request $request, Factura $factura): RedirectResponse
    {
        $this->authorize('update', $factura);

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        $this->servicio->anular($factura, $datos['motivo']);

        return redirect()
            ->route('admin.facturacion.index', ['empresa_id' => $factura->company_id])
            ->with('estado', 'Factura anulada.');
    }

    // -- Internos ------------------------------------------------

    private function resolverEmpresa(Request $request, bool $esSuperadmin): ?Empresa
    {
        if (! $esSuperadmin) {
            // Para el admin_principal la empresa sale SIEMPRE de su sesión.
            // Lo que venga en la URL se ignora.
            return $request->user()->empresa;
        }

        $id = (int) $request->query('empresa_id');

        return $id ? Empresa::find($id) : null;
    }

    private function facturas(?Empresa $empresa, bool $esSuperadmin, array $filtros)
    {
        return Factura::withoutGlobalScopes()
            ->with('empresa:id,name')
            ->when($empresa, fn (Builder $q, $e) => $q->where('company_id', $e->id))
            // Un admin_principal sin empresa resuelta no debería ver nada:
            // sin este filtro vería la facturación de todos los clientes.
            ->when(! $empresa && ! $esSuperadmin, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->when($filtros['status'] ?? null, fn (Builder $q, $v) => $q->where('payment_status', $v))
            ->when($filtros['anio'] ?? null, fn (Builder $q, $v) => $q->where('billing_year', $v))
            ->when($filtros['vence_pronto'] ?? null, fn (Builder $q) => $q
                ->where('payment_status', 'pending')
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()]))
            ->orderByDesc('billing_year')
            ->orderByDesc('billing_month')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();
    }

    private function resumen(?Empresa $empresa, bool $esSuperadmin): array
    {
        $base = fn () => Factura::withoutGlobalScopes()
            ->when($empresa, fn (Builder $q, $e) => $q->where('company_id', $e->id))
            ->when(! $empresa && ! $esSuperadmin, fn (Builder $q) => $q->whereRaw('1 = 0'));

        return [
            'pendientes' => $base()->where('payment_status', 'pending')->count(),
            'vencidas' => $base()->where('payment_status', 'overdue')->count(),
            'adeudado' => $base()->whereIn('payment_status', ['pending', 'overdue'])->sum('total_amount'),
            'cobrado_anio' => $base()->where('payment_status', 'paid')
                ->where('billing_year', now()->year)->sum('total_amount'),
        ];
    }

    private function aniosConFacturas(?Empresa $empresa)
    {
        return Factura::withoutGlobalScopes()
            ->when($empresa, fn (Builder $q, $e) => $q->where('company_id', $e->id))
            ->distinct()
            ->orderByDesc('billing_year')
            ->pluck('billing_year');
    }
}
