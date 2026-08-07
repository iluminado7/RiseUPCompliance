<?php

namespace App\Enums;

/**
 * Roles del panel de gestión.
 *
 * Los valores coinciden con roles.name en la base (§2.4).
 * El nivel jerárquico coincide con roles.hierarchy_level: menor número =
 * más privilegios.
 *
 * NOTA: el rol `reporter` del schema original fue eliminado. El
 * denunciante nunca es usuario de la plataforma.
 */
enum RolUsuario: string
{
    case Superadmin = 'superadmin';
    case AdminPrincipal = 'admin_principal';
    case Gestor = 'gestor';
    case InvestigadorExterno = 'investigador_externo';

    public function nivel(): int
    {
        return match ($this) {
            self::Superadmin => 1,
            self::AdminPrincipal => 2,
            self::Gestor => 3,
            self::InvestigadorExterno => 4,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::AdminPrincipal => 'Administrador principal',
            self::Gestor => 'Gestor',
            self::InvestigadorExterno => 'Investigador externo',
        };
    }

    /** El superadmin es el único que atraviesa el scope de empresa. */
    public function atraviesaScopeDeEmpresa(): bool
    {
        return $this === self::Superadmin;
    }

    /** Ve todas las denuncias de su empresa, no solo las asignadas. */
    public function veTodaLaEmpresa(): bool
    {
        return in_array($this, [self::Superadmin, self::AdminPrincipal], true);
    }

    /**
     * Puede operar sobre cualquier denuncia de su empresa, sin necesidad
     * de tenerla asignada.
     */
    public function operaSinAsignacion(): bool
    {
        return in_array($this, [self::Superadmin, self::AdminPrincipal], true);
    }

    /**
     * Accede a las notas internas.
     *
     * Portado literal del sistema actual (ver_denuncia.php):
     *   $puede_ver_notas = hasRole(['superadmin', 'investigador_externo'])
     *
     * Es contraintuitivo — el admin_principal no ve las notas de su propia
     * empresa — pero es el comportamiento existente y §2.2 dice que el
     * port no cambia comportamiento observable.
     */
    public function accedeANotasInternas(): bool
    {
        return in_array($this, [self::Superadmin, self::InvestigadorExterno], true);
    }

    /**
     * Participa del chat con el denunciante.
     *
     * Solo el investigador externo, y solo sobre denuncias asignadas y NO
     * anónimas. También es quien lo inicia.
     */
    public function participaDelChat(): bool
    {
        return $this === self::InvestigadorExterno;
    }
}
