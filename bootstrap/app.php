<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
return Application::configure(basePath: dirname(__DIR__))
        ->withRouting(
            web: __DIR__.'/../routes/web.php',
            commands: __DIR__.'/../routes/console.php',
            health: '/up',
            then: function () {
                Route::middleware('web')->group(base_path('routes/admin.php'));
            },
        )
        ->withMiddleware(function (Middleware $middleware) {
            $middleware->alias([
                'usuario.operativo' => \App\Http\Middleware\UsuarioOperativo::class,
                'empresa.operativa' => \App\Http\Middleware\EmpresaOperativa::class,
                '2fa' => \App\Http\Middleware\RequiereDosFactores::class,
                'rol' => \App\Http\Middleware\RequiereRol::class,
            ]);
            $middleware->redirectGuestsTo(fn () => route('admin.login'));
             $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
             })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
