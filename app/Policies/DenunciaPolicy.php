<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Denuncia;
use App\Models\Usuario;

/**
 * Autorización sobre denuncias.
 *
 * El aislamiento entre empresas NO se decide acá: lo resuelve TenantScope,
 * que hace que una denuncia de otra empresa directamente no exista para
 * las consultas de este usuario. findOrFail devuelve 404, que es lo que
 * pide §6.1 — un 403 confirmaría que el recurso existe.
 *
 * Esta Policy decide lo otro: qué puede hacer cada rol DENTRO de su
 * empresa. Todas las reglas están portadas de ver_denuncia.php.
 */
class DenunciaPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->nombreRol() !== null;
    }

    public function view(Usuario $usuario, Denuncia $denuncia): bool
    {
        if ($usuario->esSuperadmin()) {
            return true;
        }

        if (! $this->mismaEmpresa($usuario, $denuncia)) {
            return false;
        }

        if ($usuario->nombreRol()?->veTodaLaEmpresa()) {
            return true;
        }

        return $this->estaAsignado($usuario, $denuncia);
    }

    /**
     * Cambiar el estado de la denuncia.
     *
     * Portado de ver_denuncia.php:
     *   superadmin y admin_principal siempre;
     *   gestor e investigador_externo solo si están asignados.
     *
     * OJO: el investigador externo SÍ puede cambiar estados, pese a que
     * el manual de roles lo describe como "solo lectura". Es el
     * comportamiento actual y se porta tal cual.
     *
     * H-011: el gestor solo sobre lo asignado. El endpoint
     * cambiar_estado.php no validaba esto en el servidor — únicamente
     * ver_denuncia.php lo hacía al calcular $puede_cambiar_estado, así que
     * un POST directo lo salteaba.
     */
    public function cambiarEstado(Usuario $usuario, Denuncia $denuncia): bool
    {
        if ($usuario->esSuperadmin()) {
            return true;
        }

        if (! $this->mismaEmpresa($usuario, $denuncia)) {
            return false;
        }

        if ($usuario->nombreRol()?->operaSinAsignacion()) {
            return true;
        }

        return $this->estaAsignado($usuario, $denuncia);
    }

    public function asignar(Usuario $usuario, Denuncia $denuncia): bool
    {
        if ($usuario->esSuperadmin()) {
            return true;
        }

        return $usuario->tieneRol(RolUsuario::AdminPrincipal)
            && $this->mismaEmpresa($usuario, $denuncia);
    }

    /**
     * Ver las notas internas del caso.
     * Solo superadmin e investigador externo (ver_denuncia.php).
     */
    public function verNotas(Usuario $usuario, Denuncia $denuncia): bool
    {
        return $usuario->nombreRol()?->accedeANotasInternas()
            && $this->view($usuario, $denuncia);
    }

    public function crearNota(Usuario $usuario, Denuncia $denuncia): bool
    {
        return $this->verNotas($usuario, $denuncia);
    }

    /**
     * Ver el chat con el denunciante.
     * Solo el investigador externo asignado.
     */
    public function verChat(Usuario $usuario, Denuncia $denuncia): bool
    {
        return $usuario->nombreRol()?->participaDelChat()
            && $this->estaAsignado($usuario, $denuncia);
    }

    /**
     * Escribir en el chat.
     *
     * Además de ver: la denuncia no puede ser anónima. El denunciante
     * anónimo no dejó vía de contacto, así que no hay con quién chatear.
     */
    public function chatear(Usuario $usuario, Denuncia $denuncia): bool
    {
        return ! $denuncia->is_anonymous
            && $this->verChat($usuario, $denuncia);
    }

    /** Datos identificatorios del denunciante, cuando la denuncia no es anónima. */
    public function verDenunciante(Usuario $usuario, Denuncia $denuncia): bool
    {
        if ($denuncia->is_anonymous || $denuncia->estaPurgada()) {
            return false;
        }

        return $this->view($usuario, $denuncia);
    }

    public function verArchivos(Usuario $usuario, Denuncia $denuncia): bool
    {
        return $this->view($usuario, $denuncia);
    }

    public function exportar(Usuario $usuario, Denuncia $denuncia): bool
    {
        return $this->view($usuario, $denuncia);
    }

    // ── Internos ────────────────────────────────────────────────

    private function mismaEmpresa(Usuario $usuario, Denuncia $denuncia): bool
    {
        return $usuario->company_id !== null
            && $usuario->company_id === $denuncia->company_id;
    }

    /**
     * Asignación vigente.
     *
     * Consulta SOLO complaint_assignments, que es la fuente de verdad.
     *
     * El sistema actual consultaba las dos tablas con un UNION, usando
     * complaints.assigned_to_user_id como fallback "por si hay
     * desincronización" — es decir, convivía con el problema en lugar de
     * resolverlo (H-010). Acá la sincronización se garantiza al escribir:
     * el servicio de asignación actualiza ambas dentro de la misma
     * transacción, así que no hace falta leer las dos.
     */
    private function estaAsignado(Usuario $usuario, Denuncia $denuncia): bool
    {
        return $denuncia->asignaciones()
            ->whereNull('ended_at')
            ->where('assigned_to_user_id', $usuario->id)
            ->exists();
    }
}
