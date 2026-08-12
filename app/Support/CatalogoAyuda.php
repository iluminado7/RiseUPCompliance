<?php

namespace App\Support;

/**
 * Contenido de la pantalla de ayuda, portado de ayuda.php.
 *
 * Es contenido declarado y no una tabla: la ayuda cambia cuando cambia el
 * codigo, asi que viaja en el mismo commit.
 *
 * NO se porto el formulario de ticket del original. Un ticket con el
 * detalle de un caso cruza datos de la empresa hacia el proveedor, y el
 * envio dependia de Resend, que sigue sin dominio verificado.
 *
 * -- RESPUESTAS CORREGIDAS RESPECTO DEL ORIGINAL --
 *
 * Cuatro respuestas del FAQ describian comportamiento que el port ya no
 * tiene. Estan marcadas abajo con el motivo. Si alguien compara contra
 * ayuda.php y las ve distintas, la diferencia es deliberada.
 */
final class CatalogoAyuda
{
    public const SUPERADMIN = 'superadmin';
    public const ADMIN_PRINCIPAL = 'admin_principal';
    public const GESTOR = 'gestor';
    public const INVESTIGADOR = 'investigador_externo';

    /**
     * Casilla de soporte. Viene de ayuda.php, donde el TODO original decia
     * que habia que reemplazarla por la casilla real. Sigue pendiente.
     */
    public const EMAIL_SOPORTE = 'soporte@canaletico.com';

    public static function etiquetaRol(string $rol): string
    {
        return [
            self::SUPERADMIN => 'Superadmin',
            self::ADMIN_PRINCIPAL => 'Administrador principal',
            self::GESTOR => 'Gestor',
            self::INVESTIGADOR => 'Investigador externo',
        ][$rol] ?? ucfirst(str_replace('_', ' ', $rol));
    }

    /**
     * Guias que cubre el manual de cada rol.
     *
     * Son informativas: anticipan el contenido del manual, que todavia
     * esta en preparacion. No enlazan a ningun lado.
     */
    public static function guias(string $rol): array
    {
        $porRol = [
            self::SUPERADMIN => [
                ['icono' => '📋', 'titulo' => 'Panel de gestión — Visión general',
                 'desc' => 'Descripción general del panel, roles y permisos.'],
                ['icono' => '📊', 'titulo' => 'Denuncias globales y detalle',
                 'desc' => 'Cómo ver, filtrar y gestionar todas las denuncias del sistema.'],
                ['icono' => '🔧', 'titulo' => 'Catálogo del sistema',
                 'desc' => 'Gestión de categorías, relaciones, áreas y cargos.'],
                ['icono' => '👥', 'titulo' => 'Configuración del canal',
                 'desc' => 'Configuración por empresa: categorías, cuestionarios, áreas, cargos, legal.'],
                ['icono' => '📂', 'titulo' => 'Administración de usuarios y empresas',
                 'desc' => 'Alta, edición y desactivación de usuarios, empresas y sucursales.'],
                ['icono' => '📈', 'titulo' => 'Reportes',
                 'desc' => 'Cómo interpretar los reportes y usar los filtros.'],
            ],
            self::ADMIN_PRINCIPAL => [
                ['icono' => '📋', 'titulo' => 'Panel de gestión — Visión general',
                 'desc' => 'Descripción general del panel, roles y permisos.'],
                ['icono' => '📊', 'titulo' => 'Bandeja de denuncias',
                 'desc' => 'Cómo ver, filtrar y asignar las denuncias de tu empresa.'],
                ['icono' => '📈', 'titulo' => 'Reportes',
                 'desc' => 'Cómo interpretar los reportes y usar los filtros.'],
                ['icono' => '📂', 'titulo' => 'Administración — Sucursales y usuarios',
                 'desc' => 'Gestión de usuarios y sucursales de tu empresa.'],
            ],
            self::GESTOR => [
                ['icono' => '📋', 'titulo' => 'Panel de gestión — Visión general',
                 'desc' => 'Descripción general del panel, roles y permisos.'],
                ['icono' => '📊', 'titulo' => 'Mis denuncias asignadas',
                 'desc' => 'Cómo acceder y trabajar con los casos que te fueron asignados.'],
            ],
            self::INVESTIGADOR => [
                ['icono' => '📋', 'titulo' => 'Panel de gestión — Visión general',
                 'desc' => 'Descripción general del panel, roles y permisos.'],
                ['icono' => '📊', 'titulo' => 'Mis denuncias asignadas',
                 'desc' => 'Cómo acceder y trabajar con los casos asignados.'],
                ['icono' => '💬', 'titulo' => 'Chat con el denunciante',
                 'desc' => 'Cómo usar el canal de comunicación con el denunciante.'],
                ['icono' => '🗒️', 'titulo' => 'Notas internas',
                 'desc' => 'Cómo crear, editar y gestionar notas internas del caso.'],
            ],
        ];

        return $porRol[$rol] ?? [];
    }

