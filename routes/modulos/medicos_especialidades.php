<?php

/*
| Paquete medicos_especialidades: CU09, CU10, CU11
*/

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    // CU09 Gestionar especialidad    -> middleware('permiso:especialidades.gestionar')
    // CU10 Gestionar médico          -> middleware('permiso:medicos.gestionar')
    // CU11 Gestionar horario         -> middleware('permiso:horarios.gestionar')
});
