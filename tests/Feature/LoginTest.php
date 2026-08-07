<?php

namespace Tests\Feature;

use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const CLAVE = 'Prueba1234!';

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:ip:127.0.0.1');
    }

    public function test_login_correcto_abre_sesion(): void
    {
        $respuesta = $this->post(route('admin.login'), [
            'email' => 'admin@empresa-a.test',
            'password' => self::CLAVE,
        ]);

        $respuesta->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_contrasena_incorrecta_no_abre_sesion(): void
    {
        $this->post(route('admin.login'), [
            'email' => 'admin@empresa-a.test',
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * El helper original abría con `if (!$company_id) return;`, y el
     * login fallido pasaba company_id = 0. Ningún intento fallido llegó
     * nunca a audit_logs.
     */
    public function test_intento_fallido_queda_registrado_en_auditoria(): void
    {
        $antes = RegistroAuditoria::where('action', 'user.login_failed')->count();

        $this->post(route('admin.login'), [
            'email' => 'admin@empresa-a.test',
            'password' => 'incorrecta',
        ]);

        $this->assertSame(
            $antes + 1,
            RegistroAuditoria::where('action', 'user.login_failed')->count()
        );
    }

    /**
     * El superadmin no tiene company_id: en el sistema original toda su
     * actividad quedaba sin registrar.
     */
    public function test_login_de_superadmin_se_registra_en_cadena_de_plataforma(): void
    {
        $this->post(route('admin.login'), [
            'email' => 'super@goharvey.test',
            'password' => self::CLAVE,
        ]);

        $registro = RegistroAuditoria::where('action', 'user.login')
            ->whereNull('company_id')
            ->latest('id')
            ->first();

        $this->assertNotNull($registro, 'El login del superadmin debe registrarse.');
        $this->assertSame(0, (int) $registro->chain_scope);
    }

    public function test_bloqueo_de_cuenta_tras_intentos_fallidos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::clear('login:ip:127.0.0.1');

            $this->post(route('admin.login'), [
                'email' => 'gestor@empresa-a.test',
                'password' => 'incorrecta',
            ]);
        }

        $usuario = Usuario::where('email', 'gestor@empresa-a.test')->firstOrFail();

        $this->assertTrue(
            $usuario->estaBloqueado(),
            'Las columnas locked_until y failed_attempts existían pero nunca se usaron.'
        );
    }

    public function test_usuario_suspendido_no_puede_entrar(): void
    {
        Usuario::where('email', 'gestor@empresa-a.test')->update(['status' => 'suspended']);

        $this->post(route('admin.login'), [
            'email' => 'gestor@empresa-a.test',
            'password' => self::CLAVE,
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sesion_se_corta_si_el_usuario_se_suspende_despues_de_entrar(): void
    {
        $usuario = Usuario::where('email', 'gestor@empresa-a.test')->firstOrFail();

        $this->actingAs($usuario);
        $this->get(route('admin.dashboard'))->assertOk();

        $usuario->update(['status' => 'suspended']);

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }
}
