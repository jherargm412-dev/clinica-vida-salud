<?php

/*
| Paquete agenda_medica: CU17, CU18
*/

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    // CU17 Consultar agenda          -> middleware('permiso:agenda.consultar')
    // CU18 Registrar atención        -> middleware('permiso:citas.registrar_atencion')
});
