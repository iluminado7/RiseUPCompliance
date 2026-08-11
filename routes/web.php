<?php

use App\Http\Controllers\Publico\CanalController;
use App\Http\Controllers\Publico\OnboardingController;
use App\Http\Controllers\Publico\SeguimientoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Canal publico
|--------------------------------------------------------------------------
|
| SIN AUTENTICACION. Es la superficie opuesta al panel: aca entra gente
| que no tiene ni va a tener usuario en el sistema. Ligar al denunciante
| al login destruiria el anonimato, que es la propuesta de valor del
| producto (2.1 del brief).
|
| El router es lo que garantiza esa separacion estructuralmente: estas
| rutas no comparten ningun middleware de sesion con routes/admin.php.
|
| El rate limiting es la unica defensa disponible cuando no hay sesion.
|
*/

Route::get('/', fn () => redirect('/admin'));

// -- Alta de empresa por invitacion --------------------------------
Route::prefix('onboarding/{token}')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'mostrar'])->name('mostrar');
    Route::get('enviado', [OnboardingController::class, 'enviado'])->name('enviado');

    Route::middleware('throttle:20,1')->group(function () {
        Route::post('paso-1', [OnboardingController::class, 'guardarPaso1'])->name('paso1');
        Route::post('paso-2', [OnboardingController::class, 'guardarPaso2'])->name('paso2');
        Route::post('paso-3', [OnboardingController::class, 'guardarPaso3'])->name('paso3');
        Route::post('confirmar', [OnboardingController::class, 'confirmar'])->name('confirmar');
        Route::post('volver', [OnboardingController::class, 'volver'])->name('volver');
    });
})->where('token', '[a-f0-9]{64}');

// -- Seguimiento de una denuncia -----------------------------------
// Va ANTES de /{slug}: si no, el router tomaria "seguimiento" como el
// slug de una empresa.
Route::prefix('seguimiento')->name('seguimiento.')->group(function () {
    Route::get('/', [SeguimientoController::class, 'formulario'])->name('formulario');
    Route::get('estado', [SeguimientoController::class, 'estado'])->name('estado');

    Route::middleware('throttle:30,1')->group(function () {
        Route::post('consultar', [SeguimientoController::class, 'consultar'])->name('consultar');
        Route::post('responder', [SeguimientoController::class, 'responder'])->name('responder');
        Route::post('salir', [SeguimientoController::class, 'salir'])->name('salir');
    });
});

// -- Canal de denuncias de cada empresa ----------------------------
// Al final del archivo: /{slug} matchea cualquier cosa, asi que todo lo
// especifico tiene que estar declarado antes.
Route::prefix('{slug}')->name('canal.')->group(function () {
    Route::get('/', [CanalController::class, 'inicio'])->name('inicio');
    Route::get('enviada', [CanalController::class, 'confirmacion'])->name('confirmacion');
    Route::get('paso/{paso}', [CanalController::class, 'paso'])
        ->name('paso')->where('paso', '[1-7]');
    Route::get('volver/{paso}', [CanalController::class, 'volver'])
        ->name('volver')->where('paso', '[1-7]');

    Route::middleware('throttle:40,10')->group(function () {
        Route::post('paso/{paso}', [CanalController::class, 'guardar'])
            ->name('guardar')->where('paso', '[1-7]');
        Route::post('abandonar', [CanalController::class, 'abandonar'])->name('abandonar');
    });
})->where('slug', '[a-z0-9\-]+');

// PENDIENTE: sumar Cloudflare Turnstile a estos formularios. Es componente
// reusable de Business Partner.
