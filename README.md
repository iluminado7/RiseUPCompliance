# Rise UP Compliance

Canal de denuncias multi-empresa. Cada empresa cliente tiene su propio
canal público donde su gente puede reportar irregularidades —de forma
anónima si quiere— y un panel donde su equipo investiga los casos.

Desarrollado por **Go Harvey SAS**.

---

## Lo primero que hay que entender

Este sistema es un **port en curso** de una aplicación PHP puro a Laravel
12. No es un desarrollo desde cero: hay un sistema anterior funcionando,
una auditoría de seguridad hecha sobre él, y decisiones que arrastran esa
historia.

Si vas a tocar algo, importan tres cosas:

1. **Los comentarios del código explican por qué, no qué.** Cuando algo
   parece raro, casi siempre hay una nota explicando qué problema
   resolvía. Léela antes de "arreglarlo".

2. **Hay correcciones deliberadas al sistema anterior.** Están marcadas
   con el número de hallazgo (H-003, H-009, etc.) o con una explicación
   del bug que cerraban. No son reescrituras gratuitas.

3. **Hay comportamientos contraintuitivos que se portaron a propósito.**
   El más notorio: el `admin_principal` NO puede ver las notas internas de
   su propia empresa. Es así en el sistema original y está fijado por
   tests para que nadie lo "corrija" sin decidirlo.

---

## Las dos superficies

El sistema tiene dos caras que **nunca comparten sesión ni middleware**.
Esa separación es una restricción dura, no una preferencia de
organización: mezclarlas destruiría el anonimato, que es la propuesta de
valor del producto.

### Canal público (`routes/web.php`)

Sin autenticación. Entra gente que no tiene ni va a tener usuario en el
sistema.

```
/                      Portada de GoHarv
/denunciar             Buscador de empresa
/{slug}                Canal de denuncias de esa empresa (7 pasos)
/seguimiento           Consulta del estado con código
/onboarding/{token}    Alta de empresa por invitación
```

**El denunciante nunca es un usuario de la plataforma.** No hay login, no
hay cuenta, no hay `user_id` asociado. El acceso a su propia denuncia es
por un código de seguimiento del que la base guarda solo el hash.

### Panel de gestión (`routes/admin.php`)

Con autenticación, 2FA opcional y aislamiento por empresa.

```
/admin/login           Ingreso
/admin                 Dashboard
/admin/denuncias       Bandeja y detalle
/admin/reportes        Métricas
/admin/administracion  Empresas, sucursales, usuarios
/admin/catalogo        Catálogo global del sistema
/admin/configuracion   Configuración del canal por empresa
/admin/facturacion     Facturas
/admin/logs            Traza de auditoría
/admin/perfil          Perfil propio
```

---

## Convenciones que hay que respetar

### Modelos en español, tablas en inglés

```php
class Denuncia extends Model
{
    protected $table = 'complaints';
}
```

El código que se escribe todos los días está en español; la base quedó
como estaba. Cada modelo declara su `$table` y cada relación su clave
foránea explícitamente.

| Modelo | Tabla |
|---|---|
| `Denuncia` | `complaints` |
| `Denunciante` | `complaint_reporters` |
| `Empresa` | `companies` |
| `Sucursal` | `branches` |
| `Usuario` | `users` |
| `RegistroAuditoria` | `audit_logs` |

### Aislamiento entre empresas

Lo resuelve `TenantScope`, aplicado por el trait `PerteneceAEmpresa`. Los
controladores del panel **no llevan `where company_id`**: el scope lo
agrega solo.

```php
// Esto ya devuelve solo las denuncias de la empresa del usuario:
Denuncia::where('status', 'new')->get();
```

**El `company_id` sale SIEMPRE del usuario autenticado, nunca del
request.** Aceptarlo del request es permitir cambiar de tenant a voluntad.

Cinco modelos NO usan el scope, cada uno por un motivo distinto
documentado en su clase:

