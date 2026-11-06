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

    // CU21 Pagar cita: Recepcionista (pagos.cobrar) o Paciente (pagos.pagar_propia, solo sus citas).
    // El controlador decide qué puede hacer cada uno.
    Route::middleware('permiso:pagos.cobrar,pagos.pagar_propia')->group(function () {
        Route::get('/pagos', [PagoController::class, 'listarPendientes'])->name('pagos.index');
        Route::get('/pagos/citas/{cita}', [PagoController::class, 'mostrarPago'])->name('pagos.pagar');
        Route::post('/pagos/citas/{cita}/forma', [PagoController::class, 'iniciarCobro'])->name('pagos.forma');
        Route::post('/pagos/citas/{cita}/efectivo', [PagoController::class, 'registrarPagoEfectivo'])->name('pagos.efectivo');
        Route::post('/pagos/citas/{cita}/en-linea', [PagoController::class, 'iniciarPagoEnLinea'])->name('pagos.en_linea');
        // retorno: la return_url del formulario incrustado de Stripe (llega con ?session_id=...)
        // cancelado: el enlace "No pagar en línea" junto al formulario (5a)
        Route::get('/pagos/citas/{cita}/retorno', [PagoController::class, 'confirmarPagoEnLinea'])->name('pagos.retorno');
        Route::get('/pagos/citas/{cita}/cancelado', [PagoController::class, 'pagoEnLineaCancelado'])->name('pagos.cancelado');
        Route::post('/pagos/citas/{cita}/confirmar', [PagoController::class, 'confirmarPagoEfectivo'])->name('pagos.confirmar');
        Route::post('/pagos/citas/{cita}/cancelar', [PagoController::class, 'cancelarPago'])->name('pagos.cancelar');
        Route::get('/pagos/citas/{cita}/comprobante', [PagoController::class, 'mostrarComprobante'])->name('pagos.comprobante');
    });
});
