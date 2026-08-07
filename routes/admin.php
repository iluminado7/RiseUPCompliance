<?php

use App\Http\Controllers\Admin\AccionDenunciaController;
use App\Http\Controllers\Admin\Auth\DosFactoresController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\RecuperarPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DenunciaController;
use App\Http\Controllers\Admin\DetalleDenunciaController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\NotificacionController;
use App\Http\Controllers\Admin\PendienteController;
use App\Http\Controllers\Admin\ReporteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel de gestion
|--------------------------------------------------------------------------
|
| Superficie autenticada. El canal publico va en routes/web.php y NO
| comparte middleware con esto: son dos modelos de acceso opuestos y el
| router es lo que garantiza estructuralmente que no se mezclen (2.1).
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
    Route::middleware(['auth', 'usuario.operativo', 'empresa.operativa', '2fa'])->group(function () {

        Route::post('logout', [LoginController::class, 'salir'])->name('logout');

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

            Route::get('administracion', fn () => app(PendienteController::class)('Administracion'))
                ->name('administracion.index');
            Route::get('facturacion', fn () => app(PendienteController::class)('Facturacion'))
                ->name('facturacion.index');
        });

        // -- Solo superadmin -------------------------------------
        Route::middleware('rol:superadmin')->group(function () {
            Route::get('onboarding', fn () => app(PendienteController::class)('Onboarding'))
                ->name('onboarding.index');
            Route::get('catalogo', fn () => app(PendienteController::class)('Catalogo'))
                ->name('catalogo.index');
            Route::get('configuracion', fn () => app(PendienteController::class)('Configuracion del canal'))
                ->name('configuracion.index');
            Route::get('config-global', fn () => app(PendienteController::class)('Configuracion global'))
                ->name('config-global.index');

            Route::get('logs', [LogController::class, 'index'])->name('logs.index');
        });

        // -- Comunes ---------------------------------------------
        Route::get('ayuda', fn () => app(PendienteController::class)('Ayuda y soporte'))->name('ayuda');
        Route::get('perfil', fn () => app(PendienteController::class)('Mi perfil'))->name('perfil');
    });
});
