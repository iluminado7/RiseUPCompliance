<?php

namespace App\Services;

use RuntimeException;

/**
 * Hash de IPs y user agents para los registros de auditoría.
 *
 * HMAC-SHA256, no SHA-256 pelado: el hash sirve igual para agrupar y
 * comparar, pero deja de ser invertible por quien tenga el dump.
 */
class HashIp
{
    private string $clave;

    public function __construct(?string $clave = null)
    {
        $clave = $clave ?? config('cifrado.clave_hash_ip');

        if (empty($clave)) {
            throw new RuntimeException(
                'IP_HASH_KEY no está definida. Sin ella los hashes de IP serían reversibles.'
            );
        }

        $this->clave = $clave;
    }

    public function hash(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return hash_hmac('sha256', $valor, $this->clave);
    }

    /** Compara en tiempo constante. */
    public function coincide(?string $valor, ?string $hash): bool
    {
        if ($valor === null || $hash === null) {
            return false;
        }

        return hash_equals($hash, $this->hash($valor));
    }
}
