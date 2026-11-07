<?php

use App\Modulos\pagos\models\Notificacion;
use App\Modulos\pagos\service\Notificador;
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

// RF31: recordatorio 24 h antes de cada cita Programada o Confirmada (push y, si no hay o falla, correo).
// Los recordatorios se crean al programar o reprogramar la cita; este comando envía los que ya tocan.
// Uso manual: php artisan notificaciones:enviar-recordatorios
Artisan::command('notificaciones:enviar-recordatorios', function () {
    $creados = Notificacion::crearRecordatoriosFaltantes();   // respaldo: citas que nunca tuvieron recordatorio
    $pendientes = Notificacion::recordatoriosPorEnviar();

    foreach ($pendientes as $recordatorio) {
        Notificador::enviar($recordatorio);
    }

    $this->info("Recordatorios creados: {$creados} · procesados: {$pendientes->count()}");
})->purpose('Envía los recordatorios de cita que ya tocan (24 h antes)');

// Solo se ejecuta si corre el scheduler (php artisan schedule:work, o un cron con schedule:run).
// En Railway lo arranca docker/entrypoint.sh.
Schedule::command('pagos:liberar-reservas')->everyMinute();
Schedule::command('notificaciones:enviar-recordatorios')->everyFiveMinutes()->withoutOverlapping();