- `Usuario` y `Empresa` — el scope rompería el login del superadmin, que
  no tiene `company_id`
- `Pregunta` — escondería el catálogo maestro (`company_id` NULL)
- `SesionSeguimiento` — se escribe desde el canal público, sin sesión
- `RegistroAuditoria` — escondería la cadena de plataforma

En esos casos el filtrado va en la Policy.

### El superadmin atraviesa el scope

Ve todas las empresas a la vez, sin "empresa activa". Su `company_id` es
`NULL` y su vínculo es con `tenant_managers`, no con `companies`.

**Trampa del schema:** `companies.manager_id` apunta a `tenant_managers`,
NO a `users`. Ya costó una sesión de depuración.

### Errores: 404 antes que 403

Pedir un recurso de otra empresa devuelve **404**, no 403. Un 403
confirma que el recurso existe, y eso permite enumerar. Como el scope
hace que esa denuncia no exista para ese usuario, `findOrFail` devuelve
404 sin que haya que programar nada.

El 403 se usa para el otro caso: el recurso existe y es visible, pero esa
acción no está permitida.

---

## Seguridad: lo que hay que saber antes de tocar

### Cifrado y crypto-shredding

Los datos personales se cifran con AES-256-GCM. La clave de cada denuncia
se **deriva con HKDF** de la clave maestra más `complaints.encryption_salt`.

Eso es lo que permite purgar una denuncia sin borrar sus filas:

```sql
UPDATE complaints SET encryption_salt = NULL, purged_at = NOW() WHERE id = ?
```

Destruido el salt, esa denuncia —y solo esa— queda irrecuperable, y la
traza de auditoría no muestra huecos.

El sistema anterior tenía una sola clave global: destruirla habría borrado
todas las denuncias de todas las empresas.

**Variables de entorno separadas y obligatorias:**

```
APP_KEY          Cookies y sesiones (Laravel)
ENCRYPTION_KEY   Datos personales — otro ciclo de rotación
IP_HASH_KEY      HMAC de las IPs
```

`ENCRYPTION_KEY` e `IP_HASH_KEY` son hex de 64 caracteres. Si faltan, los
servicios lanzan excepción al arrancar en lugar de cifrar con clave vacía.

### Las IPs se hashean con HMAC, no con SHA-256

El sistema anterior usaba `hash('sha256', $ip)` sin clave. El espacio IPv4
completo son 4.300 millones de valores: precalcular la tabla es cuestión
de minutos.

Importa sobre todo en `tracking_sessions`, que relaciona una IP con una
denuncia concreta. Con un dump de esa tabla se reconstruía qué IP consultó
qué caso.

Usar siempre `HashIp::hash()`, nunca `hash('sha256', $ip)`.

### La cadena de auditoría

`audit_logs` es **append-only**. El modelo lanza excepción si alguien
intenta `update()` o `delete()`.

Cada registro incluye el hash del anterior, formando una cadena. Hay N+1
cadenas: una por empresa más la de plataforma (`chain_scope = 0`, para
eventos sin empresa como los login fallidos).

```
php artisan auditoria:verificar --detalle
```

Recorre todas las cadenas verificando que cada registro recalcule a su
hash y que el encadenamiento no tenga cortes ni saltos de numeración.

**Dos cosas importantes:**

- El hash cubre **todos** los campos significativos. El algoritmo original
  cubría 5 de 20, así que se podía cambiar el autor de una acción sin que
  nada lo detectara.
- El comando **necesita `ENCRYPTION_KEY`**: el detalle entra al hash como
  texto plano, porque un ciphertext AES-GCM cambia en cada escritura. No
  se puede auditar la cadena con solo un dump.

### Cuándo usar `registrar()` y cuándo `registrarSeguro()`

```php
$auditoria->registrar($datos);        // lanza excepción si falla
$auditoria->registrarSeguro($datos);  // absorbe el error
```

