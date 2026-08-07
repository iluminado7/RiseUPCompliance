<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * Registro de cada acceso a un archivo adjunto.
 *
 * Quién vio o descargó qué evidencia y cuándo. Append-only en la
 * práctica: no hay razón legítima para editar ni borrar estas filas.
 *
 * @property int $id
 * @property int $company_id
 * @property int $file_id
 * @property int $complaint_id
 * @property int $user_id
 * @property string $action
 * @property string|null $ip_hash HMAC-SHA256 con IP_HASH_KEY
 * @property string|null $ip_enc CIFRADO
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Archivo $archivo
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @property-read \App\Models\Usuario $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo whereIpEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo whereIpHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccesoArchivo whereUserId($value)
 */
	class AccesoArchivo extends \Eloquent {}
}

namespace App\Models{
/**
 * Adjunto de una denuncia.
 *
 * clean_metadata guarda el resultado del strip de metadatos: es la
 * evidencia de que el EXIF stripping corrió sobre este archivo (H-004).
 *
 * declared_mime viene del cliente y puede estar falseado. Las decisiones
 * se toman SIEMPRE con detected_mime, que se calcula de los bytes.
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property string $original_name
 * @property string $storage_name
 * @property string $storage_bucket
 * @property string $storage_path
 * @property string|null $storage_version_id
 * @property string $sha256_hash SHA-256 del contenido — verificar en cada lectura
 * @property int $size_bytes
 * @property string|null $declared_mime Declarado por el cliente — puede estar falseado, no confiar solo en esto
 * @property string|null $detected_mime Detectado de los bytes — confiable
 * @property string|null $detected_extension
 * @property bool $encrypted_at_rest
 * @property string|null $encryption_mode
 * @property string|null $kms_key_id
 * @property string $file_status
 * @property string|null $scan_engine
 * @property \Illuminate\Support\Carbon|null $scan_date
 * @property string $scan_result
 * @property array<array-key, mixed>|null $clean_metadata Resultado del strip de metadatos: qué se encontró y qué se eliminó
 * @property string|null $quarantine_path
 * @property bool $available_for_analyst
 * @property string $uploaded_by_type
 * @property int|null $uploaded_by_user_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccesoArchivo> $accesos
 * @property-read int|null $accesos_count
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @property-read \App\Models\Usuario|null $subidoPor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo disponibles()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereAvailableForAnalyst($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereCleanMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereDeclaredMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereDetectedExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereDetectedMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereEncryptedAtRest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereEncryptionMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereFileStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereKmsKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereQuarantinePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereScanDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereScanEngine($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereScanResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereSha256Hash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereSizeBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereStorageBucket($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereStorageName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereStoragePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereStorageVersionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereUploadedByType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Archivo whereUploadedByUserId($value)
 */
	class Archivo extends \Eloquent {}
}

namespace App\Models{
/**
 * Área o sector denunciado. Catálogo global.
 *
 * @property int $id
 * @property string $code
 * @property string $name_es
 * @property string|null $description_es
 * @property int $display_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Empresa> $empresas
 * @property-read int|null $empresas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area whereDescriptionEs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area whereNameEs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Area whereUpdatedAt($value)
 */
	class Area extends \Eloquent {}
}

namespace App\Models{
/**
 * Asignación de una denuncia a un investigador.
 *
 * ended_at NULL = asignación vigente. El historial completo queda acá.
 *
 * H-010: esta tabla convive con Denuncia::assigned_to_user_id y las dos
 * pueden desincronizarse. La fuente de verdad debería ser esta; la
 * columna en complaints es denormalización para los índices de listado y
 * hay que escribirla SIEMPRE en la misma transacción. Se resuelve al
 * escribir el flujo de asignación (Etapa 5).
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property int $assigned_to_user_id
 * @property int|null $assigned_by_user_id Null si la asignación fue automática
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $ended_at Null = asignación vigente
 * @property-read \App\Models\Usuario $asignadoA
 * @property-read \App\Models\Usuario|null $asignadoPor
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion vigentes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion whereAssignedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion whereAssignedToUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asignacion whereReason($value)
 */
	class Asignacion extends \Eloquent {}
}

namespace App\Models{
/**
 * Cargo o puesto de la persona denunciada. Catálogo global.
 *
 * @property int $id
 * @property string $code
 * @property string $name_es
 * @property string|null $description_es
 * @property int $display_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Empresa> $empresas
 * @property-read int|null $empresas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereDescriptionEs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereNameEs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cargo whereUpdatedAt($value)
 */
	class Cargo extends \Eloquent {}
}

namespace App\Models{
/**
 * Categoría de irregularidad. Catálogo global: cada empresa elige qué
 * subconjunto ofrece en su canal, vía la pivote company_categories.
 *
 * @property int $id
 * @property string $code
 * @property string $name_es
 * @property string|null $description_es
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Denuncia> $denuncias
 * @property-read int|null $denuncias_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Empresa> $empresas
 * @property-read int|null $empresas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pregunta> $preguntas
 * @property-read int|null $preguntas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria activas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereDescriptionEs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereNameEs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Categoria whereUpdatedAt($value)
 */
	class Categoria extends \Eloquent {}
}

