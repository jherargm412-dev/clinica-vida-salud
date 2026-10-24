<?php

/*
| Paquete medicos_especialidades: CU09, CU10, CU11
*/

use Illuminate\Support\Facades\Route;
use App\Modulos\medicos_especialidades\controller\gestionar_especialidad\EspecialidadController;
use App\Modulos\medicos_especialidades\controller\gestionar_medico\MedicoController;

//Qué es una ruta: une una dirección de la página con un método del controller. Por ejemplo, 
// “cuando abran /especialidades, ejecuta el método index”.

Route::middleware(['auth', 'cuenta.activa'])->group(function () {

    // CU09 Gestionar especialidad
    Route::middleware('permiso:especialidades.gestionar')->group(function () {
        Route::get('/especialidades', [EspecialidadController::class, 'index'])->name('especialidades.index');
        Route::get('/especialidades/nueva', [EspecialidadController::class, 'crear'])->name('especialidades.crear');
        Route::post('/especialidades', [EspecialidadController::class, 'guardar'])->name('especialidades.guardar');
        Route::get('/especialidades/{especialidad}/editar', [EspecialidadController::class, 'editar'])->name('especialidades.editar');
        Route::post('/especialidades/{especialidad}/actualizar', [EspecialidadController::class, 'actualizar'])->name('especialidades.actualizar');
        Route::post('/especialidades/{especialidad}/desactivar', [EspecialidadController::class, 'desactivar'])->name('especialidades.desactivar');
        Route::post('/especialidades/{especialidad}/activar', [EspecialidadController::class, 'activar'])->name('especialidades.activar');
    });
    // CU10 Gestionar médico
    Route::middleware('permiso:medicos.gestionar')->group(function () {
        Route::get('/medicos', [MedicoController::class, 'index'])->name('medicos.index');
        Route::get('/medicos/nuevo', [MedicoController::class, 'crear'])->name('medicos.crear');
        Route::post('/medicos', [MedicoController::class, 'guardar'])->name('medicos.guardar');
        Route::get('/medicos/{medico}/editar', [MedicoController::class, 'editar'])->name('medicos.editar');
        Route::post('/medicos/{medico}/actualizar', [MedicoController::class, 'actualizar'])->name('medicos.actualizar');
        Route::post('/medicos/{medico}/desactivar', [MedicoController::class, 'desactivar'])->name('medicos.desactivar');
        Route::post('/medicos/{medico}/activar', [MedicoController::class, 'activar'])->name('medicos.activar');
    });

    // CU11 Gestionar horario         -> middleware('permiso:horarios.gestionar')
});