**Operaciones destructivas o irreversibles: siempre `registrar()`, dentro
de la transacción.** Si no se puede dejar traza, la operación no ocurre.

`registrarSeguro()` es para eventos accesorios donde perder el registro es
preferible a romper el flujo. El login lo usa: que nadie pueda entrar al
sistema es peor que perder el registro de un ingreso.

---

## Puesta en marcha

### Requisitos

- PHP 8.2 con `gd` (para el stripping de EXIF)
- MySQL 8 (se usan columnas generadas `STORED`)
- Composer

### Base de datos

Dos usuarios, por separación de privilegios:

```sql
CREATE DATABASE whistleblowing_riseupcomp
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'riseup_app'@'localhost' IDENTIFIED BY '...';
GRANT SELECT, INSERT, UPDATE, DELETE ON whistleblowing_riseupcomp.* TO 'riseup_app'@'localhost';

CREATE USER 'riseup_ddl'@'localhost' IDENTIFIED BY '...';
GRANT ALL PRIVILEGES ON whistleblowing_riseupcomp.* TO 'riseup_ddl'@'localhost';
```

`riseup_app` es el usuario de la aplicación y **no puede crear ni alterar
tablas**. Las migraciones corren con el otro:

```bash
php artisan migrate --database=mysql_ddl
```

Si alguna vez `migrate` funciona sin `--database=mysql_ddl`, la separación
de privilegios se rompió.

### Claves

```bash
php artisan key:generate
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"   # ENCRYPTION_KEY
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"   # IP_HASH_KEY
```

Dos valores distintos: comprometer uno no debe exponer el otro.

### Datos de prueba

```bash
php artisan migrate:fresh --seed --database=mysql_ddl
```

Crea dos empresas —con una sola no se puede detectar una fuga entre
tenants— y siete usuarios. Contraseña de todos: `Prueba1234!`

| Email | Rol |
|---|---|
| `super@goharvey.test` | superadmin |
| `admin@empresa-a.test` | admin_principal |
| `gestor@empresa-a.test` | gestor |
| `investigador@empresa-a.test` | investigador_externo |

(y los equivalentes de `empresa-b`)

### Tests

Necesitan un schema aparte y el usuario DDL, porque `RefreshDatabase`
corre migraciones y el usuario de aplicación no puede.

En `phpunit.xml`:

```xml
<env name="DB_DATABASE" value="whistleblowing_riseupcomp_test"/>
<env name="DB_USERNAME" value="riseup_ddl"/>
<env name="DB_PASSWORD" value="..."/>
<env name="ENCRYPTION_KEY" value="..."/>
<env name="IP_HASH_KEY" value="..."/>
```

---

## Estructura

```
app/
  Enums/            EstadoDenuncia, RolUsuario, PrioridadDenuncia...
  Models/           En español, apuntando a tablas en inglés
    Concerns/       PerteneceAEmpresa (aplica TenantScope)
    Scopes/         TenantScope
    Onboarding/     Modelos de las tablas espejo
  Policies/         Autorización por rol
  Services/         Lógica de dominio; los controladores solo orquestan
  Http/
    Controllers/
      Admin/        Panel
      Publico/      Canal, seguimiento, onboarding, portada
    Middleware/     RequiereRol, UsuarioOperativo, EmpresaOperativa...
  Console/Commands/ auditoria:verificar, canal:limpiar-borradores
```

Los servicios llevan la lógica de negocio y las transacciones. Los
controladores validan, delegan y redirigen.

---

## Estado del port

### Terminado

- Schema completo (42 tablas) con migraciones reconstruibles desde cero
- Autenticación, 2FA real (TOTP), bloqueo por cuenta, recuperación
- Panel: denuncias, reportes, logs, administración, catálogo,
  configuración del canal, onboarding, facturación, perfil
- Canal público: formulario en 7 pasos, seguimiento, portada
- Cadena de auditoría con verificación por comando