namespace App\Models{
/**
 * Configuración global del sistema, en pares clave/valor.
 *
 * Solo los managers de plataforma pueden modificarla.
 *
 * Cuando is_secret = 1 el valor va en value_enc, cifrado.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string|null $value_enc CIFRADO — solo cuando is_secret = 1
 * @property string $value_type
 * @property bool $is_secret
 * @property int $version
 * @property string|null $description
 * @property int|null $updated_by_manager_id Solo los tenant_managers pueden modificar esta tabla
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\ManagerPlataforma|null $actualizadoPor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal whereIsSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal whereUpdatedByManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal whereValueEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal whereValueType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConfigGlobal whereVersion($value)
 */
	class ConfigGlobal extends \Eloquent {}
}

namespace App\Models{
/**
 * Datos fiscales de la empresa (1:1).
 *
 * tax_id es el CUIT. Es la clave natural de deduplicación entre módulos
 * cuando se construya la plataforma unificada (§8.1): identifica a una
 * organización mejor que el nombre. Validar con la regla portada de
 * Business Partner, no reescribirla.
 *
 * No usa PerteneceAEmpresa: se llega siempre desde la empresa, y el
 * acceso a facturación es decisión de Policy, no de scope.
 *
 * @property int $id
 * @property int $company_id
 * @property string|null $tax_id CUIT
 * @property string|null $legal_name
 * @property string|null $vat_status
 * @property string|null $fiscal_address
 * @property array<array-key, mixed>|null $billing_emails Array de strings, ej: ["a@b.com","c@d.com"]
 * @property bool $uses_global_price
 * @property numeric|null $custom_amount
 * @property int $billing_day
 * @property string $preferred_payment
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Empresa $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereBillingDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereBillingEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereCustomAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereFiscalAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales wherePreferredPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereUsesGlobalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscales whereVatStatus($value)
 */
	class DatosFiscales extends \Eloquent {}
}

namespace App\Models{
/**
 * Denuncia — entidad central del dominio.
 *
 * Dos ejes de estado, no confundirlos:
 *   - status: interno, lo ve el panel (new, seen, in_progress, ...)
 *   - public_status: lo ve el denunciante, en castellano ('Recibida', ...)
 *
 * encryption_salt es la clave del crypto-shredding: de él se derivan las
 * claves de todos los datos personales de esta denuncia. En $hidden para
 * que nunca salga en un toArray() o en un JSON por descuido.
 *
 * @property int $id
 * @property int $company_id
 * @property string $internal_code
 * @property bool $is_anonymous
 * @property string $intake_channel
 * @property string $submission_status
 * @property int $category_id
 * @property int|null $relationship_id
 * @property string|null $relationship_other_text
 * @property int|null $branch_id
 * @property int|null $reported_area_id
 * @property string|null $reported_area_other
 * @property int|null $reported_position_id
 * @property string|null $reported_position_other
 * @property \Illuminate\Support\Carbon|null $incident_date Fecha del hecho reportada por el denunciante
 * @property string|null $reported_first_name_enc CIFRADO — crypto-shredding vía encryption_salt
 * @property string|null $reported_last_name_enc CIFRADO — crypto-shredding vía encryption_salt
 * @property \App\Enums\PrioridadDenuncia $priority
 * @property \App\Enums\EstadoDenuncia $status
 * @property string $public_status Estado que ve el denunciante — desacoplado del status interno
 * @property int|null $assigned_to_user_id
 * @property string $tracking_code_hash SHA-256 — el texto plano NUNCA se guarda
 * @property string|null $encryption_salt Salt hex para derivar claves de esta denuncia. NULL = purgada (crypto-shredding)
 * @property bool $chat_enabled
 * @property \Illuminate\Support\Carbon|null $chat_enabled_at
 * @property int|null $chat_enabled_by_user_id
 * @property bool $privacy_notice_accepted
 * @property int|null $privacy_notice_version_id
 * @property \Illuminate\Support\Carbon|null $privacy_notice_accepted_at
 * @property bool $captcha_validated
 * @property \Illuminate\Support\Carbon|null $captcha_validated_at
 * @property string|null $captcha_provider
 * @property \Illuminate\Support\Carbon $last_action_at
 * @property \Illuminate\Support\Carbon|null $first_response_due_at
 * @property \Illuminate\Support\Carbon|null $resolution_due_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $purged_at Lo setea SOLO la purga automática por retención, nunca a mano
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Archivo> $archivos
 * @property-read int|null $archivos_count
 * @property-read \App\Models\Area|null $area
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asignacion> $asignaciones
 * @property-read int|null $asignaciones_count
 * @property-read \App\Models\Usuario|null $asignadoA
 * @property-read \App\Models\Cargo|null $cargo
 * @property-read \App\Models\Categoria $categoria
 * @property-read \App\Models\Denunciante|null $denunciante
 * @property-read \App\Models\Empresa $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventoDenuncia> $eventos
 * @property-read int|null $eventos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Exportacion> $exportaciones
 * @property-read int|null $exportaciones_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HistorialEstado> $historialEstados
 * @property-read int|null $historial_estados_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Mensaje> $mensajes
 * @property-read int|null $mensajes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NotaInterna> $notas
 * @property-read int|null $notas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Respuesta> $respuestas
 * @property-read int|null $respuestas_count
 * @property-read \App\Models\Sucursal|null $sucursal
 * @property-read \App\Models\Vinculo|null $vinculo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereAssignedToUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereCaptchaProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereCaptchaValidated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereCaptchaValidatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereChatEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereChatEnabledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereChatEnabledByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereEncryptionSalt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereFirstResponseDueAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereIncidentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereIntakeChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereInternalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereIsAnonymous($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereLastActionAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia wherePrivacyNoticeAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia wherePrivacyNoticeAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia wherePrivacyNoticeVersionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia wherePublicStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia wherePurgedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereRelationshipId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereRelationshipOtherText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereReportedAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereReportedAreaOther($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereReportedFirstNameEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereReportedLastNameEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereReportedPositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereReportedPositionOther($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereResolutionDueAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereSubmissionStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereTrackingCodeHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denuncia whereUpdatedAt($value)
 */
	class Denuncia extends \Eloquent {}
}

