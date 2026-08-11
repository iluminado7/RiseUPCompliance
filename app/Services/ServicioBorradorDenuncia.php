<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Borrador de una denuncia mientras se completa el formulario.
 *
 * Vive en la sesion, no en la base: hasta que la persona confirma el
 * envio, no hay denuncia. Guardar un borrador en la base significaria
 * conservar datos de alguien que quiza decidio no denunciar.
 *
 * La clave incluye el slug: alguien podria tener abiertos los canales de
 * dos empresas distintas.
 */
class ServicioBorradorDenuncia
{
    public const PASOS = 7;

    private const CARPETA_TEMPORAL = 'borradores';

    public function clave(Empresa $empresa): string
    {
        return 'denuncia.' . $empresa->slug;
    }

    public function datos(Empresa $empresa): array
    {
        return session()->get($this->clave($empresa), []);
    }

    public function guardarPaso(Empresa $empresa, int $paso, array $datos): void
    {
        $borrador = $this->datos($empresa);
        $borrador['pasos'][$paso] = $datos;
        $borrador['ultimo_paso'] = max($paso, $borrador['ultimo_paso'] ?? 1);

        session()->put($this->clave($empresa), $borrador);
    }

    /** Datos de un paso ya completado, para repoblar el formulario. */
    public function paso(Empresa $empresa, int $paso): array
    {
        return $this->datos($empresa)['pasos'][$paso] ?? [];
    }

    /** Todos los pasos aplanados en un solo array. */
    public function completo(Empresa $empresa): array
    {
        $borrador = $this->datos($empresa);
        $completo = [];

        foreach ($borrador['pasos'] ?? [] as $datos) {
            $completo = array_merge($completo, $datos);
        }

        return $completo;
    }

    /**
     * Paso al que se puede acceder.
     *
     * No se deja saltar adelante: entrar al paso 5 sin haber completado el
     * 3 dejaria el borrador incompleto y la validacion final fallaria sin
     * que quede claro por que.
     */
    public function pasoPermitido(Empresa $empresa, int $solicitado): int
    {
        $maximo = ($this->datos($empresa)['ultimo_paso'] ?? 0) + 1;

        return max(1, min($solicitado, min($maximo, self::PASOS)));
    }

    /**
     * Guarda el adjunto en una carpeta temporal.
     *
     * Un UploadedFile no se puede serializar en la sesion, asi que se
     * mueve a disco y se guarda la ruta. El archivo temporal queda fuera
     * del alcance publico.
     */
    public function guardarAdjunto(Empresa $empresa, UploadedFile $archivo): void
    {
        $this->borrarAdjunto($empresa);

        $nombre = bin2hex(random_bytes(16)) . '.tmp';
        $ruta = self::CARPETA_TEMPORAL . '/' . $nombre;

        Storage::disk('local')->putFileAs(self::CARPETA_TEMPORAL, $archivo, $nombre);

        $borrador = $this->datos($empresa);
        $borrador['adjunto'] = [
            'ruta' => $ruta,
            'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 500),
            'mime_declarado' => mb_substr((string) $archivo->getClientMimeType(), 0, 100),
            'tamanio' => $archivo->getSize(),
        ];

        session()->put($this->clave($empresa), $borrador);
    }

    public function adjunto(Empresa $empresa): ?array
    {
        return $this->datos($empresa)['adjunto'] ?? null;
    }

    public function borrarAdjunto(Empresa $empresa): void
    {
        $adjunto = $this->adjunto($empresa);

        if ($adjunto) {
            try {
                Storage::disk('local')->delete($adjunto['ruta']);
            } catch (Throwable $e) {
                Log::warning('[BORRADOR] No se pudo borrar el adjunto temporal', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $borrador = $this->datos($empresa);
        unset($borrador['adjunto']);
        session()->put($this->clave($empresa), $borrador);
    }

    /**
     * Descarta el borrador entero.
     *
     * Se llama al enviar y al abandonar. Es lo que garantiza que los datos
     * de alguien que empezo a denunciar y se arrepintio no queden dando
     * vueltas en la sesion.
     */
    public function descartar(Empresa $empresa): void
    {
        $this->borrarAdjunto($empresa);
        session()->forget($this->clave($empresa));
    }
}