### Pendiente

| Qué | Por qué |
|---|---|
| Envío de emails | Resend sin dominio verificado |
| Generación automática de facturas | Faltan definiciones comerciales |
| Stripping de metadatos en PDF/Word | Requiere exiftool o Ghostscript (H-016) |
| Antivirus de adjuntos | Los PDF/Word quedan bloqueados mientras tanto |
| Timestamping TSA de `audit_logs` | |
| Captcha (Cloudflare Turnstile) | Componente reusable de Business Partner |
| Exportación a PDF | Requiere mPDF |
| Config. global y Ayuda | Pantallas sin portar |
| Migración a AWS | |

### Bug abierto

**El dashboard devuelve 404 al entrar después del login.** Las rutas están
bien registradas (`php artisan route:list --path=admin` las muestra
todas), y el problema no era el `redirect()->intended()`. Sin diagnosticar.

Para retomarlo hace falta: la URL exacta de la barra de direcciones cuando
aparece el 404, y las últimas líneas de `storage/logs/laravel.log`.

---

## Cosas que parecen bugs y no lo son

**El `admin_principal` no ve las notas internas de su empresa.** Solo
superadmin e investigador externo. Es el comportamiento del sistema
original, portado a propósito y fijado por tests.

**El investigador externo puede cambiar estados** pese a que el manual de
roles lo describe como "solo lectura". También es el comportamiento
original.

**El chat solo funciona en denuncias no anónimas**, y solo lo maneja el
investigador externo asignado. Un denunciante anónimo no dejó vía de
contacto.

**El slug de una empresa no se puede editar** después de creada. Es la URL
del canal público: cambiarla invalida los carteles, mails y enlaces que la
empresa repartió entre su gente.

**El código de una categoría del catálogo es permanente.** Cambiarlo
rompería la trazabilidad de las denuncias ya vinculadas.

**Los adjuntos PDF y Word llegan bloqueados** (`available_for_analyst =
false`). Solo las imágenes quedan disponibles, porque el reprocesamiento
con GD destruye cualquier payload embebido: son seguras por construcción.
Los otros formatos esperan al antivirus.

---

## Trampas conocidas

**No poner `use` dentro de un bloque `@php` en Blade.** Se compila como
cuerpo de función y PHP lo rechaza. Usar el nombre completo de la clase o
pasar el dato desde el controlador.

**`@json()` en una sola línea.** Con argumentos multilínea, el parser de
Blade pierde el cierre.

**Rutas nuevas en `web.php` van ANTES del bloque `{slug}`.** Ese patrón
matchea cualquier cosa, así que una ruta declarada después queda
inalcanzable — y falla como "empresa inexistente", no con un error claro.

**Los subrayados rojos de VS Code en `auth()->id()` son falsos
positivos.** `auth()` devuelve una interfaz que no declara `id()`. En
ejecución funciona. Instalar `barryvdh/laravel-ide-helper` los silencia.

---

## Origen y decisiones de fondo

El sistema anterior pasó por una auditoría de seguridad que encontró 32
hallazgos (H-001 a H-032). Durante el port aparecieron varios más, que el
análisis estático por módulos no podía ver porque eran problemas de
interacción entre archivos:

- El hash de auditoría cubría 5 de 20 campos
- Los login fallidos y toda la actividad del superadmin nunca se
  registraron (`if (!$company_id) return;`)
- Un `admin_principal` podía promoverse a superadmin editando su propio
  usuario
- El auto-marcado a "Vista" fallaba en silencio: la pantalla mostraba
  "Vista" sobre una base que seguía diciendo "Nuevo"
- El verificador de integridad usaba un algoritmo distinto al generador,
  así que reportaba toda la traza como alterada
- El código de seguimiento se generaba y se descartaba; se usaba el
  `internal_code`, que está en claro en la base

Todos corregidos. Los comentarios del código dicen cuál era el problema en
cada caso.