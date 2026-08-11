<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Depuracion de metadatos de los archivos adjuntos.
 *
 * -- POR QUE IMPORTA (H-004) --
 *
 * Una foto sacada con un telefono lleva EXIF: coordenadas GPS, modelo del
 * dispositivo, fecha exacta y a veces el nombre del propietario. Un
 * denunciante anonimo que adjunta una foto se desanonimiza solo, sin
 * enterarse.
 *
 * Las imagenes se reprocesan con GD: al decodificar y volver a codificar,
 * los metadatos no sobreviven. Es mas contundente que intentar borrarlos
 * campo por campo.
 *
 * -- PENDIENTE (H-016) --
 *
 * PDF y Word conservan autor, fechas y a veces historial de cambios.
 * Depurarlos requiere exiftool o Ghostscript instalados en el servidor,
 * que es la deuda listada en 7 del brief. Mientras tanto se registra que
 * el archivo NO fue depurado, para que quede constancia en clean_metadata
 * y el investigador sepa que ese adjunto puede identificar a quien lo
 * subio.
 */
class ServicioMetadatos
{
    private const IMAGENES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Depura el archivo y lo deja en $destino.
     *
     * @return array Resumen para guardar en files.clean_metadata.
     */
    public function depurar(UploadedFile $archivo, string $destino, string $mimeDetectado): array
    {
        if (in_array($mimeDetectado, self::IMAGENES, true)) {
            return $this->depurarImagen($archivo, $destino, $mimeDetectado);
        }

        // El archivo se guarda tal cual, pero queda registrado que
        // conserva sus metadatos.
        copy($archivo->getRealPath(), $destino);

        Log::info('[METADATOS] Archivo guardado sin depurar', [
            'mime' => $mimeDetectado,
            'motivo' => 'Formato no soportado por el depurador (requiere exiftool/Ghostscript)',
        ]);

        return [
            'depurado' => false,
            'motivo' => 'formato_no_soportado',
            'mime' => $mimeDetectado,
            'advertencia' => 'Este archivo conserva sus metadatos y podría identificar a quien lo subió.',
        ];
    }

    private function depurarImagen(UploadedFile $archivo, string $destino, string $mime): array
    {
        if (! extension_loaded('gd')) {
            copy($archivo->getRealPath(), $destino);

            Log::warning('[METADATOS] GD no está disponible: la imagen conserva su EXIF');

            return [
                'depurado' => false,
                'motivo' => 'gd_no_disponible',
                'mime' => $mime,
                'advertencia' => 'Esta imagen conserva sus metadatos.',
            ];
        }

        try {
            $origen = $archivo->getRealPath();

            $imagen = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($origen),
                'image/png' => imagecreatefrompng($origen),
                'image/webp' => imagecreatefromwebp($origen),
                default => false,
            };

            if ($imagen === false) {
                throw new \RuntimeException('GD no pudo decodificar la imagen.');
            }

            // Los datos que se descartan se anotan ANTES de reprocesar,
            // para que el investigador sepa qué se eliminó. No se guarda
            // el valor: eso sería conservar justamente lo que se quiso
            // borrar.
            $encontrados = $this->metadatosPresentes($origen, $mime);

            // PNG y WebP conservan transparencia; sin esto queda fondo negro.
            if ($mime !== 'image/jpeg') {
                imagepalettetotruecolor($imagen);
                imagealphablending($imagen, false);
                imagesavealpha($imagen, true);
            }

            $ok = match ($mime) {
                'image/jpeg' => imagejpeg($imagen, $destino, 90),
                'image/png' => imagepng($imagen, $destino, 8),
                'image/webp' => imagewebp($imagen, $destino, 90),
                default => false,
            };

            imagedestroy($imagen);

            if (! $ok) {
                throw new \RuntimeException('GD no pudo escribir la imagen depurada.');
            }

            return [
                'depurado' => true,
                'metodo' => 'gd_reprocesado',
                'mime' => $mime,
                'campos_eliminados' => $encontrados,
            ];
        } catch (Throwable $e) {
            // Si la depuración falla, el archivo NO se guarda: es
            // preferible perder el adjunto a guardarlo con coordenadas
            // GPS de quien denunció de forma anónima.
            Log::error('[METADATOS] Falló la depuración de la imagen', [
                'mime' => $mime,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'No se pudo procesar la imagen. Probá con otro archivo.'
            );
        }
    }

    /** @return array<string> nombres de los grupos de metadatos hallados */
    private function metadatosPresentes(string $ruta, string $mime): array
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return [];
        }

        $exif = @exif_read_data($ruta);

        if (! $exif) {
            return [];
        }

        $grupos = [];

        if (isset($exif['GPSLatitude']) || isset($exif['GPSLongitude'])) {
            $grupos[] = 'ubicacion_gps';
        }

        if (isset($exif['Make']) || isset($exif['Model'])) {
            $grupos[] = 'dispositivo';
        }

        if (isset($exif['DateTimeOriginal'])) {
            $grupos[] = 'fecha_captura';
        }

        if (isset($exif['Artist']) || isset($exif['Copyright'])) {
            $grupos[] = 'autor';
        }

        return $grupos;
    }
}
