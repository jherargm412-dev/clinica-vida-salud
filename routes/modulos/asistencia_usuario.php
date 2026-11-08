<?php

/*
| Paquete asistencia_usuario: CU23, CU24, CU25
*/

use App\Modulos\asistencia_usuario\controller\consultar_asistente_virtual\AsistenteVirtualController;
use App\Modulos\asistencia_usuario\controller\gestionar_preguntas_frecuentes\PreguntaFrecuenteController;
use App\Modulos\asistencia_usuario\controller\navegar_por_voz\ComandoVozController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa'])->group(function () {

    // CU23 Gestionar preguntas frecuentes
    Route::middleware('permiso:faq.gestionar')->group(function () {
        Route::get('/preguntas-frecuentes', [PreguntaFrecuenteController::class, 'index'])->name('faq.index');
        Route::get('/preguntas-frecuentes/nueva', [PreguntaFrecuenteController::class, 'crear'])->name('faq.crear');
        Route::post('/preguntas-frecuentes', [PreguntaFrecuenteController::class, 'guardar'])->name('faq.guardar');
        Route::get('/preguntas-frecuentes/{preguntaFrecuente}/editar', [PreguntaFrecuenteController::class, 'editar'])->name('faq.editar');
        Route::post('/preguntas-frecuentes/{preguntaFrecuente}/actualizar', [PreguntaFrecuenteController::class, 'actualizar'])->name('faq.actualizar');
        Route::post('/preguntas-frecuentes/{preguntaFrecuente}/desactivar', [PreguntaFrecuenteController::class, 'desactivar'])->name('faq.desactivar');
        Route::post('/preguntas-frecuentes/{preguntaFrecuente}/activar', [PreguntaFrecuenteController::class, 'activar'])->name('faq.activar');
    });

    // CU24 Consultar asistente virtual: actor Paciente (solo con sesión iniciada).
    // "throttle:asistente-virtual" (AppServiceProvider) limita las preguntas por minuto de cada usuario.
    Route::middleware('permiso:asistente.consultar')->prefix('asistente-virtual')->name('asistente.')->group(function () {
        Route::get('/', [AsistenteVirtualController::class, 'mostrar'])->name('mostrar');
        Route::middleware('throttle:asistente-virtual')->group(function () {
            Route::post('/', [AsistenteVirtualController::class, 'enviar'])->name('enviar');
            Route::post('/categoria', [AsistenteVirtualController::class, 'categoria'])->name('categoria');
            Route::post('/preguntas/{preguntaFrecuente}', [AsistenteVirtualController::class, 'pregunta'])->name('pregunta');
            Route::post('/limpiar', [AsistenteVirtualController::class, 'limpiar'])->name('limpiar');
        });
    });

    // CU25 Navegar por comando de voz (el botón de micrófono está en la barra superior)
    Route::middleware('permiso:voz.usar')->group(function () {
        Route::post('/comando-voz', [ComandoVozController::class, 'interpretar'])
            ->middleware('throttle:comando-voz')
            ->name('voz.interpretar');
        Route::get('/comandos-de-voz', [ComandoVozController::class, 'ayuda'])->name('voz.ayuda');
    });
});