namespace App\Models{
/**
 * Datos personales del denunciante (1:1 con la denuncia).
 *
 * Existe solo cuando la denuncia NO es anónima. Todo el contenido está
 * cifrado con claves derivadas de Denuncia::encryption_salt.
 *
 * Para purgar NO se borra esta fila: se destruye el salt en complaints.
 * Los datos quedan cifrados sin clave posible y la traza no muestra huecos.
 *
 * Todas las columnas cifradas están en $hidden: nunca deben salir en un
 * toArray() ni en la respuesta de un endpoint por descuido.
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property string|null $first_name_enc CIFRADO
 * @property string|null $last_name_enc CIFRADO
 * @property string|null $gender_enc CIFRADO
 * @property string|null $email_enc CIFRADO
 * @property string|null $national_id_enc CIFRADO
 * @property string|null $phone_enc CIFRADO
 * @property string $encryption_key_id ID de la clave maestra usada (ej: key-v1). El salt vive en complaints
 * @property int $encryption_version
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereEmailEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereEncryptionKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereEncryptionVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereFirstNameEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereGenderEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereLastNameEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante whereNationalIdEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Denunciante wherePhoneEnc($value)
 */
	class Denunciante extends \Eloquent {}
}

namespace App\Models{
/**
 * Versión de un documento legal: aviso de privacidad, términos, política.
 *
 * company_id NULL = documento global. Cada denuncia guarda qué versión
 * exacta aceptó el denunciante (complaints.privacy_notice_version_id):
 * es lo que permite demostrar después qué texto se le mostró.
 *
 * content_hash verifica que el documento no cambió desde su publicación.
 *
 * @property int $id
 * @property int|null $company_id Null cuando es un documento global
 * @property string $document_type
 * @property string $version
 * @property string $content_hash SHA-256 del contenido — verificar antes de darlo por válido
 * @property \Illuminate\Support\Carbon $published_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Empresa|null $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal vigentes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal whereContentHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentoLegal whereVersion($value)
 */
	class DocumentoLegal extends \Eloquent {}
}

namespace App\Models{
/**
 * Empresa cliente (tenant).
 *
 * No usa PerteneceAEmpresa: es la empresa misma, no algo que pertenece a
 * una. El filtrado de qué empresas ve cada rol va en la Policy.
 *
 * El slug es la clave del canal público: /{slug} es el formulario de
 * denuncia de esta empresa.
 *
 * @property int $id
 * @property int $manager_id
 * @property string $name
 * @property string $email
 * @property string $slug
 * @property \App\Enums\EstadoEmpresa $status
 * @property array<array-key, mixed>|null $public_configuration
 * @property string|null $sensitive_configuration_enc CIFRADO — secretos del tenant: API keys, webhooks, certs SSO
 * @property string $default_language
 * @property string $timezone
 * @property int $complaints_retention_days
 * @property int $logs_retention_days
 * @property int $files_retention_days
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Area> $areas
 * @property-read int|null $areas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cargo> $cargos
 * @property-read int|null $cargos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Categoria> $categorias
 * @property-read int|null $categorias_count
 * @property-read \App\Models\DatosFiscales|null $datosFiscales
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Denuncia> $denuncias
 * @property-read int|null $denuncias_count
 * @property-read \App\Models\ManagerPlataforma $managerPlataforma
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Sucursal> $sucursales
 * @property-read int|null $sucursales_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Usuario> $usuarios
 * @property-read int|null $usuarios_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vinculo> $vinculos
 * @property-read int|null $vinculos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereComplaintsRetentionDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereDefaultLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereFilesRetentionDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereLogsRetentionDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa wherePublicConfiguration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereSensitiveConfigurationEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Empresa whereUpdatedAt($value)
 */
	class Empresa extends \Eloquent {}
}

