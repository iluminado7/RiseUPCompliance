<?php

namespace App\Support;

/**
 * Contenido de la pantalla de ayuda.
 *
 * Es contenido declarado, no una tabla: la ayuda cambia cuando cambia el
 * codigo, asi que vive con el codigo y viaja en el mismo commit. No hay
 * pantalla de edicion ni formulario de contacto — nada de lo que se
 * escribe en el panel sale de la empresa por aca.
 *
 * -- COMO AGREGAR CONTENIDO --
 *
 * Cada articulo declara 'roles'. Un rol que no este en la lista no ve el
 * articulo, ni siquiera en el buscador. El superadmin ve todo.
 *
 * Estructura de un articulo:
 *   'id'       ancla para el enlace directo (minusculas, con guiones)
 *   'titulo'   una linea
 *   'roles'    array de roles que lo ven; ROLES_TODOS para todos
 *   'parrafos' array de strings; cada uno es un <p>
 *   'lista'    array opcional de puntos
 *   'aviso'    string opcional; se muestra destacado
 *   'tipo'     'info' (default) o 'ojo' para los comportamientos que
 *              parecen errores y no lo son
 */
final class CatalogoAyuda
{
    public const SUPERADMIN = 'superadmin';
    public const ADMIN_PRINCIPAL = 'admin_principal';
    public const GESTOR = 'gestor';
    public const INVESTIGADOR = 'investigador_externo';

    public const ROLES_TODOS = [
        self::SUPERADMIN,
        self::ADMIN_PRINCIPAL,
        self::GESTOR,
        self::INVESTIGADOR,
    ];

    public const ROLES_GESTION = [
        self::SUPERADMIN,
        self::ADMIN_PRINCIPAL,
    ];

    /**
     * Contacto de soporte. REVISAR antes de publicar: tiene que ser una
     * casilla que alguien lea.
     */
    public const EMAIL_SOPORTE = 'soporte@goharvey.com';

    public static function secciones(): array
    {
        return [
            self::seccionPrimerosPasos(),
            self::seccionDenuncias(),
            self::seccionCanalPublico(),
            self::seccionAdministracion(),
            self::seccionCatalogo(),
            self::seccionOnboarding(),
            self::seccionSeguridad(),
            self::seccionNoEsUnError(),
            self::seccionSoporte(),
        ];
    }

    /**
     * Secciones y articulos visibles para un rol, ya filtrados.
     * Una seccion sin articulos visibles no se devuelve.
     */
    public static function paraRol(string $rol): array
    {
        $resultado = [];

        foreach (self::secciones() as $seccion) {
            $articulos = array_values(array_filter(
                $seccion['articulos'],
                fn (array $a) => in_array($rol, $a['roles'], true)
            ));

            if ($articulos) {
                $seccion['articulos'] = $articulos;
                $resultado[] = $seccion;
            }
        }

        return $resultado;
    }

    // -- Secciones ---------------------------------------------------

