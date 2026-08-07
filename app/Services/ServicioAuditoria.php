<?php

namespace App\Services;

use App\Models\RegistroAuditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Traza de auditoría con encadenamiento de hashes.
 *
 * Portado de backend/audit_helper.php con cuatro correcciones. Ninguna es
 * cosmética: el helper original tenía tres formas distintas de perder un
 * evento en silencio.
 *
 * 1. EL HASH CUBRE TODO EL REGISTRO.
 *    El original hasheaba 5 de 20 campos: company_id, company_sequence,
 *    action, result y previous_hash. Quedaban FUERA user_id,
 *    affected_complaint_id, entity_id, detail y created_at — o sea que con
 *    acceso de escritura a la base se podía cambiar quién hizo qué, sobre
 *    qué denuncia y cuándo, sin que el verificador detectara nada. Para una
 *    traza destinada a servir como evidencia, eso era casi lo único que
 *    importaba proteger.
 *
 *    Además concatenaba sin separador: action='a' + result='bc' daba el
 *    mismo hash que action='ab' + result='c'. Acá va un separador que no
 *    puede aparecer en los datos (0x1F, unit separator).
 *
 * 2. EL SEQUENCE SE TOMA CON LOCK.
 *    El original hacía SELECT ... ORDER BY DESC LIMIT 1 y después INSERT,
 *    sin transacción. Dos requests simultáneos de la misma empresa leían el
 *    mismo máximo y el segundo violaba el unique. Acá el SELECT va con
 *    FOR UPDATE dentro de una transacción.
 *
 * 3. LOS EVENTOS DE PLATAFORMA SE REGISTRAN.
 *    El original abría con `if (!$company_id) return;`. Como el login
 *    fallido pasa company_id = 0 y el superadmin tiene company_id NULL,
 *    NINGUNO de los dos se registró jamás. Ahora company_id es nullable y
 *    esos eventos van a la cadena de plataforma (chain_scope = 0).
 *
 * 4. registrar() LANZA EXCEPCIÓN.
 *    El original envolvía todo en try/catch y mandaba el error al log de
 *    PHP. Según §6.2 del brief, para operaciones destructivas el registro
 *    va dentro de la transacción y SIN catch: si falla registrar, la
 *    operación se revierte. Una destrucción sin registro es peor que una
 *    que no ocurre.
 *
 *    Para eventos no críticos existe registrarSeguro(), que sí absorbe el
 *    error — pero es la excepción y hay que elegirla a propósito.
 */
class ServicioAuditoria
{
    private const SEPARADOR = "\x1F";

    public function __construct(
        private readonly HashIp $hashIp,
        private readonly ServicioCifrado $cifrado,
    ) {}

    /**
     * Registra un evento. Lanza excepción si no puede.
     *
     * Usar este método dentro de la transacción de cualquier operación
     * destructiva o irreversible.
     *
     * @param  array{
     *   company_id?: int|null,
     *   user_id?: int|null,
     *   origin?: string,
     *   action: string,
     *   entity_type?: string|null,
     *   entity_id?: int|null,
     *   affected_complaint_id?: int|null,
     *   result?: string,
     *   ip?: string|null,
     *   user_agent?: string|null,
     *   detail?: string|null,
     *   request_id?: string|null,
     *   correlation_id?: string|null,
     *   es_anonimo?: bool,
     * }  $datos
     */
    public function registrar(array $datos): RegistroAuditoria
    {
        $empresaId = $datos['company_id'] ?? null;
        $alcance = $empresaId ?? 0;

        return DB::transaction(function () use ($datos, $empresaId, $alcance) {
            // Lock de la cadena: bloquea a otros escritores del mismo
            // alcance hasta que esta transacción cierre.
            $anterior = DB::table('audit_logs')
                ->where('chain_scope', $alcance)
                ->orderByDesc('company_sequence')
                ->lockForUpdate()
                ->first(['log_hash', 'company_sequence']);

            $hashAnterior = $anterior->log_hash ?? null;
            $secuencia = $anterior ? (int) $anterior->company_sequence + 1 : 1;

            $creadoEn = now();

            $fila = [
                'company_id' => $empresaId,
                'company_sequence' => $secuencia,
                'user_id' => $datos['user_id'] ?? null,
                'origin' => $this->validar($datos['origin'] ?? 'web', ['web', 'api', 'system', 'cron'], 'web'),
                'action' => mb_substr(trim($datos['action']), 0, 100),
                'entity_type' => isset($datos['entity_type']) ? mb_substr($datos['entity_type'], 0, 60) : null,
                'entity_id' => $datos['entity_id'] ?? null,
                'affected_complaint_id' => $datos['affected_complaint_id'] ?? null,
                'result' => $this->validar($datos['result'] ?? 'success', ['success', 'error', 'blocked'], 'success'),
                'ip_hash' => $this->hashIp->hash($datos['ip'] ?? request()?->ip()),
                'user_agent_hash' => $this->hashIp->hash($datos['user_agent'] ?? request()?->userAgent()),
                'request_id' => $datos['request_id'] ?? null,
                'session_id' => $this->resolverSessionId($datos),
                'correlation_id' => $datos['correlation_id'] ?? null,
                'previous_hash' => $hashAnterior,
                'created_at' => $creadoEn,
            ];

            // Los campos cifrados no entran al hash: el ciphertext cambia
            // en cada escritura (IV aleatorio) y haría irreproducible la
            // verificación. Se hashea el texto plano, más abajo.
            $fila['ip_enc'] = $this->cifrar($datos['ip'] ?? request()?->ip());
            $fila['user_agent_enc'] = $this->cifrar($datos['user_agent'] ?? request()?->userAgent());
            $fila['detail_enc'] = $this->cifrar($datos['detail'] ?? null);

            $fila['log_hash'] = $this->calcularHash($fila, [
                'ip' => $datos['ip'] ?? request()?->ip(),
                'user_agent' => $datos['user_agent'] ?? request()?->userAgent(),
                'detail' => $datos['detail'] ?? null,
            ]);

            $id = DB::table('audit_logs')->insertGetId($fila);

            return RegistroAuditoria::findOrFail($id);
        });
    }

