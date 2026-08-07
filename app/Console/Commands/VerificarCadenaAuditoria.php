<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\RegistroAuditoria;
use App\Services\ServicioAuditoria;
use Illuminate\Console\Command;

/**
 * Verifica la integridad de las cadenas de hash de audit_logs.
 *
 *     php artisan auditoria:verificar
 *     php artisan auditoria:verificar --empresa=3
 *     php artisan auditoria:verificar --plataforma
 *
 * Es el verificable de 6.2 del brief.
 *
 * -- QUE COMPRUEBA --
 *
 * 1. Que cada registro recalcule al mismo log_hash que tiene guardado.
 *    Como el hash cubre TODOS los campos significativos (autor, denuncia
 *    afectada, detalle, fecha), alterar cualquiera de ellos lo rompe.
 *    El algoritmo original hasheaba 5 de 20 campos, asi que se podia
 *    cambiar quien hizo que sobre cual denuncia sin que nada lo notara.
 *
 * 2. Que el previous_hash de cada registro coincida con el log_hash del
 *    anterior. Esto detecta la eliminacion de un registro intermedio,
 *    que la comprobacion individual no ve.
 *
 * 3. Que la numeracion no tenga saltos. Un hueco en company_sequence
 *    significa que alguien borro el ultimo registro de un tramo.
 *
 * -- N+1 CADENAS --
 *
 * Hay una cadena por empresa mas la de plataforma (chain_scope = 0), que
 * agrupa los eventos sin empresa: login fallidos y actividad del
 * superadmin. El verificador del sistema anterior filtraba por company_id
 * y no podia recorrer esa cadena.
 *
 * -- REQUIERE LA CLAVE DE CIFRADO --
 *
 * El detalle entra al hash como texto plano (un ciphertext AES-GCM cambia
 * en cada escritura por el IV aleatorio, y haria irreproducible la
 * verificacion). Por eso este comando necesita ENCRYPTION_KEY: no se
 * puede auditar la cadena con solo un dump de la base.
 */
class VerificarCadenaAuditoria extends Command
{
    protected $signature = 'auditoria:verificar
                            {--empresa= : Verificar solo esta empresa (id)}
                            {--plataforma : Verificar solo la cadena de plataforma}
                            {--detalle : Mostrar cada ruptura encontrada}';

    protected $description = 'Verifica la integridad de la cadena de hashes de audit_logs';

    public function handle(ServicioAuditoria $auditoria): int
    {
        $cadenas = $this->cadenasAVerificar();

        if (empty($cadenas)) {
            $this->warn('No hay cadenas para verificar.');

            return self::SUCCESS;
        }

        $totalRupturas = 0;
        $totalRegistros = 0;
        $filas = [];

        foreach ($cadenas as $alcance => $etiqueta) {
            [$registros, $rupturas] = $this->verificarCadena($auditoria, (int) $alcance);

            $totalRegistros += $registros;
            $totalRupturas += count($rupturas);

            $filas[] = [
                $etiqueta,
                $registros,
                count($rupturas) === 0 ? 'OK' : count($rupturas) . ' ruptura(s)',
            ];

            if ($rupturas && $this->option('detalle')) {
                $this->newLine();
                $this->error("Rupturas en {$etiqueta}:");
                foreach ($rupturas as $ruptura) {
                    $this->line('  · ' . $ruptura);
                }
            }
        }

        $this->newLine();
        $this->table(['Cadena', 'Registros', 'Estado'], $filas);

        if ($totalRupturas === 0) {
            $this->info("Integridad verificada: {$totalRegistros} registros, sin rupturas.");

            return self::SUCCESS;
        }

        $this->error("Se detectaron {$totalRupturas} ruptura(s) en {$totalRegistros} registros.");

        if (! $this->option('detalle')) {
            $this->line('Volvé a correr con --detalle para ver cuáles.');
        }

        return self::FAILURE;
    }

    /** @return array<int,string> alcance => etiqueta */
    private function cadenasAVerificar(): array
    {
        if ($this->option('plataforma')) {
            return [0 => 'Plataforma'];
        }

        if ($empresaId = $this->option('empresa')) {
            $empresa = Empresa::find($empresaId);

            if (! $empresa) {
                $this->error("No existe la empresa {$empresaId}.");

                return [];
            }

            return [$empresa->id => $empresa->name];
        }

        // Todas: la de plataforma mas una por empresa con registros.
        $cadenas = [0 => 'Plataforma'];

        $conRegistros = RegistroAuditoria::query()
            ->whereNotNull('company_id')
            ->distinct()
            ->pluck('company_id');

        foreach (Empresa::whereIn('id', $conRegistros)->orderBy('name')->get() as $empresa) {
            $cadenas[$empresa->id] = $empresa->name;
        }

        return $cadenas;
    }

    /** @return array{0:int,1:array<string>} */
    private function verificarCadena(ServicioAuditoria $auditoria, int $alcance): array
    {
        $rupturas = [];
        $cantidad = 0;
        $hashAnterior = null;
        $secuenciaEsperada = 1;

        RegistroAuditoria::where('chain_scope', $alcance)
            ->orderBy('company_sequence')
            ->chunk(500, function ($lote) use (
                $auditoria, &$rupturas, &$cantidad, &$hashAnterior, &$secuenciaEsperada
            ) {
                foreach ($lote as $registro) {
                    $cantidad++;

                    // 1. Saltos en la numeracion.
                    if ((int) $registro->company_sequence !== $secuenciaEsperada) {
                        $rupturas[] = sprintf(
                            'Salto de numeración: se esperaba #%d y vino #%d (id %d).',
                            $secuenciaEsperada,
                            $registro->company_sequence,
                            $registro->id
                        );
                        $secuenciaEsperada = (int) $registro->company_sequence;
                    }

                    // 2. Encadenamiento.
                    if ($registro->previous_hash !== $hashAnterior) {
                        $rupturas[] = sprintf(
                            'Cadena cortada en #%d (id %d): previous_hash no coincide con el registro anterior.',
                            $registro->company_sequence,
                            $registro->id
                        );
                    }

                    // 3. Hash propio.
                    $recalculado = $auditoria->recalcularHash($registro);

                    if (! hash_equals($registro->log_hash, $recalculado)) {
                        $rupturas[] = sprintf(
                            'Hash alterado en #%d (id %d, acción "%s"): el contenido no coincide con su hash.',
                            $registro->company_sequence,
                            $registro->id,
                            $registro->action
                        );
                    }

                    $hashAnterior = $registro->log_hash;
                    $secuenciaEsperada++;
                }
            });

        return [$cantidad, $rupturas];
    }
}
