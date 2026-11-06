<?php

/*
| Paquete agenda_medica: CU17, CU18
*/

use App\Modulos\agenda_medica\controller\consultar_agenda_medica\AgendaMedicaController;
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

    // CU18 Registrar atención        -> middleware('permiso:citas.registrar_atencion')
});
