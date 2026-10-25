<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render entrega la app detrás de varios proxies internos (IPs privadas 10.x).
        // Confiamos en los rangos privados para que request()->ip() devuelva la IP real del usuario.
        $middleware->trustProxies(at: [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.1',
            '::1',
        ]);

        // Sin sesión → al login; con sesión e intentando entrar al login → al inicio
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('inicio'));

        $middleware->alias([
            'permiso' => \App\Modulos\usuarios_acceso\middleware\VerificarPermiso::class,
            'cuenta.activa' => \App\Modulos\usuarios_acceso\middleware\VerificarCuentaActiva::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Las páginas de error están en resources/views/errors (403, 404, 419, 500)
    })->create();
