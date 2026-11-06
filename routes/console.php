<?php

use App\Modulos\pagos\service\ReservaPago;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// CU21 (7a): cancela las citas "Pendiente de pago" cuya reserva de 10 minutos venció.
// El sistema ya lo hace solo al usar las pantallas; este comando es un respaldo opcional.
// Uso manual: php artisan pagos:liberar-reservas
Artisan::command('pagos:liberar-reservas', function () {
    $this->info('Reservas liberadas: ' . ReservaPago::liberarVencidas());
})->purpose('Cancela las citas pendientes de pago con la reserva de 10 minutos vencida');

// Solo se ejecuta si corre el scheduler (php artisan schedule:work, o un cron con schedule:run)
Schedule::command('pagos:liberar-reservas')->everyMinute();