namespace App\Models{
/**
 * Eventos de la denuncia — la línea de tiempo que ve el investigador.
 *
 * No confundir con audit_logs: eso es la traza legal encadenada, esto es
 * la actividad del caso. payload NUNCA debe contener datos personales,
 * solo IDs.
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property int|null $user_id Null para eventos de sistema o del denunciante
 * @property string $event_type
 * @property array<array-key, mixed>|null $payload JSON plano — nunca debe contener datos personales, solo IDs
 * @property string|null $request_id
 * @property string|null $source_ip_hash HMAC-SHA256 con IP_HASH_KEY
 * @property string|null $source_ip_enc CIFRADO
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @property-read \App\Models\Usuario|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia whereSourceIpEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia whereSourceIpHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventoDenuncia whereUserId($value)
 */
	class EventoDenuncia extends \Eloquent {}
}

namespace App\Models{
/**
 * PDF o paquete de evidencia generado a partir de una denuncia.
 *
 * file_hash permite demostrar que un export presentado como prueba es
 * exactamente el que el sistema produjo.
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property int $generated_by_user_id
 * @property string $export_type
 * @property array<array-key, mixed>|null $query_params_json Filtros aplicados al generar: company_id, branch_id, status, rango de fechas
 * @property string $storage_path
 * @property string $file_hash SHA-256 del archivo exportado
 * @property int $file_size_bytes
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @property-read \App\Models\Usuario $generadaPor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion whereExportType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion whereFileHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion whereFileSizeBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion whereGeneratedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion whereQueryParamsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Exportacion whereStoragePath($value)
 */
	class Exportacion extends \Eloquent {}
}

namespace App\Models{
/**
 * Factura mensual de una empresa. afip_cae es el código de autorización.
 *
 * @property int $id
 * @property int $company_id
 * @property int $billing_month
 * @property int $billing_year
 * @property numeric|null $subtotal_amount
 * @property numeric|null $tax_amount
 * @property numeric $total_amount
 * @property string $currency_code
 * @property string|null $afip_cae
 * @property \Illuminate\Support\Carbon|null $afip_cae_expiry
 * @property string|null $invoice_number
 * @property string $payment_status
 * @property string|null $concept
 * @property \Illuminate\Support\Carbon $issue_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $payment_date
 * @property string|null $pdf_url
 * @property string|null $pdf_hash
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Empresa $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura impagas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereAfipCae($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereAfipCaeExpiry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereBillingMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereBillingYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereConcept($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereCurrencyCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereIssueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura wherePdfHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura wherePdfUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereSubtotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereTaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Factura whereUpdatedAt($value)
 */
	class Factura extends \Eloquent {}
}

namespace App\Models{
/**
 * Transiciones de estado de una denuncia.
 *
 * from_status y to_status son string y no EstadoDenuncia a propósito: el
 * historial tiene que poder conservar estados que ya no existan en el
 * enum. Castearlos rompería el historial viejo si alguna vez se retira
 * un estado.
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property string|null $from_status Null en la primera transición
 * @property string $to_status
 * @property int|null $changed_by_user_id Null si lo cambió el sistema
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @property-read \App\Models\Usuario|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado whereChangedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado whereFromStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialEstado whereToStatus($value)
 */
	class HistorialEstado extends \Eloquent {}
}

namespace App\Models{
/**
 * Marca de lectura de un mensaje por parte de un usuario del panel.
 *
 * No lleva company_id: se llega siempre a través del mensaje, que sí lo
 * tiene. Por eso no usa PerteneceAEmpresa.
 *
 * @property int $message_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $read_at
 * @property-read \App\Models\Mensaje $mensaje
 * @property-read \App\Models\Usuario $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturaMensaje newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturaMensaje newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturaMensaje query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturaMensaje whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturaMensaje whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LecturaMensaje whereUserId($value)
 */
	class LecturaMensaje extends \Eloquent {}
}

namespace App\Models{
/**
 * Manager de plataforma — tabla tenant_managers.
 *
 * Es la entidad del lado de Go Harvey, no de la empresa cliente: cada
 * empresa tiene un manager asignado (companies.manager_id), y los
 * usuarios de plataforma se vinculan por users.manager_id.
 *
 * Ojo con la trampa del schema: companies.manager_id apunta ACÁ, no a
 * users. Es el error que ya costó una sesión de depuración durante el
 * onboarding.
 *
 * @property int $id
 * @property string $full_name
 * @property string $email
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Empresa> $empresas
 * @property-read int|null $empresas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Usuario> $usuarios
 * @property-read int|null $usuarios_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerPlataforma newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerPlataforma newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerPlataforma query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerPlataforma whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerPlataforma whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerPlataforma whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerPlataforma whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ManagerPlataforma whereUpdatedAt($value)
 */
	class ManagerPlataforma extends \Eloquent {}
}

