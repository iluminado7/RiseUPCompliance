<?php

namespace App\Services;

use RuntimeException;

/**
 * Cifrado simétrico AES-256-GCM de datos personales.
 *
 * Portado de backend/EncryptionService.php, con tres correcciones:
 *
 * 1. DERIVACIÓN POR DENUNCIA.
 *    El original tenía una sola clave activa global, así que el
 *    encryption_key_id que se guardaba en cada fila era siempre el mismo.
 *    Destruirlo habría borrado TODAS las denuncias, no una — es decir, el
 *    crypto-shredding que el schema promete era imposible.
 *
 *    Acá la clave de cada denuncia se deriva con HKDF de la clave maestra
 *    más el salt de esa denuncia. Destruir el salt (encryption_salt = NULL)
 *    hace irrecuperable esa denuncia y solo esa.
 *
 * 2. SIN FALLBACK SILENCIOSO.
 *    resolveKey() del original, cuando no encontraba la clave de un
 *    key_id, caía a la clave activa y seguía. Eso enmascara una
 *    configuración rota y produce un error de descifrado engañoso más
 *    adelante. Acá falla en el acto.
 *
 * 3. AAD (datos autenticados adicionales).
 *    El original no lo usaba, así que un ciphertext era portable entre
 *    filas: con acceso de escritura se podía mover el nombre cifrado de un
 *    denunciante a otra denuncia sin que nada lo detectara. El AAD ata el
 *    ciphertext a su contexto.
 */
class ServicioCifrado
{
    private const CIFRADO = 'aes-256-gcm';
    private const LARGO_IV = 12;
    private const LARGO_TAG = 16;

    private string $claveMaestra;
    private string $claveActivaId;

    public function __construct(?string $claveHex = null, ?string $claveId = null)
    {
        $hex = $claveHex ?? config('cifrado.clave_maestra');

        if (empty($hex)) {
            throw new RuntimeException('ENCRYPTION_KEY no está definida en el entorno.');
        }

        $binaria = @hex2bin($hex);

        if ($binaria === false || strlen($binaria) !== 32) {
            throw new RuntimeException('ENCRYPTION_KEY debe ser un hex de 64 caracteres (32 bytes).');
        }

        $this->claveMaestra = $binaria;
        $this->claveActivaId = $claveId ?? config('cifrado.clave_activa_id', 'key-v1');
    }

    public function claveActivaId(): string
    {
        return $this->claveActivaId;
    }

    /** Salt nuevo para una denuncia. Se guarda en complaints.encryption_salt. */
    public function generarSalt(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Cifra un valor.
     *
     * @param  string|null  $salt  Salt de la denuncia. Null usa la clave
     *                             maestra directa (config, 2FA, IPs: cosas
     *                             que no se purgan por denuncia).
     * @param  string  $aad  Contexto que ata el ciphertext a su lugar,
     *                       ej: 'complaint_reporters.email_enc:42'.
     */
    public function cifrar(?string $plano, ?string $salt = null, string $aad = ''): ?string
    {
        if ($plano === null || $plano === '') {
            return null;
        }

        $clave = $this->derivarClave($salt, $this->claveMaestra);
        $iv = random_bytes(self::LARGO_IV);
        $tag = '';

        $cifrado = openssl_encrypt(
            $plano,
            self::CIFRADO,
            $clave,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad,
            self::LARGO_TAG
        );

        if ($cifrado === false) {
            throw new RuntimeException('Falló el cifrado: ' . openssl_error_string());
        }

        return base64_encode($iv . $tag . $cifrado);
    }

    /**
     * Descifra un valor.
     *
     * @param  string|null  $claveId  ID de la clave con que se cifró. Si no
     *                                es la activa, se busca entre las
     *                                retiradas — y si no está, falla.
     */
    public function descifrar(?string $codificado, ?string $salt = null, string $aad = '', ?string $claveId = null): ?string
    {
        if ($codificado === null || $codificado === '') {
            return null;
        }

        $crudo = base64_decode($codificado, true);

        if ($crudo === false) {
            throw new RuntimeException('El valor cifrado no es base64 válido.');
        }

        if (strlen($crudo) < self::LARGO_IV + self::LARGO_TAG + 1) {
            throw new RuntimeException('El valor cifrado es demasiado corto para ser válido.');
        }

        $iv = substr($crudo, 0, self::LARGO_IV);
        $tag = substr($crudo, self::LARGO_IV, self::LARGO_TAG);
        $cifrado = substr($crudo, self::LARGO_IV + self::LARGO_TAG);

        $maestra = $this->resolverClaveMaestra($claveId);
        $clave = $this->derivarClave($salt, $maestra);

        $plano = openssl_decrypt(
            $cifrado,
            self::CIFRADO,
            $clave,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if ($plano === false) {
            throw new RuntimeException(
                'Falló el descifrado. Dato alterado, salt incorrecto o contexto (AAD) que no corresponde.'
            );
        }

        return $plano;
    }

    /**
     * Deriva la clave de una denuncia con HKDF.
     *
     * Sin salt devuelve la maestra: es el caso de los datos que no se
     * purgan por denuncia.
     */
    private function derivarClave(?string $salt, string $maestra): string
    {
        if ($salt === null || $salt === '') {
            return $maestra;
        }

        return hash_hkdf('sha256', $maestra, 32, 'riseup-denuncia', $salt);
    }

    private function resolverClaveMaestra(?string $claveId): string
    {
        if ($claveId === null || $claveId === $this->claveActivaId) {
            return $this->claveMaestra;
        }

        $hex = config("cifrado.claves_anteriores.{$claveId}");

        if (empty($hex)) {
            throw new RuntimeException(
                "No hay clave configurada para '{$claveId}'. Definir la variable de entorno correspondiente."
            );
        }

        $binaria = @hex2bin($hex);

        if ($binaria === false || strlen($binaria) !== 32) {
            throw new RuntimeException("La clave '{$claveId}' no es un hex de 64 caracteres.");
        }

        return $binaria;
    }
}
