<?php

use App\Http\Controllers\Publico\CanalController;
use App\Http\Controllers\Publico\OnboardingController;
use App\Http\Controllers\Publico\PortadaController;
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
| ATENCION AL ->where(): va SIEMPRE encadenado ANTES del ->group(), nunca
| despues. RouteRegistrar::group() registra las rutas y recien ahi
| devuelve $this, asi que un where() posterior guarda la restriccion en un
| registrar que ya no la va a aplicar: se pierde en silencio, sin error.
| Eso fue exactamente el bug del 404 en /admin — {slug} quedaba sin
| patron, matcheaba "admin" y el canal publico se comia el panel entero.
|
*/

// -- Portada del sistema -------------------------------------------
Route::get('/', [PortadaController::class, 'portada'])->name('portada.inicio');

Route::prefix('denunciar')->name('portada.')->group(function () {
    Route::get('/', [PortadaController::class, 'selector'])->name('selector');

    // La busqueda tiene su propio limite: sin el, alguien recorre el
    // abecedario con unas pocas decenas de consultas y reconstruye la
    // lista de clientes, que es justamente lo que el buscador sin listado
    // quiere evitar.
    Route::get('buscar', [PortadaController::class, 'buscar'])
        ->name('buscar')->middleware('throttle:30,1');

    Route::post('/', [PortadaController::class, 'ir'])
        ->name('ir')->middleware('throttle:20,1');
});

// -- Alta de empresa por invitacion --------------------------------
// El where() del token filtra en el router lo que de otro modo llegaria
// al controlador: sin el, cualquier string entra como token de invitacion.
Route::prefix('onboarding/{token}')
    ->name('onboarding.')
    ->where(['token' => '[a-f0-9]{64}'])
    ->group(function () {
        Route::get('/', [OnboardingController::class, 'mostrar'])->name('mostrar');
        Route::get('enviado', [OnboardingController::class, 'enviado'])->name('enviado');

        Route::middleware('throttle:20,1')->group(function () {
            Route::post('paso-1', [OnboardingController::class, 'guardarPaso1'])->name('paso1');
            Route::post('paso-2', [OnboardingController::class, 'guardarPaso2'])->name('paso2');
            Route::post('paso-3', [OnboardingController::class, 'guardarPaso3'])->name('paso3');
            Route::post('confirmar', [OnboardingController::class, 'confirmar'])->name('confirmar');
            Route::post('volver', [OnboardingController::class, 'volver'])->name('volver');
        });
    });

// -- Seguimiento de una denuncia -----------------------------------
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
// especifico tiene que estar declarado antes. Si maniana se agrega una
// ruta publica nueva, va ARRIBA de este bloque Y su primer segmento se
// suma a la lista de exclusion del where(), o el router la va a tomar
// como el slug de una empresa.
//
// La exclusion es defensa en profundidad, no la unica linea: routes/admin.php
// se registra despues que este archivo, asi que si el patron se rompe el
// panel entero queda inalcanzable.
Route::prefix('{slug}')
    ->name('canal.')
    ->where(['slug' => '(?!(?:admin|onboarding|seguimiento|denunciar|up)$)[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?'])
    ->group(function () {
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
    });

// PENDIENTE: sumar Cloudflare Turnstile a estos formularios. Es componente
// reusable de Business Partner.