namespace App\Models{
/**
 * Mensaje del chat entre denunciante e investigador.
 *
 * content va cifrado con clave derivada de Denuncia::encryption_salt.
 * En el sistema actual el encryption_key_id era el literal
 * 'key-dev-placeholder' y el contenido iba en claro.
 *
 * El analista puede retirar un mensaje solo si el denunciante todavía no
 * lo leyó. original_content guarda la copia de auditoría, también
 * cifrada, y nunca se muestra al denunciante.
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property string $sender_type
 * @property int|null $sender_user_id Null si el emisor es el denunciante o el sistema
 * @property string $content CIFRADO — crypto-shredding vía complaints.encryption_salt
 * @property string $encryption_key_id ID de la clave maestra usada. El salt vive en complaints
 * @property bool $is_read_by_reporter
 * @property bool $is_deleted_by_sender Solo permitido si is_read_by_reporter = 0
 * @property \Illuminate\Support\Carbon|null $deleted_by_sender_at
 * @property string|null $original_content CIFRADO — copia de auditoría previa al borrado, nunca visible al denunciante
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Usuario|null $emisor
 * @property-read \App\Models\Empresa $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LecturaMensaje> $lecturas
 * @property-read int|null $lecturas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje visiblesParaDenunciante()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereDeletedBySenderAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereEncryptionKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereIsDeletedBySender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereIsReadByReporter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereOriginalContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereSenderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mensaje whereSenderUserId($value)
 */
	class Mensaje extends \Eloquent {}
}

namespace App\Models{
/**
 * Nota interna del investigador sobre el caso.
 *
 * Nunca visible para el denunciante.
 *
 * PENDIENTE: content va en texto plano, portado tal cual del original.
 * La auditoría no lo marcó (H-003 cubría solo las respuestas), pero estas
 * notas suelen tener más detalle sensible que la denuncia misma:
 * hipótesis, nombres de testigos, resultados parciales. Cifrarlas con el
 * mismo salt por denuncia es barato y conviene antes de tener datos reales.
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property int $user_id
 * @property string $content
 * @property string|null $original_content Copia previa a la edición
 * @property bool $is_priority
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $edited_at
 * @property int|null $edited_by_user_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $deleted_by_user_id
 * @property-read \App\Models\Usuario $autor
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Usuario|null $editadaPor
 * @property-read \App\Models\Usuario|null $eliminadaPor
 * @property-read \App\Models\Empresa $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna vigentes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereDeletedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereEditedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereEditedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereIsPriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereOriginalContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotaInterna whereUserId($value)
 */
	class NotaInterna extends \Eloquent {}
}

namespace App\Models{
/**
 * Aviso hacia el DENUNCIANTE (email o código de seguimiento).
 *
 * recipient_enc va cifrado: en una denuncia no anónima, el email
 * identifica a la persona.
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property string $type
 * @property string $channel
 * @property string|null $recipient_enc CIFRADO — email o código de seguimiento del destinatario
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia whereRecipientEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionDenuncia whereType($value)
 */
	class NotificacionDenuncia extends \Eloquent {}
}

namespace App\Models{
/**
 * Aviso hacia un usuario del panel.
 *
 * @property int $id
 * @property int $company_id
 * @property int $user_id
 * @property int|null $complaint_id
 * @property string $type
 * @property string $status
 * @property array<array-key, mixed>|null $metadata Datos adicionales según el tipo, ej: {empresa, token_id}
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Denuncia|null $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @property-read \App\Models\Usuario $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario noLeidas()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificacionUsuario whereUserId($value)
 */
	class NotificacionUsuario extends \Eloquent {}
}

namespace App\Models\Onboarding{
/**
 * @property int $id
 * @property int $token_id
 * @property int $company_onboarding_id
 * @property string|null $tax_id CUIT
 * @property string|null $legal_name
 * @property string|null $vat_status
 * @property string|null $fiscal_address
 * @property array<array-key, mixed>|null $billing_emails
 * @property bool $uses_global_price
 * @property numeric|null $custom_amount
 * @property int $billing_day
 * @property string $preferred_payment
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Onboarding\EmpresaOnboarding $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereBillingDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereBillingEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereCompanyOnboardingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereCustomAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereFiscalAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding wherePreferredPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereTokenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereUsesGlobalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DatosFiscalesOnboarding whereVatStatus($value)
 */
	class DatosFiscalesOnboarding extends \Eloquent {}
}

