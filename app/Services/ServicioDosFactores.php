<?php

namespace App\Services;

use App\Models\Usuario;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/**
 * Segundo factor TOTP.
 *
 * ── ESTO NO ES UN PORT: ES CONSTRUCCIÓN NUEVA ──
 *
 * El sistema original tenía las columnas (two_factor_enabled,
 * two_factor_secret_enc), la dependencia instalada y la UI diciendo
 * "próximamente", pero el login NUNCA validó un TOTP. Es el hallazgo
 * H-023 de la auditoría, y por eso §6.7 del brief ("portar sin degradar")
 * no aplica: no había nada que portar.
 *
 * H-027: el QR se genera LOCALMENTE con bacon/bacon-qr-code. El proveedor
 * por defecto de la librería original mandaba el secreto a
 * api.qrserver.com — es decir, entregaba el segundo factor a un tercero
 * para que dibujara una imagen.
 *
 * El secreto se guarda cifrado con la clave maestra (sin salt: no
 * pertenece a ninguna denuncia y no se purga por retención).
 *
 * Requiere en composer.json:
 *   "pragmarx/google2fa": "^8.0"
 *   "bacon/bacon-qr-code": "^3.0"
 */
class ServicioDosFactores
{
    private const VENTANA_TOLERANCIA = 1; // ±30 s, para relojes desfasados

    public function __construct(
        private readonly Google2FA $google2fa,
        private readonly ServicioCifrado $cifrado,
    ) {}

    /** Secreto nuevo, todavía sin guardar. */
    public function generarSecreto(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /** Guarda el secreto cifrado. No activa el 2FA todavía. */
    public function guardarSecreto(Usuario $usuario, string $secreto): void
    {
        $usuario->two_factor_secret_enc = $this->cifrado->cifrar(
            $secreto,
            null,
            $this->contexto($usuario)
        );
        $usuario->save();
    }

    /**
     * Activa el 2FA. Solo tras verificar un código válido: si se activara
     * antes, un secreto mal copiado dejaría al usuario afuera.
     */
    public function activar(Usuario $usuario): void
    {
        $usuario->two_factor_enabled = true;
        $usuario->save();
    }

    public function desactivar(Usuario $usuario): void
    {
        $usuario->two_factor_enabled = false;
        $usuario->two_factor_secret_enc = null;
        $usuario->save();
    }

    public function secretoDe(Usuario $usuario): ?string
    {
        if (! $usuario->two_factor_secret_enc) {
            return null;
        }

        return $this->cifrado->descifrar(
            $usuario->two_factor_secret_enc,
            null,
            $this->contexto($usuario)
        );
    }

    public function verificar(Usuario $usuario, string $codigo): bool
    {
        $secreto = $this->secretoDe($usuario);

        if (! $secreto) {
            return false;
        }

        return $this->verificarConSecreto($secreto, $codigo);
    }

    /** Para el alta, cuando el secreto todavía no se guardó. */
    public function verificarConSecreto(string $secreto, string $codigo): bool
    {
        $codigo = preg_replace('/\s+/', '', $codigo);

        return $this->google2fa->verifyKey($secreto, $codigo, self::VENTANA_TOLERANCIA);
    }

    /** QR en SVG, generado en el servidor. El secreto no sale de acá. */
    public function qrSvg(Usuario $usuario, string $secreto): string
    {
        $url = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $usuario->email,
            $secreto
        );

        $escritor = new Writer(
            new ImageRenderer(
                new RendererStyle(240),
                new SvgImageBackEnd
            )
        );

        return $escritor->writeString($url);
    }

    /**
     * AAD: ata el secreto cifrado a este usuario. Si alguien copiara el
     * valor de two_factor_secret_enc a otra fila, el descifrado falla.
     */
    private function contexto(Usuario $usuario): string
    {
        return 'users.two_factor_secret_enc:' . $usuario->id;
    }
}
