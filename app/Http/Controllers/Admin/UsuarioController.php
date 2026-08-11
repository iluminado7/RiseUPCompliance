<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Services\ServicioUsuarioPanel;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function __construct(
        private readonly ServicioUsuarioPanel $servicio,
    ) {}

    public function create(): View
    {
        $this->authorize('create', Usuario::class);

        return view('admin.usuarios.form', $this->datosFormulario(null));
    }

    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        $this->authorize('create', Usuario::class);

        $usuario = $this->servicio->crear(
            $request->only(['first_name', 'last_name', 'email', 'role_id', 'password']),
            $request->input('phone'),
            $this->empresaDestino($request),
            $request->sucursales(),
        );

        return redirect()
            ->route('admin.administracion.index', ['tab' => 'usuarios'])
            ->with('estado', "Usuario {$usuario->email} creado.");
    }

    public function edit(Usuario $usuario): View
    {
        $this->authorize('update', $usuario);

        return view('admin.usuarios.form', $this->datosFormulario($usuario));
    }

    public function update(GuardarUsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        $this->authorize('update', $usuario);

        $this->servicio->actualizar(
            $usuario,
            $request->only(['first_name', 'last_name', 'email', 'role_id', 'status']),
            $request->input('phone'),
            $this->empresaDestino($request),
            $request->sucursales(),
            $request->input('nueva_password') ?: null,
        );

        return redirect()
            ->route('admin.administracion.index', ['tab' => 'usuarios'])
            ->with('estado', "Usuario {$usuario->email} actualizado.");
    }

    /**
     * Empresa a la que pertenece el usuario.
     *
     * Para admin_principal sale SIEMPRE de su sesion. El original tomaba
     * company_id del POST en la edicion, lo que permitia mover usuarios
     * entre empresas.
     */
    private function empresaDestino(GuardarUsuarioRequest $request): ?int
    {
        if ($request->user()->nombreRol() !== RolUsuario::Superadmin) {
            return $request->user()->company_id;
        }

        return $request->input('company_id') ? (int) $request->input('company_id') : null;
    }

    private function datosFormulario(?Usuario $usuario): array
    {
        $autor = auth()->user();
        $esSuperadmin = $autor->nombreRol() === RolUsuario::Superadmin;

        $empresaSucursales = $usuario?->company_id ?? $autor->company_id;

        return [
            'usuario' => $usuario,
            'titulo' => $usuario ? 'Editar ' . $usuario->nombreCompleto() : 'Nuevo usuario',
            'esSuperadmin' => $esSuperadmin,
            'roles' => ServicioUsuarioPanel::rolesAsignables($autor),
            'empresas' => $esSuperadmin
                ? Empresa::orderBy('name')->get(['id', 'name'])
                : Empresa::where('id', $autor->company_id)->get(['id', 'name']),
            'sucursales' => $empresaSucursales
                ? Sucursal::withoutGlobalScopes()
                    ->where('company_id', $empresaSucursales)
                    ->where('is_active', true)
                    ->orderByDesc('is_headquarter')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : collect(),
            'sucursalesAsignadas' => $usuario?->sucursales->pluck('id')->all() ?? [],
            'telefono' => $usuario ? $this->servicio->telefonoDe($usuario) : null,
        ];
    }
}
