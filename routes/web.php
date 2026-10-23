<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web
|--------------------------------------------------------------------------
| Las rutas de cada paquete están en routes/modulos/<paquete>.php
| (misma organización que el diagrama de paquetes).
|
| Middleware que se usan:
| - guest:          solo sin sesión (login)
| - auth:           solo con sesión iniciada
| - cuenta.activa:  cierra la sesión si la cuenta fue desactivada o bloqueada
| - permiso:clave:  solo si el rol tiene ese permiso (tabla rol_permiso)
*/

Route::get('/', fn () => redirect()->route(auth()->check() ? 'inicio' : 'login'));

require __DIR__ . '/modulos/usuarios_acceso.php';
require __DIR__ . '/modulos/pacientes.php';
require __DIR__ . '/modulos/medicos_especialidades.php';
require __DIR__ . '/modulos/citas.php';
require __DIR__ . '/modulos/agenda_medica.php';
require __DIR__ . '/modulos/reportes.php';