namespace App\Models\Onboarding{
/**
 * Datos de empresa cargados en el formulario de alta, antes de confirmar.
 *
 * DIVERGENCIA HEREDADA: los defaults de retención no coinciden con los de
 * `companies` (3660 días acá contra 365 allá). Un alta por onboarding
 * queda con diez años de retención y una creada a mano con uno. Es una
 * decisión de negocio pendiente, no un bug del port.
 *
 * @property int $id
 * @property int $token_id
 * @property string $name
 * @property string $email
 * @property string $slug
 * @property string $default_language
 * @property string $timezone
 * @property int $retention_complaints_days
 * @property int $retention_files_days
 * @property int $retention_logs_days
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Onboarding\DatosFiscalesOnboarding|null $datosFiscales
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Onboarding\SucursalOnboarding> $sucursales
 * @property-read int|null $sucursales_count
 * @property-read \App\Models\Onboarding\TokenOnboarding $token
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Onboarding\UsuarioOnboarding> $usuarios
 * @property-read int|null $usuarios_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereDefaultLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereRetentionComplaintsDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereRetentionFilesDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereRetentionLogsDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmpresaOnboarding whereTokenId($value)
 */
	class EmpresaOnboarding extends \Eloquent {}
}

namespace App\Models\Onboarding{
/**
 * @property int $id
 * @property int $token_id
 * @property int $company_onboarding_id
 * @property string $name
 * @property string|null $internal_code
 * @property string|null $address
 * @property bool $is_headquarter
 * @property int $display_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Onboarding\EmpresaOnboarding $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding whereCompanyOnboardingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding whereInternalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding whereIsHeadquarter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SucursalOnboarding whereTokenId($value)
 */
	class SucursalOnboarding extends \Eloquent {}
}

namespace App\Models\Onboarding{
/**
 * Invitación de alta de empresa. Vence a las 48 horas.
 *
 * El token viaja en la URL del formulario público, así que es el único
 * control de acceso de esa pantalla: tiene que generarse con
 * random_bytes y verificarse en tiempo constante.
 *
 * @property int $id
 * @property string $token
 * @property int $created_by Superadmin que generó la invitación
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Usuario $creadoPor
 * @property-read \App\Models\Onboarding\DatosFiscalesOnboarding|null $datosFiscales
 * @property-read \App\Models\Onboarding\EmpresaOnboarding|null $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Onboarding\SucursalOnboarding> $sucursales
 * @property-read int|null $sucursales_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Onboarding\UsuarioOnboarding> $usuarios
 * @property-read int|null $usuarios_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding utilizables()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TokenOnboarding whereUsedAt($value)
 */
	class TokenOnboarding extends \Eloquent {}
}

namespace App\Models\Onboarding{
/**
 * Usuario cargado en el alta, antes de confirmar.
 *
 * OJO: acá `role` es un enum de strings ('admin_principal' | 'gestor'),
 * mientras que en la tabla real users se usa role_id. La confirmación de
 * alta tiene que traducir uno en otro.
 *
 * @property int $id
 * @property int $token_id
 * @property int $company_onboarding_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password_hash
 * @property string $role
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Onboarding\EmpresaOnboarding $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding whereCompanyOnboardingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding wherePasswordHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsuarioOnboarding whereTokenId($value)
 */
	class UsuarioOnboarding extends \Eloquent {}
}

namespace App\Models{
/**
 * Pregunta del cuestionario de una categoría.
 *
 * Dos niveles: company_id NULL es el catálogo maestro (sirve a todas las
 * empresas); company_id poblado es una pregunta propia de esa empresa.
 *
 * question_text_es es INMUTABLE: para cambiar el enunciado se crea una
 * versión nueva, no se hace UPDATE. valid_from / valid_to delimitan la
 * vigencia.
 *
 * NO usa PerteneceAEmpresa: el scope filtraría por company_id y
 * escondería el catálogo maestro, que es justo lo que toda empresa
 * necesita ver. El filtrado correcto es "las mías O las del maestro",
 * que es lo que hace scopeParaEmpresa().
 *
 * @property int $id
 * @property int $category_id
 * @property int|null $company_id NULL = catálogo maestro; poblado = pregunta exclusiva de la empresa
 * @property int|null $catalog_question_id Pregunta del catálogo base de la que deriva. NULL = personalizada
 * @property int $version
 * @property int $question_order
 * @property string $question_text_es INMUTABLE — para cambiarlo se crea una versión nueva, no se hace UPDATE
 * @property \Illuminate\Support\Carbon $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_to
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Categoria $categoria
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read Pregunta|null $preguntaBase
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta paraEmpresa(int $empresaId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta vigentes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereCatalogQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereQuestionOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereQuestionTextEs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereValidTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregunta whereVersion($value)
 */
	class Pregunta extends \Eloquent {}
}

