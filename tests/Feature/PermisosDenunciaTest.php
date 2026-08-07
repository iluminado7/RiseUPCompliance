<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Denuncia;
use App\Models\Scopes\TenantScope;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Permisos por rol dentro de la misma empresa.
 *
 * Todas las reglas están portadas de ver_denuncia.php. Varias son
 * contraintuitivas y por eso conviene que estén fijadas por tests: si
 * alguien "corrige" el comportamiento sin querer, un test se rompe.
 */
class PermisosDenunciaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $email): Usuario
    {
        return Usuario::where('email', $email)->firstOrFail();
    }

    private function denunciaDe(string $slug): Denuncia
    {
        return Denuncia::withoutGlobalScope(TenantScope::class)
            ->whereHas('empresa', fn ($q) => $q->where('slug', $slug))
            ->firstOrFail();
    }

    private function asignar(Denuncia $denuncia, Usuario $usuario): Denuncia
    {
        Asignacion::create([
            'company_id' => $denuncia->company_id,
            'complaint_id' => $denuncia->id,
            'assigned_to_user_id' => $usuario->id,
        ]);

        return $denuncia->fresh();
    }

    // ── Cambio de estado ────────────────────────────────────────

    public function test_gestor_no_puede_cambiar_estado_de_denuncia_no_asignada(): void
    {
        $gestor = $this->usuario('gestor@empresa-a.test');

        $this->assertFalse(
            $gestor->can('cambiarEstado', $this->denunciaDe('empresa-a')),
            'H-011: el gestor solo debe operar sus denuncias asignadas.'
        );
    }

    public function test_gestor_puede_cambiar_estado_de_denuncia_asignada(): void
    {
        $gestor = $this->usuario('gestor@empresa-a.test');
        $denuncia = $this->asignar($this->denunciaDe('empresa-a'), $gestor);

        $this->assertTrue($gestor->can('cambiarEstado', $denuncia));
    }

    /**
     * Contraintuitivo pero es el comportamiento actual: el manual lo
     * describe como "solo lectura", y sin embargo puede cambiar estados
     * sobre lo que tiene asignado.
     */
    public function test_investigador_externo_asignado_puede_cambiar_estado(): void
    {
        $investigador = $this->usuario('investigador@empresa-a.test');
        $denuncia = $this->asignar($this->denunciaDe('empresa-a'), $investigador);

        $this->assertTrue($investigador->can('cambiarEstado', $denuncia));
    }

    public function test_admin_principal_puede_sobre_cualquier_denuncia_de_su_empresa(): void
    {
        $admin = $this->usuario('admin@empresa-a.test');

        $this->assertTrue($admin->can('cambiarEstado', $this->denunciaDe('empresa-a')));
    }

    public function test_admin_principal_no_puede_sobre_otra_empresa(): void
    {
        $admin = $this->usuario('admin@empresa-a.test');

        $this->assertFalse($admin->can('cambiarEstado', $this->denunciaDe('empresa-b')));
    }

    // ── Asignación ──────────────────────────────────────────────

    public function test_solo_admin_y_superadmin_asignan(): void
    {
        $denuncia = $this->denunciaDe('empresa-a');

        $this->assertTrue($this->usuario('super@goharvey.test')->can('asignar', $denuncia));
        $this->assertTrue($this->usuario('admin@empresa-a.test')->can('asignar', $denuncia));
        $this->assertFalse($this->usuario('gestor@empresa-a.test')->can('asignar', $denuncia));
    }

    // ── Notas internas ──────────────────────────────────────────

    /**
     * Solo superadmin e investigador externo. El admin_principal NO ve las
     * notas de su propia empresa: contraintuitivo, pero es lo que hace el
     * sistema actual.
     */
    public function test_notas_internas_solo_para_superadmin_e_investigador(): void
    {
        $denuncia = $this->denunciaDe('empresa-a');
        $investigador = $this->usuario('investigador@empresa-a.test');
        $denunciaAsignada = $this->asignar($denuncia, $investigador);

        $this->assertTrue($this->usuario('super@goharvey.test')->can('verNotas', $denuncia));
        $this->assertTrue($investigador->can('verNotas', $denunciaAsignada));

        $this->assertFalse($this->usuario('admin@empresa-a.test')->can('verNotas', $denuncia));
        $this->assertFalse($this->usuario('gestor@empresa-a.test')->can('verNotas', $denuncia));
    }

    // ── Chat ────────────────────────────────────────────────────

    public function test_chat_solo_para_investigador_externo_asignado(): void
    {
        $denuncia = Denuncia::withoutGlobalScope(TenantScope::class)
            ->whereHas('empresa', fn ($q) => $q->where('slug', 'empresa-a'))
            ->where('is_anonymous', false)
            ->firstOrFail();

        $investigador = $this->usuario('investigador@empresa-a.test');
        $asignada = $this->asignar($denuncia, $investigador);

        $this->assertTrue($investigador->can('chatear', $asignada));

        $this->assertFalse($this->usuario('super@goharvey.test')->can('chatear', $asignada));
        $this->assertFalse($this->usuario('admin@empresa-a.test')->can('chatear', $asignada));
        $this->assertFalse($this->usuario('gestor@empresa-a.test')->can('chatear', $asignada));
    }

    public function test_no_hay_chat_en_denuncias_anonimas(): void
    {
        $anonima = Denuncia::withoutGlobalScope(TenantScope::class)
            ->whereHas('empresa', fn ($q) => $q->where('slug', 'empresa-a'))
            ->where('is_anonymous', true)
            ->firstOrFail();

        $investigador = $this->usuario('investigador@empresa-a.test');
        $asignada = $this->asignar($anonima, $investigador);

        $this->assertTrue($investigador->can('verChat', $asignada));
        $this->assertFalse(
            $investigador->can('chatear', $asignada),
            'Sin datos de contacto no hay con quién chatear.'
        );
    }

    public function test_investigador_no_asignado_no_ve_el_chat(): void
    {
        $investigador = $this->usuario('investigador@empresa-a.test');

        $this->assertFalse($investigador->can('verChat', $this->denunciaDe('empresa-a')));
    }
}
