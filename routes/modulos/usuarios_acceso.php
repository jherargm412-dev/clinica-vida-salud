<?php

/*
| Paquete usuarios_acceso: CU01, CU02, CU03, CU04, CU05
*/

use App\Modulos\usuarios_acceso\controller\iniciar_sesion\AuthController;
use App\Modulos\usuarios_acceso\controller\iniciar_sesion\InicioController;
use App\Modulos\usuarios_acceso\controller\gestionar_usuarios\UsuarioController;
use App\Modulos\usuarios_acceso\controller\gestionar_roles\RolController;
use App\Modulos\usuarios_acceso\controller\consultar_bitacora\BitacoraController;
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

    // CU03 Gestionar roles y permisos
    // (la ruta /roles/nuevo va ANTES de /roles/{rol} para que "nuevo" no se tome como un id)
    Route::middleware('permiso:roles.gestionar')->group(function () {
        Route::get('/roles', [RolController::class, 'index'])->name('roles.index');
        Route::get('/roles/nuevo', [RolController::class, 'crear'])->name('roles.crear');
        Route::post('/roles', [RolController::class, 'guardar'])->name('roles.guardar');
        Route::get('/roles/{rol}', [RolController::class, 'ver'])->name('roles.ver');
        Route::get('/roles/{rol}/editar', [RolController::class, 'editar'])->name('roles.editar');
        Route::post('/roles/{rol}/actualizar', [RolController::class, 'actualizar'])->name('roles.actualizar');
        Route::post('/roles/{rol}/deshabilitar', [RolController::class, 'deshabilitar'])->name('roles.deshabilitar');
        Route::post('/roles/{rol}/habilitar', [RolController::class, 'habilitar'])->name('roles.habilitar');
    });

    // CU04 Gestionar perfil        -> (todos los usuarios con sesión)
    // CU05 Consultar bitácora (solo lectura: una sola ruta GET; el detalle se ve en un modal)
    Route::get('/bitacora', [BitacoraController::class, 'index'])
        ->middleware('permiso:bitacora.consultar')
        ->name('bitacora.index');
});
