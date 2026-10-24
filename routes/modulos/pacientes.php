<?php

/*
| Paquete pacientes: CU06, CU07, CU08
*/

use App\Modulos\pacientes\controller\gestionar_paciente\PacienteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {

    // CU06 Gestionar paciente
    Route::middleware('permiso:pacientes.gestionar')->group(function () {
        Route::get('/pacientes', [PacienteController::class, 'listarPacientes'])->name('pacientes.index');
        Route::get('/pacientes/nuevo', [PacienteController::class, 'mostrarFormularioNuevo'])->name('pacientes.crear');
        Route::post('/pacientes', [PacienteController::class, 'registrarPaciente'])->name('pacientes.guardar');
        Route::get('/pacientes/{paciente}/editar', [PacienteController::class, 'mostrarFormularioEdicion'])->name('pacientes.editar');
        Route::post('/pacientes/{paciente}/actualizar', [PacienteController::class, 'modificarPaciente'])->name('pacientes.actualizar');
        Route::post('/pacientes/{paciente}/desactivar', [PacienteController::class, 'desactivarPaciente'])->name('pacientes.desactivar');
        Route::post('/pacientes/{paciente}/activar', [PacienteController::class, 'activarPaciente'])->name('pacientes.activar');
    });

    // CU07 Gestionar perfil médico   -> middleware('permiso:perfil_medico.gestionar')
    // CU08 Consultar historial       -> middleware('permiso:historial.consultar')
});