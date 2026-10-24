<?php

/*
| Paquete usuarios_acceso: CU01, CU02, CU03, CU04, CU05
*/

use App\Modulos\usuarios_acceso\controller\iniciar_sesion\AuthController;
use App\Modulos\usuarios_acceso\controller\iniciar_sesion\InicioController;
use App\Modulos\usuarios_acceso\controller\gestionar_usuarios\UsuarioController;
use Illuminate\Support\Facades\Route;

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

    // CU01 Gestionar usuarios
    Route::middleware('permiso:usuarios.gestionar')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/nuevo', [UsuarioController::class, 'crear'])->name('usuarios.crear');
        Route::post('/usuarios', [UsuarioController::class, 'guardar'])->name('usuarios.guardar');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'editar'])->name('usuarios.editar');
        Route::post('/usuarios/{usuario}/actualizar', [UsuarioController::class, 'actualizar'])->name('usuarios.actualizar');
        Route::post('/usuarios/{usuario}/desactivar', [UsuarioController::class, 'desactivar'])->name('usuarios.desactivar');
        Route::post('/usuarios/{usuario}/reactivar', [UsuarioController::class, 'reactivar'])->name('usuarios.reactivar');
        Route::post('/usuarios/{usuario}/desbloquear', [UsuarioController::class, 'desbloquear'])->name('usuarios.desbloquear');
    });

    // CU03 Gestionar roles         -> middleware('permiso:roles.gestionar')
    // CU04 Gestionar perfil        -> (todos los usuarios con sesión)
    // CU05 Consultar bitácora      -> middleware('permiso:bitacora.consultar')
});