    /**
     * Registra un evento absorbiendo cualquier error.
     *
     * SOLO para eventos accesorios donde perder el registro es preferible a
     * romper el flujo. Nunca para operaciones destructivas.
     */
    public function registrarSeguro(array $datos): ?RegistroAuditoria
    {
        try {
            return $this->registrar($datos);
        } catch (Throwable $e) {
            Log::error('[AUDITORIA] No se pudo registrar el evento', [
                'action' => $datos['action'] ?? 'desconocida',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Hash del registro: SHA-256 sobre todos los campos significativos,
     * en orden fijo, con separador, encadenado al hash anterior.
     *
     * Los campos cifrados entran por su texto plano — si entrara el
     * ciphertext, el IV aleatorio haría imposible reverificar la cadena.
     */
    private function calcularHash(array $fila, array $planos): string
    {
        $partes = [
            $fila['company_id'] ?? '',
            $fila['company_sequence'],
            $fila['user_id'] ?? '',
            $fila['origin'],
            $fila['action'],
            $fila['entity_type'] ?? '',
            $fila['entity_id'] ?? '',
            $fila['affected_complaint_id'] ?? '',
            $fila['result'],
            $fila['ip_hash'] ?? '',
            $fila['user_agent_hash'] ?? '',
            $fila['request_id'] ?? '',
            $fila['session_id'] ?? '',
            $fila['correlation_id'] ?? '',
            $planos['detail'] ?? '',
            $fila['created_at']->format('Y-m-d H:i:s'),
            $fila['previous_hash'] ?? '',
        ];

        return hash('sha256', implode(self::SEPARADOR, $partes));
    }

    /**
     * Recalcula el hash de un registro ya guardado, para el comando de
     * verificación de integridad (Etapa 7).
     *
     * Necesita descifrar detail_enc, así que solo puede correr con la
     * clave disponible.
     */
    public function recalcularHash(RegistroAuditoria $registro): string
    {
        $detalle = $registro->detail_enc
            ? $this->cifrado->descifrar($registro->detail_enc)
            : null;

        return $this->calcularHash([
            'company_id' => $registro->company_id,
            'company_sequence' => $registro->company_sequence,
            'user_id' => $registro->user_id,
            'origin' => $registro->origin,
            'action' => $registro->action,
            'entity_type' => $registro->entity_type,
            'entity_id' => $registro->entity_id,
            'affected_complaint_id' => $registro->affected_complaint_id,
            'result' => $registro->result,
            'ip_hash' => $registro->ip_hash,
            'user_agent_hash' => $registro->user_agent_hash,
            'request_id' => $registro->request_id,
            'session_id' => $registro->session_id,
            'correlation_id' => $registro->correlation_id,
            'created_at' => $registro->created_at,
            'previous_hash' => $registro->previous_hash,
        ], ['detail' => $detalle]);
    }

    private function cifrar(?string $valor): ?string
    {
        return $valor === null || $valor === ''
            ? null
            : $this->cifrado->cifrar($valor);
    }

    /**
     * Session ID.
     *
     * Para actividad anónima se hashea, así no se puede correlacionar al
     * mismo denunciante a lo largo del tiempo (H-005). El original hacía
     * lo mismo pero escribía 69 caracteres en una columna char(36): el
     * INSERT fallaba y el catch se comía el error, así que ningún evento
     * anónimo llegó nunca a registrarse. La columna ahora es varchar(80).
     */
    private function resolverSessionId(array $datos): ?string
    {
        $id = session()->getId();

        if (! $id) {
            return null;
        }

        if (! empty($datos['es_anonimo'])) {
            return 'anon:' . substr(hash('sha256', $id . ($datos['company_id'] ?? '')), 0, 64);
        }

        return mb_substr($id, 0, 80);
    }

    private function validar(string $valor, array $permitidos, string $porDefecto): string
    {
        return in_array($valor, $permitidos, true) ? $valor : $porDefecto;
    }
}
