<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
    }
}
