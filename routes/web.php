<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InicioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web
|--------------------------------------------------------------------------
| Cada ruta conecta una dirección (URL) con un método de un controlador.
| - guest:          solo sin sesión (login)
| - auth:           solo con sesión iniciada
| - cuenta.activa:  cierra la sesión si la cuenta fue desactivada o bloqueada
| - permiso:clave:  solo si el rol tiene ese permiso (tabla rol_permiso)
*/

Route::get('/', fn () => redirect()->route(auth()->check() ? 'inicio' : 'login'));

// CU02 Iniciar sesión
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'iniciarSesion'])
        ->middleware('throttle:10,1')          // máximo 10 intentos por minuto desde la misma IP
        ->name('login.intentar');
});

Route::middleware(['auth', 'cuenta.activa'])->group(function () {
    Route::post('/logout', [AuthController::class, 'cerrarSesion'])->name('logout');
    Route::get('/inicio', [InicioController::class, 'index'])->name('inicio');

    // Aquí se agregan los módulos de cada caso de uso, por ejemplo:
    // Route::middleware('permiso:especialidades.gestionar')->group(function () {
    //     Route::get('/especialidades', [EspecialidadController::class, 'index'])->name('especialidades.index');
    // });
});
