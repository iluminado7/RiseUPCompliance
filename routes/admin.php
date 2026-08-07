<?php

use App\Http\Controllers\Admin\Auth\DosFactoresController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\RecuperarPasswordController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DenunciaController;
use App\Http\Controllers\Admin\NotificacionController;
use App\Http\Controllers\Admin\PendienteController;
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
| El denunciante nunca pasa por aca.
|
| Las secciones todavia no portadas apuntan a PendienteController. Estan
| declaradas desde ahora porque el sidebar las referencia, y una ruta
| inexistente no rompe el enlace: rompe la pagina entera.
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

    // Desafio de 2FA: entre el primer factor y la sesion abierta. Sin
    // 'auth' porque en ese intervalo todavia no hay sesion.
    Route::get('2fa', [DosFactoresController::class, 'mostrar'])->name('2fa.mostrar');
    Route::post('2fa', [DosFactoresController::class, 'verificar'])
        ->name('2fa.verificar')->middleware('throttle:10,1');

    // -- Autenticado ---------------------------------------------
    Route::middleware(['auth', 'usuario.operativo', 'empresa.operativa', '2fa'])->group(function () {

        Route::post('logout', [LoginController::class, 'salir'])->name('logout');

        Route::get('2fa/alta', [DosFactoresController::class, 'mostrarAlta'])->name('2fa.alta');
        Route::post('2fa/alta', [DosFactoresController::class, 'confirmarAlta'])->name('2fa.alta.confirmar');

        Route::get('/', DashboardController::class)->name('dashboard');

        // -- Notificaciones (campana de la topbar) ---------------
        Route::prefix('notificaciones')->name('notificaciones.')->group(function () {
            Route::get('contar', [NotificacionController::class, 'contar'])->name('contar');
            Route::get('listar', [NotificacionController::class, 'listar'])->name('listar');
            Route::post('leer', [NotificacionController::class, 'leer'])->name('leer');
            Route::post('leer-todas', [NotificacionController::class, 'leerTodas'])->name('leer-todas');
        });

        // -- Denuncias -------------------------------------------
        Route::get('denuncias', [DenunciaController::class, 'index'])->name('denuncias.index');

        // El detalle llega en el proximo bloque de esta etapa.
        Route::get('denuncias/{denuncia}', fn () => app(PendienteController::class)('Detalle de denuncia'))
            ->name('denuncias.show');

        // -- Reportes --------------------------------------------
        Route::middleware('rol:superadmin,admin_principal')->group(function () {
            Route::get('reportes', fn () => app(PendienteController::class)('Reportes'))
                ->name('reportes.index');
        });

        // -- Gestion: superadmin y admin_principal ---------------
        Route::middleware('rol:superadmin,admin_principal')->group(function () {
            Route::get('administracion', fn () => app(PendienteController::class)('Administracion'))
                ->name('administracion.index');
            Route::get('facturacion', fn () => app(PendienteController::class)('Facturacion'))
                ->name('facturacion.index');
        });

        // -- Gestion: solo superadmin ----------------------------
        Route::middleware('rol:superadmin')->group(function () {
            Route::get('onboarding', fn () => app(PendienteController::class)('Onboarding'))
                ->name('onboarding.index');
            Route::get('catalogo', fn () => app(PendienteController::class)('Catalogo'))
                ->name('catalogo.index');
            Route::get('configuracion', fn () => app(PendienteController::class)('Configuracion del canal'))
                ->name('configuracion.index');
            Route::get('config-global', fn () => app(PendienteController::class)('Configuracion global'))
                ->name('config-global.index');
            Route::get('logs', fn () => app(PendienteController::class)('Logs de actividad'))
                ->name('logs.index');
        });

        // -- Comunes ---------------------------------------------
        Route::get('ayuda', fn () => app(PendienteController::class)('Ayuda y soporte'))->name('ayuda');
        Route::get('perfil', fn () => app(PendienteController::class)('Mi perfil'))->name('perfil');
    });
});
