<?php

/*
| Paquete agenda_medica: CU17, CU18
*/

use App\Modulos\agenda_medica\controller\consultar_agenda_medica\AgendaMedicaController;
use App\Modulos\agenda_medica\controller\registrar_atencion\AtencionCitaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    Route::middleware('permiso:agenda.consultar')->group(function () {
        Route::get('/agenda', [AgendaMedicaController::class, 'listarMedicosParaAgenda'])
            ->name('agenda.index');
        Route::get('/agenda/medico/{idMedico}', [AgendaMedicaController::class, 'consultarAgenda'])
            ->whereNumber('idMedico')
            ->name('agenda.consultar');
        Route::get('/mi-agenda', [AgendaMedicaController::class, 'consultarMiAgenda'])
            ->name('agenda.mi');
    });

    Route::middleware('permiso:citas.registrar_atencion')->group(function () {
        Route::get('/atenciones', [AtencionCitaController::class, 'listarCitasAtendibles'])
            ->name('atencion.index');
        Route::get('/atenciones/{idCita}', [AtencionCitaController::class, 'obtenerDatosCita'])
            ->whereNumber('idCita')
            ->name('atencion.datos');
        Route::post('/atenciones/{idCita}', [AtencionCitaController::class, 'guardarAtencion'])
            ->whereNumber('idCita')
            ->name('atencion.guardar');
    });
});
