<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Borra los adjuntos de borradores abandonados.
 *
 *     php artisan canal:limpiar-borradores
 *     php artisan canal:limpiar-borradores --horas=48
 *
 * Cuando alguien sube un archivo en el formulario y despues abandona, el
 * temporal queda en storage/app/borradores sin ninguna referencia: la
 * sesion que lo apuntaba expiro.
 *
 * Conviene programarlo a diario. Son archivos de hasta 25 MB que pueden
 * contener evidencia de una denuncia que nunca se envio, asi que no es
 * solo una cuestion de espacio.
 */
class LimpiarBorradores extends Command
{
    protected $signature = 'canal:limpiar-borradores
                            {--horas=24 : Antigüedad mínima para borrar}';

    protected $description = 'Borra los adjuntos de borradores de denuncia abandonados';

    public function handle(): int
    {
        $horas = (int) $this->option('horas');
        $limite = now()->subHours($horas)->timestamp;

        $disco = Storage::disk('local');

        if (! $disco->exists('borradores')) {
            $this->info('No hay borradores.');

            return self::SUCCESS;
        }

        $borrados = 0;
        $liberado = 0;

        foreach ($disco->files('borradores') as $archivo) {
            if ($disco->lastModified($archivo) >= $limite) {
                continue;
            }

            $liberado += $disco->size($archivo);
            $disco->delete($archivo);
            $borrados++;
        }

        $this->info(sprintf(
            '%d archivo(s) borrado(s), %s liberados.',
            $borrados,
            $this->formatearTamanio($liberado)
        ));

        return self::SUCCESS;
    }

    private function formatearTamanio(int $bytes): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($unidades) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $unidades[$i];
    }
}
