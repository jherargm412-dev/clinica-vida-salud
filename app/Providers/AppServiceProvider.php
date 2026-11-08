<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // En la nube (Railway) todas las URL se generan con https
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // La paginación usa los estilos de Bootstrap 5
        Paginator::useBootstrapFive();

        // Transporte de correo "brevo" (API HTTP de Brevo, paquete symfony/brevo-mailer).
        // Se activa con MAIL_MAILER=brevo; en local se puede dejar MAIL_MAILER=log.
        Mail::extend('brevo', fn () => (new BrevoTransportFactory())->create(
            new Dsn('brevo+api', 'default', config('services.brevo.key'))
        ));

        // CU02 "Recuperar contraseña": límites de peticiones por IP. Al tener nombre, cada uno usa
        // su propio contador (los "throttle:N,1" sin nombre comparten uno solo por IP con el login).
        RateLimiter::for('recuperar-contrasena', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('restablecer-contrasena', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        // CU24 Asistente virtual: máximo 30 acciones por minuto por usuario.
        // Al superarlo, Laravel muestra la página 429 ("Demasiadas consultas").
        RateLimiter::for('asistente-virtual', fn (Request $request) => Limit::perMinute(30)->by((string) $request->user()?->id_usuario ?: $request->ip()));
        // CU25 Comandos de voz: máximo 30 por minuto por usuario.
        RateLimiter::for('comando-voz', fn (Request $request) => Limit::perMinute(30)->by((string) $request->user()?->id_usuario ?: $request->ip()));
    }
}
