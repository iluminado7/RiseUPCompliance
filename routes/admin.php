<?php

use App\Http\Controllers\Admin\Auth\DosFactoresController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\RecuperarPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel de gestión
|--------------------------------------------------------------------------
|
| Superficie autenticada. El canal público va en routes/web.php y NO
| comparte middleware con esto: son dos modelos de acceso opuestos y el
| router es lo que garantiza estructuralmente que no se mezclen (§2.1).
|
| El denunciante nunca pasa por acá.
|
*/

Route::prefix('admin')->name('admin.')->group(function () {

    // ── Sin autenticar ──────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'mostrar'])->name('login');
        Route::post('login', [LoginController::class, 'ingresar'])
            ->middleware('throttle:10,1');

        Route::get('recuperar', [RecuperarPasswordController::class, 'solicitar'])
            ->name('password.solicitar');
        Route::post('recuperar', [RecuperarPasswordController::class, 'enviar'])
            ->name('password.enviar')
            ->middleware('throttle:10,1');

        Route::get('recuperar/{token}', [RecuperarPasswordController::class, 'formulario'])
            ->name('password.formulario');
        Route::post('recuperar/nueva', [RecuperarPasswordController::class, 'restablecer'])
            ->name('password.restablecer')
            ->middleware('throttle:10,1');
    });

    // Desafío de 2FA: entre el primer factor y la sesión abierta. No lleva
    // 'auth' porque en ese intervalo el usuario todavía no está autenticado.
    Route::get('2fa', [DosFactoresController::class, 'mostrar'])->name('2fa.mostrar');
    Route::post('2fa', [DosFactoresController::class, 'verificar'])
        ->name('2fa.verificar')
        ->middleware('throttle:10,1');

    // ── Autenticado ─────────────────────────────────────────────
    Route::middleware(['auth', 'usuario.operativo', 'empresa.operativa', '2fa'])->group(function () {

        Route::post('logout', [LoginController::class, 'salir'])->name('logout');

        Route::get('2fa/alta', [DosFactoresController::class, 'mostrarAlta'])->name('2fa.alta');
        Route::post('2fa/alta', [DosFactoresController::class, 'confirmarAlta'])->name('2fa.alta.confirmar');

        // Placeholder — el dashboard real llega en la Etapa 5.
        Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');

        // Ejemplo de restricción por rol. Las rutas reales se agregan
        // en las etapas 5 y 6.
        //
        // Route::middleware('rol:superadmin')->group(function () {
        //     Route::get('empresas', [EmpresaController::class, 'index'])->name('empresas.index');
        // });
    });
});
