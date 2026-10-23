<?php

/*
| Paquete citas: CU12, CU13, CU14, CU15, CU16
*/

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    // CU12 Consultar disponibilidad  -> middleware('permiso:disponibilidad.consultar')
    // CU13 Registrar cita            -> middleware('permiso:citas.registrar')
    // CU14 Reprogramar cita          -> middleware('permiso:citas.reprogramar')
    // CU15 Cancelar cita             -> middleware('permiso:citas.cancelar')
    // CU16 Controlar estado          -> middleware('permiso:citas.controlar_estado')
});
