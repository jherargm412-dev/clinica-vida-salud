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
        // La nube entrega la app detrás de proxies internos. Laravel solo lee la IP real del usuario
        // (encabezado X-Forwarded-For) si la petición viene de un proxy de confianza; si no, la bitácora
        // guarda la IP del proxy. Railway usa el rango 100.64.0.0/10 (por eso salían IPs 100.64.0.x);
        // los rangos privados 10.x, 172.16.x y 192.168.x se mantienen (Render, Docker local).
        // Son direcciones internas que no se pueden usar desde internet, así que nadie puede falsificar su IP.
        $middleware->trustProxies(at: [
            '100.64.0.0/10',
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
