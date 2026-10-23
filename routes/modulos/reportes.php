<?php

/*
| Paquete reportes: CU19
*/

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    // CU19 Generar reporte           -> middleware('permiso:reportes.generar')
});
