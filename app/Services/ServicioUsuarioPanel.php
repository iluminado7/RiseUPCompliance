<?php

namespace App\Services;

use App\Enums\RolUsuario;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Alta y edicion de usuarios del panel.
 *
 * El telefono se cifra con la clave maestra, sin salt: no pertenece a
 * ninguna denuncia y no se purga por retencion. La columna users.phone
 * esta documentada como CIFRADO desde el principio, pero el INSERT del
 * sistema anterior guardaba el valor crudo.
 */
class ServicioUsuarioPanel
{
    public function __construct(
        private readonly ServicioAuditoria $auditoria,
        private readonly ServicioCifrado $cifrado,
    ) {}

    public function crear(array $datos, ?string $telefono, ?int $empresaId, array $sucursales): Usuario
    {
        return DB::transaction(function () use ($datos, $telefono, $empresaId, $sucursales) {
            $usuario = new Usuario($datos);
            $usuario->company_id = $empresaId;
            $usuario->status = 'active';
            $usuario->password_hash = Hash::make($datos['password']);
            $usuario->save();

            // El AAD incluye el id, asi que hay que cifrar despues del
            // insert: ata el valor a esta fila y solo a esta.
            if ($telefono) {
                $usuario->phone = $this->cifrado->cifrar($telefono, null, $this->contextoTelefono($usuario));
                $usuario->save();
            }

            $this->sincronizarSucursales($usuario, $sucursales);

            $this->auditoria->registrar([
                'company_id' => $empresaId,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'user.created',
                'entity_type' => 'user',
                'entity_id' => $usuario->id,
                'result' => 'success',
                'detail' => "Alta de usuario {$usuario->email} · rol: " . ($usuario->nombreRol()?->value ?? '?'),
            ]);

            return $usuario;
        });
    }

    public function actualizar(
        Usuario $usuario,
        array $datos,
        ?string $telefono,
        ?int $empresaId,
        array $sucursales,
        ?string $nuevaPassword,
    ): void {
        DB::transaction(function () use ($usuario, $datos, $telefono, $empresaId, $sucursales, $nuevaPassword) {
            $usuario->fill($datos);
            $usuario->company_id = $empresaId;

            if ($telefono !== null) {
                $usuario->phone = $telefono === ''
                    ? null
                    : $this->cifrado->cifrar($telefono, null, $this->contextoTelefono($usuario));
            }

            if ($nuevaPassword) {
                $usuario->password_hash = Hash::make($nuevaPassword);
                $usuario->password_changed_at = now();
                // Cambiarle la contrasena a alguien levanta su bloqueo por
                // intentos fallidos: si no, no podria entrar con la nueva.
                $usuario->failed_attempts = 0;
                $usuario->locked_until = null;
            }

            $usuario->save();

            $this->sincronizarSucursales($usuario, $sucursales);

            $this->auditoria->registrar([
                'company_id' => $empresaId,
                'user_id' => auth()->id(),
                'origin' => 'web',
                'action' => 'user.updated',
                'entity_type' => 'user',
                'entity_id' => $usuario->id,
                'result' => 'success',
                'detail' => "Edición de {$usuario->email}"
                    . ($nuevaPassword ? ' · contraseña restablecida' : ''),
            ]);
        });
    }

    /**
     * Telefono descifrado, para mostrar en el formulario de edicion.
     *
     * Devuelve null si no se puede descifrar: un dato ilegible no debe
     * romper la pantalla de administracion.
     */
    public function telefonoDe(Usuario $usuario): ?string
    {
        if (! $usuario->phone) {
            return null;
        }

        try {
            return $this->cifrado->descifrar($usuario->phone, null, $this->contextoTelefono($usuario));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Sucursales asignadas.
     *
     * El admin_principal recibe TODAS las activas de su empresa
     * automaticamente: es el comportamiento del original y tiene sentido,
     * porque su alcance es la empresa entera.
     */
    private function sincronizarSucursales(Usuario $usuario, array $sucursales): void
    {
        if (! $usuario->company_id) {
            $usuario->sucursales()->sync([]);

            return;
        }

        if ($usuario->nombreRol() === RolUsuario::AdminPrincipal) {
            $todas = Sucursal::withoutGlobalScopes()
                ->where('company_id', $usuario->company_id)
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            $usuario->sucursales()->sync($todas);

            return;
        }

        // Solo sucursales que existan y sean de la empresa del usuario.
        // Sin este filtro, un id inyectado en el POST asignaria una
        // sucursal de otra empresa.
        $validas = Sucursal::withoutGlobalScopes()
            ->where('company_id', $usuario->company_id)
            ->whereIn('id', $sucursales)
            ->pluck('id')
            ->all();

        $usuario->sucursales()->sync($validas);
    }

    private function contextoTelefono(Usuario $usuario): string
    {
        return 'users.phone:' . $usuario->id;
    }

    /** Roles que el usuario autenticado puede asignar. */
    public static function rolesAsignables(Usuario $autor)
    {
        $nombres = $autor->tieneRol(RolUsuario::Superadmin)
            ? ['superadmin', 'admin_principal', 'gestor', 'investigador_externo']
            : ['gestor'];  // admin_principal: solo gestores

        return Rol::whereIn('name', $nombres)->orderBy('hierarchy_level')->get();
    }
}
