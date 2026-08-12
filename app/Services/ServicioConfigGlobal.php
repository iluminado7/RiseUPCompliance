<?php

namespace App\Services;

use App\Models\ConfigGlobal;
use App\Support\CatalogoConfigGlobal;
use App\Support\ClaveConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Lectura y escritura de global_config.
 *
 * Es el unico camino de acceso a la tabla: si el onboarding lee por un
 * lado y la pantalla escribe por otro, los defaults se separan sin que
 * nada lo note. El casteo por value_type vive aca y en ningun otro lado.
 */
class ServicioConfigGlobal
{
    private const CACHE_KEY = 'config_global.valores';

    public function __construct(
        private readonly ServicioAuditoria $auditoria,
    ) {}

    /**
     * Todas las claves declaradas con su valor efectivo (base o default).
     *
     * @return array<string, mixed>
     */
    public function valores(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $enBase = ConfigGlobal::pluck('value', 'key')->all();
            $valores = [];

            foreach (CatalogoConfigGlobal::definiciones() as $clave => $def) {
                $valores[$clave] = array_key_exists($clave, $enBase)
                    ? $this->castear($enBase[$clave], $def->tipo)
                    : $def->porDefecto;
            }

            return $valores;
        });
    }

    public function obtener(string $clave): mixed
    {
        $valores = $this->valores();

        if (array_key_exists($clave, $valores)) {
            return $valores[$clave];
        }

        return CatalogoConfigGlobal::definicion($clave)?->porDefecto;
    }

    /**
     * Metadatos por clave para la pantalla: version, quien la toco y cuando.
     */
    public function metadatos(): array
    {
        return ConfigGlobal::with('actualizadoPor')
            ->get()
            ->keyBy('key')
            ->all();
    }

    /**
     * Filas que existen en la base pero no estan declaradas en el catalogo.
     *
     * Se muestran en solo lectura. Editarlas sin saber su tipo ni quien las
     * consume seria escribir a ciegas.
     */
    public function noDeclaradas(): array
    {
        $declaradas = array_keys(CatalogoConfigGlobal::definiciones());

        return ConfigGlobal::whereNotIn('key', $declaradas)
            ->orderBy('key')
            ->get()
            ->all();
    }

    /**
     * Guarda los cambios recibidos del formulario.
     *
     * Cambiar un default global afecta a toda alta futura, asi que la
     * traza va con registrar() DENTRO de la transaccion: si no se puede
     * auditar, el cambio no ocurre (6.2 del brief).
     *
     * @param  array<string, mixed>  $entradas  clave real => valor nuevo
     * @return array<int, string>  claves efectivamente modificadas
     */
    public function actualizar(array $entradas): array
    {
        $managerId = auth()->user()->manager_id;

        if (! $managerId) {
            throw new RuntimeException(
                'Tu usuario no tiene un responsable interno vinculado. '
                . 'global_config solo la pueden modificar los tenant_managers.'
            );
        }

        $modificadas = DB::transaction(function () use ($entradas, $managerId) {
            $detalle = [];
            $claves = [];

            foreach ($entradas as $clave => $valorNuevo) {
                $def = CatalogoConfigGlobal::definicion($clave);

                // Clave no declarada: no se toca. El formulario no deberia
                // mandarla, pero el control va en el servidor.
                if (! $def) {
                    continue;
                }

                $fila = ConfigGlobal::where('key', $clave)->lockForUpdate()->first();

                if ($def->esSecreta) {
                    // Vacio = dejar como estaba.
                    if ($valorNuevo === null || $valorNuevo === '') {
                        continue;
                    }

                    // El cifrado de secretos todavia no esta cableado a
                    // ENCRYPTION_KEY. Antes de declarar una clave secreta hay
                    // que resolverlo: guardarla en claro seria peor que no
                    // tenerla.
                    throw new RuntimeException(
                        "La clave '{$clave}' esta declarada como secreta y el "
                        . 'cifrado de global_config no esta implementado todavia.'
                    );
                }

                $anterior = $fila
                    ? $this->castear($fila->value, $def->tipo)
                    : $def->porDefecto;

                $serializadoNuevo = $this->serializar($valorNuevo, $def->tipo);

                if ($serializadoNuevo === $this->serializar($anterior, $def->tipo)) {
                    continue;
                }

                if ($fila) {
                    $fila->value = $serializadoNuevo;
                    $fila->value_type = $def->tipo;
                    $fila->version = $fila->version + 1;
                    $fila->updated_by_manager_id = $managerId;
                    $fila->save();
                } else {
                    ConfigGlobal::create([
                        'key' => $clave,
                        'value' => $serializadoNuevo,
                        'value_type' => $def->tipo,
                        'is_secret' => false,
                        'version' => 1,
                        'description' => $def->descripcion,
                        'updated_by_manager_id' => $managerId,
                    ]);
                }

                $claves[] = $clave;
                $detalle[] = sprintf(
                    '%s: %s -> %s',
                    $clave,
                    $this->paraDetalle($anterior),
                    $this->paraDetalle($valorNuevo)
                );
            }

            if ($detalle) {
                $this->auditoria->registrar([
                    'company_id' => null,   // evento de plataforma: chain_scope 0
                    'user_id' => auth()->id(),
                    'origin' => 'web',
                    'action' => 'global_config.updated',
                    'entity_type' => 'global_config',
                    'entity_id' => null,
                    'result' => 'success',
                    'detail' => 'Configuracion global · ' . implode(' · ', $detalle),
                ]);
            }

            return $claves;
        });

        if ($modificadas) {
            Cache::forget(self::CACHE_KEY);
        }

        return $modificadas;
    }

    /** Fuerza la relectura. Util despues de un seed o un cambio a mano. */
    public function olvidarCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function castear(?string $valor, string $tipo): mixed
    {
        if ($valor === null) {
            return null;
        }

        return match ($tipo) {
            'integer' => (int) $valor,
            'decimal' => (float) $valor,
            'boolean' => filter_var($valor, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($valor, true),
            default => $valor,
        };
    }

    private function serializar(mixed $valor, string $tipo): string
    {
        return match ($tipo) {
            'boolean' => $valor ? '1' : '0',
            'json' => json_encode($valor),
            'integer' => (string) (int) $valor,
            'decimal' => (string) (float) $valor,
            default => (string) $valor,
        };
    }

    private function paraDetalle(mixed $valor): string
    {
        if (is_bool($valor)) {
            return $valor ? 'si' : 'no';
        }

        if (is_array($valor)) {
            return json_encode($valor);
        }

        return (string) $valor;
    }
}