namespace App\Models{
/**
 * Trazabilidad de procesamientos con modelos de lenguaje.
 *
 * input_hash guarda solo el SHA-256 de la entrada, nunca el texto de la
 * denuncia. contains_pii e input_redacted registran si se envió material
 * identificable a un tercero — dato relevante para el cumplimiento.
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property string $processing_type
 * @property string $status
 * @property string $model
 * @property string $provider
 * @property string|null $provider_request_id
 * @property string|null $processing_region
 * @property string $input_hash SHA-256 de la entrada — para deduplicación. NUNCA guardar la entrada cruda
 * @property array<array-key, mixed>|null $output
 * @property int|null $input_tokens
 * @property int|null $output_tokens
 * @property numeric|null $cost_usd
 * @property bool $contains_pii
 * @property bool $input_redacted
 * @property string|null $prompt_version
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereContainsPii($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereCostUsd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereInputHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereInputRedacted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereInputTokens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereOutput($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereOutputTokens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereProcessingRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereProcessingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA wherePromptVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereProviderRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProcesamientoIA whereUpdatedAt($value)
 */
	class ProcesamientoIA extends \Eloquent {}
}

namespace App\Models{
/**
 * Registro de la traza de auditoría encadenada.
 *
 * APPEND-ONLY. Nunca UPDATE ni DELETE. Los métodos de escritura y borrado
 * están deshabilitados a propósito: el único camino para insertar es
 * AuditLogService, que calcula el hash y toma el lock del sequence.
 *
 * chain_scope es columna generada: 0 = cadena de plataforma (eventos sin
 * empresa: login fallido, actividad del superadmin), >0 = cadena de esa
 * empresa. El verificador recorre N+1 cadenas.
 *
 * NO usa PerteneceAEmpresa: la lectura de logs se decide por Policy, y
 * un scope automático escondería la cadena de plataforma.
 *
 * @property int $id
 * @property int|null $company_id Null para eventos de plataforma: login fallido, acción de manager, sistema
 * @property int|null $chain_scope 0 = cadena de plataforma; >0 = cadena de la empresa
 * @property int $company_sequence
 * @property int|null $user_id Null para eventos de sistema
 * @property string $origin
 * @property string $action
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property int|null $affected_complaint_id
 * @property string $result
 * @property string|null $ip_hash HMAC-SHA256 con IP_HASH_KEY
 * @property string|null $ip_enc CIFRADO
 * @property string|null $user_agent_hash
 * @property string|null $user_agent_enc CIFRADO
 * @property string|null $request_id
 * @property string|null $session_id
 * @property string|null $correlation_id
 * @property string|null $detail_enc CIFRADO
 * @property string|null $previous_hash Hash del registro anterior de la cadena
 * @property string $log_hash SHA-256 de este registro, incluyendo previous_hash
 * @property string|null $external_timestamp_token Token TSA para certificación legal
 * @property string|null $signed_hash
 * @property string|null $signature_provider
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\Usuario|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria cadenaDeEmpresa(int $empresaId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria cadenaDePlataforma()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereAffectedComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereChainScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereCompanySequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereCorrelationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereDetailEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereExternalTimestampToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereIpEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereIpHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereLogHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereOrigin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria wherePreviousHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereSignatureProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereSignedHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereUserAgentEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereUserAgentHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegistroAuditoria whereUserId($value)
 */
	class RegistroAuditoria extends \Eloquent {}
}

namespace App\Models{
/**
 * Respuesta al cuestionario de la categoría.
 *
 * question_text es una copia inmutable del enunciado al momento del
 * envío: si el catálogo cambia después, la denuncia sigue mostrando la
 * pregunta tal como se le formuló a esa persona.
 *
 * H-003: answer_encrypted guardaba texto plano pese al nombre. El nombre
 * de la columna se conserva (§2.4) pero el contenido va cifrado de verdad.
 *
 * @property int $id
 * @property int $company_id
 * @property int $complaint_id
 * @property int|null $category_question_id Null si la pregunta se eliminó del catálogo
 * @property int $question_order
 * @property string $question_text Copia inmutable del enunciado al momento del envío
 * @property string|null $answer_encrypted CIFRADO — crypto-shredding vía complaints.encryption_salt
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Denuncia $denuncia
 * @property-read \App\Models\Empresa $empresa
 * @property-read \App\Models\Pregunta|null $pregunta
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta whereAnswerEncrypted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta whereCategoryQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta whereQuestionOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Respuesta whereQuestionText($value)
 */
	class Respuesta extends \Eloquent {}
}

namespace App\Models{
/**
 * Rol del panel. Catálogo global, no pertenece a ninguna empresa.
 *
 * permissions_json se conserva por compatibilidad con el sistema actual,
 * pero las decisiones de autorización van en las Policies, no leyendo
 * este JSON: un permiso disperso en datos es más difícil de auditar que
 * uno expresado en código y cubierto por tests.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $hierarchy_level
 * @property array<array-key, mixed> $permissions_json
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Usuario> $usuarios
 * @property-read int|null $usuarios_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol whereHierarchyLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol wherePermissionsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol whereUpdatedAt($value)
 */
	class Rol extends \Eloquent {}
}

