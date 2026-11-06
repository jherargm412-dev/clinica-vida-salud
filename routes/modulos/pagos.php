<?php

/*
| Paquete pagos: CU20, CU21, CU22
*/

use Illuminate\Support\Facades\Route;
use App\Modulos\pagos\controller\gestionar_caja\CajaController;
use App\Modulos\pagos\controller\gestionar_tarifas_metodos_pago\TarifaMetodoPagoController;
use App\Modulos\pagos\controller\pagar_cita\PagoController;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {

    // CU20 Gestionar tarifas y métodos de pago
    Route::middleware('permiso:pagos.configurar')->group(function () {
        Route::get('/tarifas', [TarifaMetodoPagoController::class, 'index'])->name('tarifas.index');
        Route::get('/tarifas/nueva', [TarifaMetodoPagoController::class, 'crear'])->name('tarifas.crear');
        Route::post('/tarifas', [TarifaMetodoPagoController::class, 'guardar'])->name('tarifas.guardar');
        Route::get('/tarifas/{tarifa}/editar', [TarifaMetodoPagoController::class, 'editar'])->name('tarifas.editar');
        Route::post('/tarifas/{tarifa}/actualizar', [TarifaMetodoPagoController::class, 'actualizar'])->name('tarifas.actualizar');
        Route::post('/tarifas/{tarifa}/desactivar', [TarifaMetodoPagoController::class, 'desactivar'])->name('tarifas.desactivar');
        Route::post('/metodos-pago/{metodoPago}/estado', [TarifaMetodoPagoController::class, 'cambiarEstadoMetodo'])->name('metodos_pago.estado');
    });

    // CU22 Gestionar caja (cada usuario trabaja con su propia caja)
    Route::middleware('permiso:caja.gestionar')->group(function () {
        Route::get('/caja', [CajaController::class, 'mostrarCaja'])->name('caja.index');
        Route::post('/caja/abrir', [CajaController::class, 'abrirCaja'])->name('caja.abrir');
        Route::get('/caja/cerrar', [CajaController::class, 'prepararCierre'])->name('caja.cerrar.confirmar');
        Route::post('/caja/cerrar', [CajaController::class, 'cerrarCaja'])->name('caja.cerrar');
        Route::get('/caja/cierres/{sesionCaja}', [CajaController::class, 'mostrarResumenCierre'])->name('caja.cierre');
    });
});
