<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarSucursalRequest;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Services\ServicioSucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SucursalController extends Controller
{
    public function __construct(
        private readonly ServicioSucursal $servicio,
    ) {}

    public function create(): View
    {
        $this->authorize('create', Sucursal::class);

        return view('admin.sucursales.form', [
            'sucursal' => null,
            'empresas' => $this->empresasDisponibles(),
            'titulo' => 'Nueva sucursal',
        ]);
    }

    public function store(GuardarSucursalRequest $request): RedirectResponse
    {
        $this->authorize('create', Sucursal::class);

        // El company_id sale de la sesion para admin_principal, NUNCA del
        // request: aceptarlo es permitir crear sucursales en otra empresa.
        $empresaId = $request->user()->nombreRol() === RolUsuario::Superadmin
            ? (int) $request->input('company_id')
            : $request->user()->company_id;

        abort_if(! $empresaId, 422, 'Falta la empresa.');

        $this->servicio->crear($empresaId, $request->datos());

        return redirect()
            ->route('admin.administracion.index', ['tab' => 'sucursales'])
            ->with('estado', 'Sucursal creada.');
    }

    public function edit(Sucursal $sucursal): View
    {
        $this->authorize('update', $sucursal);

        return view('admin.sucursales.form', [
            'sucursal' => $sucursal,
            'empresas' => $this->empresasDisponibles(),
            'titulo' => 'Editar ' . $sucursal->name,
        ]);
    }

    public function update(GuardarSucursalRequest $request, Sucursal $sucursal): RedirectResponse
    {
        $this->authorize('update', $sucursal);

        $this->servicio->actualizar($sucursal, $request->datos());

        return redirect()
            ->route('admin.administracion.index', ['tab' => 'sucursales'])
            ->with('estado', 'Sucursal actualizada.');
    }

    private function empresasDisponibles()
    {
        return auth()->user()->nombreRol() === RolUsuario::Superadmin
            ? Empresa::orderBy('name')->get(['id', 'name'])
            : Empresa::where('id', auth()->user()->company_id)->get(['id', 'name']);
    }
}
