<?php

/*
| Paquete reportes: CU19
*/

use App\Modulos\reportes\controller\generar_reporte\ReporteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {

    // CU19 Generar reporte. Entra quien tenga cualquiera de los dos permisos; qué reportes y filtros
    // ve cada uno lo decide ReporteController (reportes.generar_todos = los 6 reportes).
    Route::middleware('permiso:reportes.generar,reportes.generar_todos')->group(function () {
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        // POST: exportar escribe en la bitácora
        Route::post('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');
    });
});