namespace App\Models{
/**
 * Cada consulta al canal público de seguimiento.
 *
 * Es la tabla más delicada del sistema para el anonimato: relaciona una
 * IP con una denuncia. ip_hash es HMAC con IP_HASH_KEY, que vive solo en
 * el entorno — el SHA-256 sin clave del sistema original era reversible
 * por fuerza bruta sobre el espacio IPv4 completo.
 *
 * NO usa PerteneceAEmpresa: se escribe desde el canal público, sin
 * sesión, y company_id puede ser null cuando el código no corresponde a
 * ninguna empresa. Los intentos fallidos se registran igual: son la
 * señal de que alguien está probando códigos.
 *
 * @property int $id
 * @property int|null $company_id Null cuando el código no corresponde a ninguna empresa
 * @property int|null $complaint_id
 * @property string $tracking_code_hash SHA-256 — el texto plano nunca se guarda
 * @property string $ip_hash HMAC-SHA256 con IP_HASH_KEY — no reversible sin la clave
 * @property string $ip_enc CIFRADO
 * @property string|null $user_agent_hash
 * @property string|null $user_agent_enc CIFRADO
 * @property string $result
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Denuncia|null $denuncia
 * @property-read \App\Models\Empresa|null $empresa
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento whereComplaintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento whereIpEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento whereIpHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento whereTrackingCodeHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento whereUserAgentEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SesionSeguimiento whereUserAgentHash($value)
 */
	class SesionSeguimiento extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $internal_code
 * @property string|null $address
 * @property bool $is_headquarter
 * @property bool $is_active
 * @property string|null $default_language
 * @property string|null $timezone
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Denuncia> $denuncias
 * @property-read int|null $denuncias_count
 * @property-read \App\Models\Empresa $empresa
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Usuario> $usuarios
 * @property-read int|null $usuarios_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereDefaultLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereInternalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereIsHeadquarter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sucursal whereUpdatedAt($value)
 */
	class Sucursal extends \Eloquent {}
}

namespace App\Models{
/**
 * Usuario del panel de gestión.
 *
 * Dos tipos, según cuál vínculo esté poblado:
 *   - de empresa:    company_id != null, manager_id == null
 *   - de plataforma: manager_id != null, company_id == null
 *
 * El denunciante NO está acá y nunca debe estarlo.
 *
 * Este modelo NO usa PerteneceAEmpresa: si se le aplicara el scope, un
 * usuario no podría verse a sí mismo cuando company_id es null, y el
 * login del superadmin quedaría roto. El filtrado por empresa en los
 * listados de usuarios va en la Policy.
 *
 * @property int $id
 * @property int|null $manager_id Null cuando es usuario de empresa
 * @property int|null $company_id Null cuando es manager de plataforma
 * @property int $role_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone CIFRADO
 * @property string $password_hash
 * @property \App\Enums\EstadoUsuario $status
 * @property string|null $preferred_language
 * @property string|null $timezone
 * @property bool $two_factor_enabled
 * @property string|null $two_factor_secret_enc CIFRADO — secreto TOTP, se descifra para validar 2FA
 * @property int $failed_attempts
 * @property \Illuminate\Support\Carbon|null $locked_until
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $last_login_ip_hash HMAC-SHA256 de la IP
 * @property string|null $last_login_ip_enc CIFRADO
 * @property string|null $recovery_code_hash
 * @property \Illuminate\Support\Carbon|null $recovery_expires_at
 * @property \Illuminate\Support\Carbon|null $password_changed_at
 * @property bool $must_change_password
 * @property \Illuminate\Support\Carbon|null $last_failed_login_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Denuncia> $denunciasAsignadas
 * @property-read int|null $denuncias_asignadas_count
 * @property-read \App\Models\Empresa|null $empresa
 * @property-read \App\Models\ManagerPlataforma|null $managerPlataforma
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Rol $rol
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Sucursal> $sucursales
 * @property-read int|null $sucursales_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereFailedAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereLastFailedLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereLastLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereLastLoginIpEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereLastLoginIpHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereLockedUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereMustChangePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario wherePasswordChangedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario wherePasswordHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario wherePreferredLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereRecoveryCodeHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereRecoveryExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereTwoFactorEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereTwoFactorSecretEnc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Usuario whereUpdatedAt($value)
 */
	class Usuario extends \Eloquent {}
}

namespace App\Models{
/**
 * Vínculo del denunciante con la empresa: empleado, ex-empleado,
 * cliente, proveedor, otro. Catálogo global.
 *
 * @property int $id
 * @property string $code
 * @property string $name_es
 * @property string|null $description_es
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Empresa> $empresas
 * @property-read int|null $empresas_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo activos()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo whereDescriptionEs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo whereNameEs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vinculo whereUpdatedAt($value)
 */
	class Vinculo extends \Eloquent {}
}

