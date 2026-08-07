<?php

namespace Tests\Feature;

use App\Models\RegistroAuditoria;
use App\Services\ServicioAuditoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integridad de la cadena de hashes.
 *
 * El algoritmo original hasheaba 5 de 20 campos, así que alterar el autor
 * o la denuncia afectada de un registro no rompía nada. Estos tests
 * fijan que ahora sí rompa.
 */
class CadenaAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function servicio(): ServicioAuditoria
    {
        return app(ServicioAuditoria::class);
    }

    public function test_los_registros_se_encadenan(): void
    {
        $primero = $this->servicio()->registrar([
            'action' => 'test.uno',
            'result' => 'success',
        ]);

        $segundo = $this->servicio()->registrar([
            'action' => 'test.dos',
            'result' => 'success',
        ]);

        $this->assertNull($primero->previous_hash);
        $this->assertSame($primero->log_hash, $segundo->previous_hash);
        $this->assertSame($primero->company_sequence + 1, $segundo->company_sequence);
    }

    public function test_eventos_de_plataforma_y_de_empresa_son_cadenas_separadas(): void
    {
        $empresaId = DB::table('companies')->value('id');

        $plataforma = $this->servicio()->registrar(['action' => 'test.plataforma']);
        $empresa = $this->servicio()->registrar([
            'action' => 'test.empresa',
            'company_id' => $empresaId,
        ]);

        $this->assertSame(0, (int) $plataforma->chain_scope);
        $this->assertSame((int) $empresaId, (int) $empresa->chain_scope);
        $this->assertSame(1, $empresa->company_sequence, 'Cada cadena numera desde 1.');
    }

    public function test_alterar_el_autor_rompe_el_hash(): void
    {
        $registro = $this->servicio()->registrar([
            'action' => 'test.alteracion',
            'user_id' => 1,
            'result' => 'success',
        ]);

        $this->assertSame(
            $registro->log_hash,
            $this->servicio()->recalcularHash($registro),
            'Un registro intacto debe recalcular al mismo hash.'
        );

        // Se altera por fuera del modelo: RegistroAuditoria::update() lanza.
        DB::table('audit_logs')->where('id', $registro->id)->update(['user_id' => 999]);

        $alterado = RegistroAuditoria::find($registro->id);

        $this->assertNotSame(
            $alterado->log_hash,
            $this->servicio()->recalcularHash($alterado),
            'Cambiar el autor debe romper la verificación. Con el algoritmo original no la rompía.'
        );
    }

    public function test_el_modelo_impide_modificar_un_registro(): void
    {
        $registro = $this->servicio()->registrar(['action' => 'test.append_only']);

        $this->expectException(\LogicException::class);

        $registro->update(['action' => 'otra']);
    }
}
