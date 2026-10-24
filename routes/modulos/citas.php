<?php

/*
| Paquete citas: CU12, CU13, CU14, CU15, CU16
*/

use Illuminate\Support\Facades\Route;
use App\Modulos\citas\controller\consultar_disponibilidad\DisponibilidadController;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    // CU12 Consultar disponibilidad
    Route::middleware('permiso:disponibilidad.consultar')->group(function () {
        Route::get('/disponibilidad', [DisponibilidadController::class, 'listarEspecialidadesActivas'])
            ->name('disponibilidad.index');
        Route::get('/disponibilidad/medicos', [DisponibilidadController::class, 'listarMedicosPorEspecialidad'])
            ->name('disponibilidad.medicos');
        Route::get('/disponibilidad/horarios', [DisponibilidadController::class, 'consultarHorariosDisponibles'])
            ->name('disponibilidad.horarios');
    });

    // CU13 Registrar cita            -> middleware('permiso:citas.registrar')
    // CU14 Reprogramar cita          -> middleware('permiso:citas.reprogramar')
    // CU15 Cancelar cita             -> middleware('permiso:citas.cancelar')
    // CU16 Controlar estado          -> middleware('permiso:citas.controlar_estado')
});
