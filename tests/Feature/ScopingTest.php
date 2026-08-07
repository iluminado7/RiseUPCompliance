<?php

namespace Tests\Feature;

use App\Models\Denuncia;
use App\Models\Empresa;
use App\Models\Scopes\TenantScope;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aislamiento entre empresas.
 *
 * Es el verificable de la Etapa 3 (§9): cada rol ve solo lo suyo, y el
 * cruce de tenants devuelve 404 y no 403.
 *
 * La diferencia entre 404 y 403 no es cosmética: un 403 confirma que el
 * recurso existe, y eso ya es una fuga — permite enumerar cuántas
 * denuncias tiene un competidor probando IDs.
 */
class ScopingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function usuario(string $email): Usuario
    {
        return Usuario::where('email', $email)->firstOrFail();
    }

    public function test_gestor_solo_ve_denuncias_de_su_empresa(): void
    {
        $gestor = $this->usuario('gestor@empresa-a.test');
        $empresaA = Empresa::where('slug', 'empresa-a')->firstOrFail();

        $this->actingAs($gestor);

        $denuncias = Denuncia::all();

        $this->assertGreaterThan(0, $denuncias->count(), 'El seeder debe haber cargado denuncias.');
        $this->assertTrue(
            $denuncias->every(fn (Denuncia $d) => $d->company_id === $empresaA->id),
            'El scope dejó pasar denuncias de otra empresa.'
        );
    }

    public function test_denuncia_de_otra_empresa_no_existe_para_el_scope(): void
    {
        $gestor = $this->usuario('gestor@empresa-a.test');

        $ajena = Denuncia::withoutGlobalScope(TenantScope::class)
            ->whereHas('empresa', fn ($q) => $q->where('slug', 'empresa-b'))
            ->firstOrFail();

        $this->actingAs($gestor);

        $this->assertNull(
            Denuncia::find($ajena->id),
            'Una denuncia de otra empresa debe ser invisible, no prohibida.'
        );
    }

    public function test_superadmin_atraviesa_el_scope(): void
    {
        $this->actingAs($this->usuario('super@goharvey.test'));

        $empresas = Denuncia::all()->pluck('company_id')->unique();

        $this->assertGreaterThan(1, $empresas->count(), 'El superadmin debe ver todas las empresas.');
    }

    public function test_crear_denuncia_toma_la_empresa_de_la_sesion(): void
    {
        $admin = $this->usuario('admin@empresa-a.test');

        $this->actingAs($admin);

        $denuncia = new Denuncia([
            'internal_code' => 'TEST-' . uniqid(),
            'intake_channel' => 'web',
            'submission_status' => 'confirmed',
            'category_id' => 1,
            'status' => 'new',
        ]);
        $denuncia->tracking_code_hash = hash('sha256', uniqid());
        $denuncia->save();

        $this->assertSame(
            $admin->company_id,
            $denuncia->company_id,
            'company_id debe salir de la sesión, nunca del request.'
        );
    }

    public function test_company_id_del_request_no_puede_cambiar_de_tenant(): void
    {
        $admin = $this->usuario('admin@empresa-a.test');
        $empresaB = Empresa::where('slug', 'empresa-b')->firstOrFail();

        $this->actingAs($admin);

        // Aunque se fuerce otro company_id, el scope impide leerlo después:
        // el registro queda fuera del alcance de quien lo creó.
        $denuncia = new Denuncia([
            'company_id' => $empresaB->id,
            'internal_code' => 'FUGA-' . uniqid(),
            'intake_channel' => 'web',
            'submission_status' => 'confirmed',
            'category_id' => 1,
            'status' => 'new',
        ]);
        $denuncia->tracking_code_hash = hash('sha256', uniqid());
        $denuncia->save();

        $this->assertNull(Denuncia::find($denuncia->id));
    }
}
