<?php

/*
| Paquete citas: CU12, CU13, CU14, CU15, CU16
*/

use Illuminate\Support\Facades\Route;
use App\Modulos\citas\controller\consultar_disponibilidad\DisponibilidadController;
use App\Modulos\citas\controller\registrar_cita\CitaController;

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

    // CU13 Registrar cita
    Route::middleware('permiso:citas.registrar')->group(function () {
        Route::get('/citas/nueva', [CitaController::class, 'mostrarFormularioRegistro'])->name('citas.crear');
        Route::post('/citas', [CitaController::class, 'registrarCita'])->name('citas.guardar');
        Route::get('/citas/{cita}/confirmacion', [CitaController::class, 'mostrarConfirmacion'])->name('citas.confirmacion');
    });

    // CU14 Reprogramar cita
    Route::middleware('permiso:citas.reprogramar')->group(function () {
        Route::get('/citas/reprogramar', [CitaController::class, 'listarCitasReprogramables'])
            ->name('citas.reprogramar.index');
        Route::get('/citas/reprogramar/buscar', [CitaController::class, 'buscarCitasReprogramables'])
            ->name('citas.reprogramar.buscar');
        Route::get('/citas/{cita}/reprogramar', [CitaController::class, 'obtenerCitaReprogramable'])
            ->name('citas.reprogramar.editar');
        Route::post('/citas/{cita}/reprogramar', [CitaController::class, 'reprogramarCita'])
            ->name('citas.reprogramar.guardar');
        Route::get('/citas/{cita}/reprogramada', [CitaController::class, 'mostrarConfirmacionReprogramacion'])
            ->name('citas.reprogramar.confirmacion');
    });

    // CU15 Cancelar cita
    Route::middleware('permiso:citas.cancelar')->group(function () {
        Route::get('/citas/cancelar', [CitaController::class, 'listarCitasCancelables'])
            ->name('citas.cancelar.index');
        Route::get('/citas/cancelar/buscar', [CitaController::class, 'buscarCitasCancelables'])
            ->name('citas.cancelar.buscar');
        Route::get('/citas/{cita}/cancelar', [CitaController::class, 'obtenerCitaCancelable'])
            ->name('citas.cancelar.confirmar');
        Route::post('/citas/{cita}/cancelar', [CitaController::class, 'cancelarCita'])
            ->name('citas.cancelar.guardar');
    });

    // CU16 Controlar estado          -> middleware('permiso:citas.controlar_estado')
});
