<?php

use App\Http\Controllers\Admin\AccionDenunciaController;
use App\Http\Controllers\Admin\AdministracionController;
use App\Http\Controllers\Admin\Auth\DosFactoresController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\RecuperarPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DenunciaController;
use App\Http\Controllers\Admin\DetalleDenunciaController;
use App\Http\Controllers\Admin\EmpresaController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\NotificacionController;
use App\Http\Controllers\Admin\PendienteController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\SucursalController;
use App\Http\Controllers\Admin\UsuarioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\OnboardingController;
use App\Http\Controllers\Admin\CambioPasswordController;
use App\Http\Controllers\Admin\PerfilController;
use App\Http\Controllers\Admin\CatalogoController;
use App\Http\Controllers\Admin\ConfiguracionCanalController;
use App\Http\Controllers\Admin\FacturacionController;
/*
|--------------------------------------------------------------------------
| Panel de gestion
|--------------------------------------------------------------------------
|
| Superficie autenticada. El canal publico va en routes/web.php y NO
| comparte middleware con esto (2.1).
|
*/

Route::prefix('admin')->name('admin.')->group(function () {

    // -- Sin autenticar ------------------------------------------
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'mostrar'])->name('login');
        Route::post('login', [LoginController::class, 'ingresar'])->middleware('throttle:10,1');

        Route::get('recuperar', [RecuperarPasswordController::class, 'solicitar'])->name('password.solicitar');
        Route::post('recuperar', [RecuperarPasswordController::class, 'enviar'])
            ->name('password.enviar')->middleware('throttle:10,1');
        Route::get('recuperar/{token}', [RecuperarPasswordController::class, 'formulario'])->name('password.formulario');
        Route::post('recuperar/nueva', [RecuperarPasswordController::class, 'restablecer'])
            ->name('password.restablecer')->middleware('throttle:10,1');
    });

    Route::get('2fa', [DosFactoresController::class, 'mostrar'])->name('2fa.mostrar');
    Route::post('2fa', [DosFactoresController::class, 'verificar'])
        ->name('2fa.verificar')->middleware('throttle:10,1');

    // -- Autenticado ---------------------------------------------
    Route::middleware(['auth', 'usuario.operativo', 'empresa.operativa', '2fa', 'password.cambio'])->group(function () {

        Route::post('logout', [LoginController::class, 'salir'])->name('logout');
        Route::get('cambiar-password', [CambioPasswordController::class, 'mostrar'])
            ->name('password.cambio-obligatorio');
        Route::post('cambiar-password', [CambioPasswordController::class, 'guardar'])
            ->name('password.cambio-obligatorio.guardar');
        Route::get('2fa/alta', [DosFactoresController::class, 'mostrarAlta'])->name('2fa.alta');
        Route::post('2fa/alta', [DosFactoresController::class, 'confirmarAlta'])->name('2fa.alta.confirmar');

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::prefix('notificaciones')->name('notificaciones.')->group(function () {
            Route::get('contar', [NotificacionController::class, 'contar'])->name('contar');
            Route::get('listar', [NotificacionController::class, 'listar'])->name('listar');
            Route::post('leer', [NotificacionController::class, 'leer'])->name('leer');
            Route::post('leer-todas', [NotificacionController::class, 'leerTodas'])->name('leer-todas');
        });

        // -- Denuncias -------------------------------------------
        Route::get('denuncias', [DenunciaController::class, 'index'])->name('denuncias.index');
        Route::get('denuncias/{denuncia}', [DetalleDenunciaController::class, 'show'])->name('denuncias.show');

        Route::prefix('denuncias/{denuncia}')->name('denuncias.')->group(function () {
            Route::post('estado', [AccionDenunciaController::class, 'cambiarEstado'])->name('estado');
            Route::post('prioridad', [AccionDenunciaController::class, 'cambiarPrioridad'])->name('prioridad');
            Route::post('asignar', [AccionDenunciaController::class, 'asignar'])->name('asignar');
            Route::post('mensaje', [AccionDenunciaController::class, 'enviarMensaje'])->name('mensaje');

            Route::post('notas', [AccionDenunciaController::class, 'crearNota'])->name('notas.crear');
            Route::put('notas/{nota}', [AccionDenunciaController::class, 'editarNota'])->name('notas.editar');
            Route::delete('notas/{nota}', [AccionDenunciaController::class, 'eliminarNota'])->name('notas.eliminar');
        });

        // -- Superadmin y admin_principal ------------------------
        Route::middleware('rol:superadmin,admin_principal')->group(function () {
            Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');

            Route::get('administracion', [AdministracionController::class, 'index'])
                ->name('administracion.index');

            Route::prefix('sucursales')->name('sucursales.')->group(function () {
                Route::get('nueva', [SucursalController::class, 'create'])->name('create');
                Route::post('/', [SucursalController::class, 'store'])->name('store');
                Route::get('{sucursal}/editar', [SucursalController::class, 'edit'])->name('edit');
                Route::put('{sucursal}', [SucursalController::class, 'update'])->name('update');
            });

            Route::prefix('usuarios')->name('usuarios.')->group(function () {
                Route::get('nuevo', [UsuarioController::class, 'create'])->name('create');
                Route::post('/', [UsuarioController::class, 'store'])->name('store');
                Route::get('{usuario}/editar', [UsuarioController::class, 'edit'])->name('edit');
                Route::put('{usuario}', [UsuarioController::class, 'update'])->name('update');
            });
            Route::prefix('facturacion')->name('facturacion.')->group(function () {
                Route::get('/', [FacturacionController::class, 'index'])->name('index');
                Route::post('/', [FacturacionController::class, 'guardar'])->name('guardar');
                Route::put('{factura}', [FacturacionController::class, 'actualizar'])
                    ->name('actualizar');
                Route::post('{factura}/pagar', [FacturacionController::class, 'pagar'])
                    ->name('pagar');
                Route::post('{factura}/anular', [FacturacionController::class, 'anular'])
                    ->name('anular');
            });
        });

        // -- Solo superadmin -------------------------------------
        Route::middleware('rol:superadmin')->group(function () {

            Route::prefix('empresas')->name('empresas.')->group(function () {
                Route::get('nueva', [EmpresaController::class, 'create'])->name('create');
                Route::post('/', [EmpresaController::class, 'store'])->name('store');
                Route::get('{empresa}/editar', [EmpresaController::class, 'edit'])->name('edit');
                Route::put('{empresa}', [EmpresaController::class, 'update'])->name('update');
                Route::post('{empresa}/estado', [EmpresaController::class, 'cambiarEstado'])->name('estado');
            });

            Route::prefix('onboarding')->name('onboarding.')->group(function () {
                Route::get('/', [OnboardingController::class, 'index'])->name('index');
                Route::post('generar', [OnboardingController::class, 'generar'])->name('generar');

                // {token:id} y no {token}: getRouteKeyName() del modelo
                // devuelve 'token', asi que el binding por defecto pone la
                // credencial del formulario publico en la URL y en el HTML
                // del listado.
                Route::post('{token:id}/revocar', [OnboardingController::class, 'revocar'])
                    ->name('revocar')->whereNumber('token');
                Route::post('{token:id}/confirmar', [OnboardingController::class, 'confirmar'])
                    ->name('confirmar')->whereNumber('token');

                // 'limpiar' ANTES de '{token:id}': con whereNumber no
                // colisionarian igual, pero no conviene depender de eso.
                Route::delete('limpiar', [OnboardingController::class, 'limpiar'])
                    ->name('limpiar');
                Route::delete('{token:id}', [OnboardingController::class, 'eliminar'])
                    ->name('eliminar')->whereNumber('token');
            });
            Route::prefix('catalogo')->name('catalogo.')->group(function () {
                Route::get('/', [CatalogoController::class, 'index'])->name('index');
                Route::post('{tipo}', [CatalogoController::class, 'crear'])->name('crear');
                Route::put('{tipo}/{id}', [CatalogoController::class, 'actualizar'])
                    ->name('actualizar');

                Route::get('categoria/{categoria}/preguntas',
                    [CatalogoController::class, 'preguntas'])->name('preguntas');
                Route::post('categoria/{categoria}/preguntas',
                    [CatalogoController::class, 'agregarPregunta'])->name('preguntas.agregar');
                Route::put('categoria/{categoria}/preguntas/{pregunta}',
                    [CatalogoController::class, 'editarPregunta'])->name('preguntas.editar');
                Route::delete('categoria/{categoria}/preguntas/{pregunta}',
                    [CatalogoController::class, 'desactivarPregunta'])
                    ->name('preguntas.desactivar');
            });
            Route::prefix('configuracion')->name('configuracion.')->group(function () {
                Route::get('/', [ConfiguracionCanalController::class, 'index'])->name('index');
                Route::post('{empresa}/categorias',
                    [ConfiguracionCanalController::class, 'guardarCategorias'])->name('categorias');
                Route::post('{empresa}/areas',
                    [ConfiguracionCanalController::class, 'guardarAreas'])->name('areas');
                Route::post('{empresa}/importar',
                    [ConfiguracionCanalController::class, 'importarCatalogo'])->name('importar');
                Route::post('{empresa}/cuestionario',
                    [ConfiguracionCanalController::class, 'guardarCuestionario'])->name('cuestionario');
                Route::post('{empresa}/legal',
                    [ConfiguracionCanalController::class, 'guardarLegal'])->name('legal');
            });
            Route::get('config-global', fn () => app(PendienteController::class)('Configuracion global'))
                ->name('config-global.index');

            Route::get('logs', [LogController::class, 'index'])->name('logs.index');
        });

        // -- Comunes ---------------------------------------------
        Route::get('ayuda', fn () => app(PendienteController::class)('Ayuda y soporte'))->name('ayuda');


        Route::get('perfil', [PerfilController::class, 'index'])->name('perfil');
        Route::prefix('perfil')->name('perfil.')->group(function () {
           Route::put('datos', [PerfilController::class, 'guardarDatos'])->name('datos');
           Route::put('password', [PerfilController::class, 'cambiarPassword'])->name('password');
           Route::put('preferencias', [PerfilController::class, 'guardarPreferencias'])
               ->name('preferencias');
           Route::delete('2fa', [PerfilController::class, 'desactivarDosFactores'])
               ->name('2fa.desactivar');
       });
    });
});
