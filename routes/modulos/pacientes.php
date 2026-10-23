<?php

/*
| Paquete pacientes: CU06, CU07, CU08
*/

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    // CU06 Gestionar paciente        -> middleware('permiso:pacientes.gestionar')
    // CU07 Gestionar perfil médico   -> middleware('permiso:perfil_medico.gestionar')
    // CU08 Consultar historial       -> middleware('permiso:historial.consultar')
});