    /** Nombre del PDF de manual que le corresponde al rol. */
    public static function archivoManual(string $rol): ?string
    {
        return [
            self::SUPERADMIN => 'manual_superadmin.pdf',
            self::ADMIN_PRINCIPAL => 'manual_admin_principal.pdf',
            self::GESTOR => 'manual_gestor.pdf',
            self::INVESTIGADOR => 'manual_investigador_externo.pdf',
        ][$rol] ?? null;
    }

    /**
     * Los manuales todavia no existen. Se chequea la existencia real del
     * archivo en lugar de una bandera: el dia que se suban a public/docs/
     * el boton se habilita solo, sin tocar codigo.
     */
    public static function manualDisponible(string $rol): bool
    {
        $archivo = self::archivoManual($rol);

        return $archivo !== null && is_file(public_path('docs/' . $archivo));
    }

    public static function preguntasFrecuentes(): array
    {
        return [
            [
                'id' => 'faq-password',
                'pregunta' => '¿Cómo recupero mi contraseña?',
                // CORREGIDA: la original prometia un correo sin mas. El envio
                // de mails sigue pendiente (Resend sin dominio verificado),
                // asi que hace falta decir que hacer si no llega.
                'respuesta' => 'En la pantalla de ingreso hay un enlace para restablecer '
                    . 'la contraseña. Ingresás tu email y el sistema te envía las '
                    . 'instrucciones. Si el correo no llega, revisá la carpeta de spam y, '
                    . 'si aun así no aparece, pedile a un administrador de tu empresa que '
                    . 'te asigne una contraseña nueva: vas a tener que cambiarla en el '
                    . 'primer ingreso.',
            ],
            [
                'id' => 'faq-asignar',
                'pregunta' => '¿Cómo asigno una denuncia a un analista?',
                // CORREGIDA: la original describia la pantalla vieja ("columna
                // derecha de la pestana Informacion") y prometia una
                // notificacion automatica por mail que hoy no se envia.
                'respuesta' => 'Abrí la denuncia desde el listado. En el panel de acciones '
                    . 'vas a encontrar la opción de asignar: elegís a la persona y '
                    . 'confirmás. La asignación queda registrada en la traza con quién la '
                    . 'hizo y cuándo, y el caso pasa a aparecer en la bandeja de esa '
                    . 'persona.',
            ],
            [
                'id' => 'faq-estado',
                'pregunta' => '¿Qué pasa si cambio el estado de una denuncia por error?',
                // CORREGIDA: la original decia que se podia volver a cualquier
                // estado anterior. El port tiene maquina de estados
                // (transicionesValidas) y el paso a Vista es irreversible.
                'respuesta' => 'El sistema solo ofrece las transiciones válidas desde el '
                    . 'estado actual, así que no todo cambio se puede deshacer. Una '
                    . 'denuncia resuelta o cerrada se puede reabrir a En progreso, pero el '
                    . 'paso automático a Vista —que ocurre cuando alguien abre una '
                    . 'denuncia nueva— no se revierte. Todos los cambios quedan '
                    . 'registrados con autor y fecha.',
            ],
            [
                'id' => 'faq-notas',
                'pregunta' => '¿Las notas internas las puede ver el denunciante?',
                // CORREGIDA: la original decia "privadas para el equipo del
                // panel", lo que sugiere que las ve todo el equipo. El
                // admin_principal tampoco las ve, y es a proposito.
                'respuesta' => 'No, el denunciante nunca las ve. Tampoco las ve todo el '
                    . 'equipo del panel: solo el superadmin y el investigador externo '
                    . 'asignado. El administrador principal de la empresa no accede a las '
                    . 'notas internas, y es así a propósito. El denunciante solo ve el '
                    . 'estado de su denuncia y los mensajes del chat.',
            ],
            [
                'id' => 'faq-chat',
                'pregunta' => '¿Por qué no aparece el chat en todas las denuncias?',
                // Sin cambios respecto del original: describe bien el port.
                'respuesta' => 'El chat solo está disponible cuando se cumplen tres '
                    . 'condiciones al mismo tiempo: el usuario es un investigador externo, '
                    . 'la denuncia está asignada a ese investigador, y la denuncia no es '
                    . 'anónima. Si alguna no se cumple, el chat no aparece. En una '
                    . 'denuncia anónima no hay chat porque el denunciante no dejó ninguna '
                    . 'vía de contacto.',
            ],
        ];
    }
}