    private static function seccionPrimerosPasos(): array
    {
        return [
            'id' => 'primeros-pasos',
            'titulo' => 'Primeros pasos',
            'articulos' => [
                [
                    'id' => 'ingreso',
                    'titulo' => 'Ingreso y bloqueo de cuenta',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Se ingresa con el email y la contraseña que te asignaron. '
                        . 'Después de varios intentos fallidos seguidos la cuenta '
                        . 'queda bloqueada por un rato: es una defensa contra quien '
                        . 'prueba contraseñas, no un error.',
                        'Si el bloqueo te agarró a vos, esperá a que venza o pedile '
                        . 'a un administrador que lo levante. Cambiar la contraseña '
                        . 'desde "olvidé mi contraseña" también sirve.',
                    ],
                ],
                [
                    'id' => 'primer-ingreso',
                    'titulo' => 'Cambio de contraseña en el primer ingreso',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'La primera vez que entrás, el sistema te lleva a cambiar la '
                        . 'contraseña antes que a ninguna otra pantalla. No se puede '
                        . 'saltear: la contraseña con la que te dieron de alta la '
                        . 'conoce quien te creó el usuario.',
                    ],
                ],
                [
                    'id' => 'dos-factores',
                    'titulo' => 'Segundo factor (2FA)',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Se activa desde Perfil, pestaña de seguridad. Necesitás una '
                        . 'app de códigos en el teléfono (Google Authenticator, Authy '
                        . 'o similar): escaneás el QR una vez y desde ahí el ingreso '
                        . 'pide un código de seis dígitos que cambia cada treinta '
                        . 'segundos.',
                        'Guardá los códigos de recuperación en un lugar seguro. Si '
                        . 'perdés el teléfono y no los tenés, un administrador tiene '
                        . 'que desactivarte el 2FA para que puedas volver a entrar.',
                    ],
                    'aviso' => 'Si trabajás con denuncias, activalo. El panel muestra '
                        . 'identidades de denunciantes no anónimos.',
                ],
                [
                    'id' => 'perfil',
                    'titulo' => 'Tu perfil',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Desde Perfil cambiás tus datos, tu contraseña y tus '
                        . 'preferencias, y administrás el segundo factor. Lo que no '
                        . 'podés cambiar es tu propio rol ni la empresa a la que '
                        . 'pertenecés.',
                    ],
                ],
            ],
        ];
    }

    private static function seccionDenuncias(): array
    {
        return [
            'id' => 'denuncias',
            'titulo' => 'Trabajar una denuncia',
            'articulos' => [
                [
                    'id' => 'bandeja',
                    'titulo' => 'La bandeja',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'El listado muestra las denuncias de tu empresa. No hace '
                        . 'falta filtrar por empresa: el sistema ya lo hace por vos y '
                        . 'no hay forma de ver las de otra.',
                        'Cada denuncia tiene un código interno que la identifica en '
                        . 'el panel. Ese código no es el mismo que el código de '
                        . 'seguimiento que recibió el denunciante.',
                    ],
                ],
                [
                    'id' => 'estados',
                    'titulo' => 'Estados y cómo avanzan',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Una denuncia entra como Nueva. Cuando alguien la abre por '
                        . 'primera vez pasa sola a Vista, y ese paso no se deshace. '
                        . 'De ahí avanza a En progreso y termina en Resuelta o '
                        . 'Cerrada.',
                        'El panel de acciones solo ofrece las transiciones válidas '
                        . 'desde el estado actual. Si un estado no aparece en la '
                        . 'lista, es que no se puede llegar a él desde donde está la '
                        . 'denuncia.',
                    ],
                    'lista' => [
                        'Una denuncia cerrada o resuelta se puede reabrir a En progreso.',
                        'El paso automático a Vista queda registrado en la traza.',
                    ],
                ],
                [
                    'id' => 'asignacion',
                    'titulo' => 'Asignación y prioridad',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Asignar una denuncia le da a esa persona la responsabilidad '
                        . 'del caso y la hace aparecer en su bandeja. La prioridad es '
                        . 'orientativa: no cambia permisos ni dispara nada '
                        . 'automático.',
                    ],
                ],
                [
                    'id' => 'notas-internas',
                    'titulo' => 'Notas internas',
                    'roles' => [self::SUPERADMIN, self::INVESTIGADOR],
                    'parrafos' => [
                        'Las notas internas son el espacio de trabajo del caso y no '
                        . 'las ve el denunciante. Tampoco las ve todo el equipo: solo '
                        . 'el superadmin y el investigador externo.',
                    ],
                    'aviso' => 'El administrador principal de la empresa NO ve las '
                        . 'notas internas. Es a propósito.',
                ],
                [
                    'id' => 'chat',
                    'titulo' => 'Conversación con el denunciante',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Solo hay conversación cuando la denuncia no es anónima y '
                        . 'tiene un investigador externo asignado. Si la denuncia es '
                        . 'anónima no hay chat, porque el denunciante no dejó ninguna '
                        . 'vía de contacto.',
                        'El denunciante lee y responde desde la pantalla pública de '
                        . 'seguimiento, con su código.',
                    ],
                ],
                [
                    'id' => 'adjuntos',
                    'titulo' => 'Archivos adjuntos',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Las imágenes se pueden abrir con normalidad: el sistema las '
                        . 'reprocesa al recibirlas, lo que borra los metadatos y '
                        . 'destruye cualquier contenido escondido adentro del '
                        . 'archivo.',
                        'Los PDF y los documentos de Word llegan bloqueados y todavía '
                        . 'no se pueden abrir desde el panel. Es una limitación '
                        . 'conocida, no una falla de permisos.',
                    ],
                    'tipo' => 'ojo',
                ],
                [
                    'id' => 'anonimato',
                    'titulo' => 'Qué se sabe y qué no de quien denuncia',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'El denunciante nunca es usuario del sistema: no tiene cuenta '
                        . 'ni login. Si eligió el modo anónimo, el sistema no guarda '
                        . 'su identidad en ningún lado y no hay forma de recuperarla '
                        . 'desde el panel.',
                        'Los datos personales de las denuncias no anónimas se guardan '
                        . 'cifrados.',
                    ],
                    'aviso' => 'Pedirle a un denunciante anónimo que se identifique, '
                        . 'o intentar deducir quién es por el contenido, contradice '
                        . 'la finalidad del canal.',
                ],
            ],
        ];
    }

    private static function seccionCanalPublico(): array
    {
        return [
            'id' => 'canal-publico',
            'titulo' => 'El canal público',
            'articulos' => [
                [
                    'id' => 'direccion-canal',
                    'titulo' => 'La dirección del canal',
                    'roles' => self::ROLES_GESTION,
                    'parrafos' => [
                        'Cada empresa tiene su propia dirección pública, formada por '
                        . 'el nombre corto que se definió al darla de alta. Es la que '
                        . 'se reparte entre la gente de la empresa: carteles, mails, '
                        . 'intranet.',
                    ],
                    'aviso' => 'Ese nombre corto no se puede cambiar después. '
                        . 'Cambiarlo dejaría muertos todos los enlaces ya repartidos.',
                    'tipo' => 'ojo',
                ],
                [
                    'id' => 'formulario',
                    'titulo' => 'El formulario de denuncia',
                    'roles' => self::ROLES_GESTION,
                    'parrafos' => [
                        'Son siete pasos. El denunciante puede volver atrás y '
                        . 'corregir antes de enviar. Lo que carga queda guardado '
                        . 'mientras completa, y se descarta solo si abandona.',
                        'Las categorías y las preguntas que ve son las que la empresa '
                        . 'tenga habilitadas en Configuración del canal.',
                    ],
                ],
                [
                    'id' => 'codigo-seguimiento',
                    'titulo' => 'El código de seguimiento',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Al enviar la denuncia, el denunciante recibe un código con '
                        . 'el que después consulta el estado y responde mensajes. El '
                        . 'sistema guarda solo una huella de ese código, no el código '
                        . 'en sí.',
                    ],
                    'aviso' => 'Si el denunciante lo pierde, no hay forma de '
                        . 'recuperarlo ni de volver a mostrarlo. Es la contrapartida '
                        . 'de que nadie más pueda usarlo.',
                    'tipo' => 'ojo',
                ],
            ],
        ];
    }

    private static function seccionAdministracion(): array
    {
        return [
            'id' => 'administracion',
            'titulo' => 'Administración',
            'articulos' => [
                [
                    'id' => 'roles',
                    'titulo' => 'Qué puede hacer cada rol',
                    'roles' => self::ROLES_GESTION,
                    'parrafos' => [
                        'El sistema tiene cuatro roles y cada uno ve una parte '
                        . 'distinta del panel.',
                    ],
                    'lista' => [
                        'Superadmin — plataforma completa, todas las empresas.',
                        'Administrador principal — su empresa: usuarios, sucursales, '
                        . 'denuncias y reportes. No ve notas internas.',
                        'Gestor — la bandeja de denuncias de su empresa.',
                        'Investigador externo — los casos que tiene asignados, con '
                        . 'notas internas y conversación.',
                    ],
                ],
                [
                    'id' => 'usuarios',
                    'titulo' => 'Crear y editar usuarios',
                    'roles' => self::ROLES_GESTION,
                    'parrafos' => [
                        'Un usuario nuevo entra con la contraseña que le cargues y el '
                        . 'sistema lo obliga a cambiarla en el primer ingreso.',
                        'No podés asignar un rol con más alcance que el tuyo, ni '
                        . 'cambiarte el rol a vos mismo. Si el sistema te lo rechaza, '
                        . 'es eso.',
                    ],
                ],
                [
                    'id' => 'sucursales',
                    'titulo' => 'Sucursales',
                    'roles' => self::ROLES_GESTION,
                    'parrafos' => [
                        'Cada empresa tiene una sede central y las sucursales que '
                        . 'necesite. El denunciante elige la suya en el formulario, '
                        . 'así que la lista conviene mantenerla al día.',
                        'Una sucursal no se borra: se desactiva. Borrarla dejaría sin '
                        . 'referencia a las denuncias ya hechas desde ahí.',
                    ],
                ],
                [
                    'id' => 'estado-empresa',
                    'titulo' => 'Suspender o desactivar una empresa',
                    'roles' => [self::SUPERADMIN],
                    'parrafos' => [
                        'Cambiar el estado de una empresa a inactiva deja a todos sus '
                        . 'usuarios afuera del panel en el siguiente request y cierra '
                        . 'su canal público. No borra nada, pero el efecto es '
                        . 'inmediato y visible para la empresa.',
                    ],
                    'tipo' => 'ojo',
                ],
            ],
        ];
    }

    private static function seccionCatalogo(): array
    {
        return [
            'id' => 'catalogo',
            'titulo' => 'Catálogo y configuración del canal',
            'articulos' => [
                [
                    'id' => 'catalogo-global',
                    'titulo' => 'El catálogo del sistema',
                    'roles' => [self::SUPERADMIN],
                    'parrafos' => [
                        'El catálogo tiene las categorías, áreas y preguntas que '
                        . 'después cada empresa habilita para su canal. Es la '
                        . 'plantilla común de la plataforma.',
                        'El texto de una pregunta no se edita en el lugar: al '
                        . 'cambiarlo se crea una versión nueva. Así una denuncia vieja '
                        . 'sigue mostrando la pregunta tal como se la hicieron.',
                    ],
                    'aviso' => 'El código de una categoría es permanente. Cambiarlo '
                        . 'rompería la trazabilidad de las denuncias ya vinculadas.',
                ],
                [
                    'id' => 'config-canal',
                    'titulo' => 'Configuración del canal de una empresa',
                    'roles' => [self::SUPERADMIN],
                    'parrafos' => [
                        'Acá se elige qué categorías y áreas ve el denunciante de esa '
                        . 'empresa, se arma su cuestionario y se administran los '
                        . 'textos legales.',
                        'Los cambios del catálogo se propagan a los cuestionarios de '
                        . 'las empresas que tengan esa pregunta habilitada.',
                    ],
                ],
                [
                    'id' => 'config-global',
                    'titulo' => 'Configuración global',
                    'roles' => [self::SUPERADMIN],
                    'parrafos' => [
                        'Define los valores con los que se crean las empresas nuevas: '
                        . 'zona horaria, idioma y plazos de retención.',
                    ],
                    'aviso' => 'No toca a las empresas que ya existen. Cambiar un '
                        . 'valor acá no reescribe la configuración de nadie.',
                    'tipo' => 'ojo',
                ],
            ],
        ];
    }

    private static function seccionOnboarding(): array
    {
        return [
            'id' => 'onboarding',
            'titulo' => 'Alta de empresas',
            'articulos' => [
                [
                    'id' => 'links',
                    'titulo' => 'Links de invitación',
                    'roles' => [self::SUPERADMIN],
                    'parrafos' => [
                        'Se genera un link y se le envía a la empresa para que cargue '
                        . 'sus datos. Vence a las 48 horas.',
                        'El link se muestra una sola vez, al generarlo: copialo en ese '
                        . 'momento. Después no vuelve a mostrarse.',
                    ],
                ],
                [
                    'id' => 'estados-link',
                    'titulo' => 'Qué significa cada situación',
                    'roles' => [self::SUPERADMIN],
                    'parrafos' => [
                        'La columna Situación cruza el estado del link con lo que la '
                        . 'empresa haya cargado.',
                    ],
                    'lista' => [
                        'Enviado — generado, la empresa todavía no entró.',
                        'Completando — está cargando datos.',
                        'Por revisar — envió el formulario y espera tu confirmación.',
                        'Alta confirmada — la empresa ya existe en el sistema.',
                        'Vencido / Revocado — el link no sirve más.',
                    ],
                ],
                [
                    'id' => 'confirmar',
                    'titulo' => 'Revisar y confirmar un alta',
                    'roles' => [self::SUPERADMIN],
                    'parrafos' => [
                        'Al revisar podés corregir los datos antes de confirmar. Es '
                        . 'la última oportunidad de cambiar la dirección del canal: '
                        . 'después queda fija.',
                        'Al confirmar se crean la empresa, sus sucursales y sus '
                        . 'usuarios, y los datos provisorios se borran.',
                    ],
                ],
                [
                    'id' => 'eliminar-link',
                    'titulo' => 'Eliminar links',
                    'roles' => [self::SUPERADMIN],
                    'parrafos' => [
                        'Se pueden eliminar los links vencidos, los revocados y los '
                        . 'que estén por revisar cuando el envío fue una prueba o un '
                        . 'error. El borrado se lleva también los datos que la empresa '
                        . 'haya cargado.',
                        'Un alta ya confirmada no se elimina desde acá: esa fila es la '
                        . 'constancia de por qué esa empresa existe.',
                    ],
                    'tipo' => 'ojo',
                ],
            ],
        ];
    }

    private static function seccionSeguridad(): array
    {
        return [
            'id' => 'seguridad',
            'titulo' => 'Traza y conservación de datos',
            'articulos' => [
                [
                    'id' => 'traza',
                    'titulo' => 'La traza de auditoría',
                    'roles' => self::ROLES_GESTION,
                    'parrafos' => [
                        'Cada acción sobre una denuncia queda registrada: quién, qué, '
                        . 'cuándo. Los registros no se editan ni se borran, ni siquiera '
                        . 'desde la base de datos, y están encadenados entre sí de '
                        . 'forma que alterar uno se detecta.',
                        'Es lo que permite demostrar ante un tercero que un caso se '
                        . 'trató como se dice que se trató.',
                    ],
                ],
                [
                    'id' => 'retencion',
                    'titulo' => 'Cuánto se conservan los datos',
                    'roles' => self::ROLES_GESTION,
                    'parrafos' => [
                        'Cada empresa tiene sus propios plazos para denuncias, '
                        . 'archivos y registros de auditoría. Se definen al darla de '
                        . 'alta.',
                        'Cuando una denuncia se purga, sus datos personales quedan '
                        . 'irrecuperables pero la traza no muestra huecos: se puede '
                        . 'seguir demostrando que el caso existió y cómo se trató.',
                    ],
                ],
            ],
        ];
    }

    private static function seccionNoEsUnError(): array
    {
        return [
            'id' => 'no-es-un-error',
            'titulo' => 'Parece un error y no lo es',
            'articulos' => [
                [
                    'id' => 'ne-notas',
                    'titulo' => 'No veo las notas internas de mi empresa',
                    'roles' => [self::ADMIN_PRINCIPAL, self::SUPERADMIN],
                    'parrafos' => [
                        'El administrador principal no accede a las notas internas, '
                        . 'aunque sean de su propia empresa. Las ven el superadmin y '
                        . 'el investigador externo asignado.',
                    ],
                    'tipo' => 'ojo',
                ],
                [
                    'id' => 'ne-investigador',
                    'titulo' => 'El investigador externo cambia estados',
                    'roles' => self::ROLES_GESTION,
                    'parrafos' => [
                        'Aunque se lo describa como un rol de consulta, el '
                        . 'investigador externo puede mover el estado de los casos que '
                        . 'tiene asignados.',
                    ],
                    'tipo' => 'ojo',
                ],
                [
                    'id' => 'ne-404',
                    'titulo' => 'Me da "no encontrado" en algo que existe',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Si pedís una denuncia que no es de tu empresa, el sistema '
                        . 'responde que no existe en lugar de decir que no tenés '
                        . 'permiso. Decir "existe pero no podés verla" ya sería '
                        . 'información sobre otra empresa.',
                    ],
                    'tipo' => 'ojo',
                ],
                [
                    'id' => 'ne-adjuntos',
                    'titulo' => 'No puedo abrir un PDF adjunto',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Los PDF y documentos de Word llegan bloqueados a la espera '
                        . 'del análisis de archivos. Las imágenes sí se abren.',
                    ],
                    'tipo' => 'ojo',
                ],
                [
                    'id' => 'ne-vista',
                    'titulo' => 'La denuncia pasó a Vista sin que yo hiciera nada',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Abrir una denuncia nueva la marca como Vista automáticamente '
                        . 'y queda registrado quién la abrió. No se vuelve atrás.',
                    ],
                    'tipo' => 'ojo',
                ],
            ],
        ];
    }

    private static function seccionSoporte(): array
    {
        return [
            'id' => 'soporte',
            'titulo' => 'Soporte',
            'articulos' => [
                [
                    'id' => 'contacto',
                    'titulo' => 'Cómo pedir ayuda',
                    'roles' => self::ROLES_TODOS,
                    'parrafos' => [
                        'Escribí a ' . self::EMAIL_SOPORTE . '. Para que podamos '
                        . 'ayudarte rápido, contá qué pantalla estabas usando, qué '
                        . 'hiciste, qué esperabas que pasara y qué pasó en su lugar.',
                    ],
                    'aviso' => 'No incluyas en el mail el contenido de una denuncia, '
                        . 'datos del denunciante ni archivos adjuntos del caso. '
                        . 'Alcanza con el código interno para identificarlo.',
                ],
                [
                    'id' => 'incidente',
                    'titulo' => 'Si sospechás de un acceso indebido',
                    'roles' => self::ROLES_GESTION,
                    'parrafos' => [
                        'Avisá de inmediato y no borres nada. La traza de auditoría '
                        . 'registra los accesos y es la que permite reconstruir qué '
                        . 'pasó; cualquier movimiento apurado complica el análisis en '
                        . 'lugar de ayudarlo.',
                    ],
                    'tipo' => 'ojo',
                ],
            ],
        ];
    }
}
