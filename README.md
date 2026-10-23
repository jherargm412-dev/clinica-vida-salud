# Clínica Vida Salud — Sistema web de gestión de citas médicas

Proyecto del Grupo 15 (Ingeniería de Requisitos). Aplicación **monolítica** con patrón **MVC**:

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2+ con Laravel 12 |
| Vistas | Blade + Bootstrap 5 (CDN) + JavaScript |
| Base de datos | PostgreSQL (script `database/sql/clinica_vida_salud.sql`) |

## Cómo ejecutarlo en tu computadora

1. Instala **PHP 8.2+** (con la extensión `pdo_pgsql`), **Composer** y **PostgreSQL**.
2. Crea la base de datos `clinica_vida_salud` y ejecuta el script `database/sql/clinica_vida_salud.sql`
   (en pgAdmin: Query Tool → abrir archivo → ejecutar).
3. En la carpeta del proyecto:
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```
4. Abre `.env` y escribe la contraseña de tu usuario `postgres` en `DB_PASSWORD`.
5. Ejecuta `php artisan serve` y abre http://localhost:8000

Usuario inicial: **admin** / **Admin2026*** (cámbiala después).

> No se usan migraciones de Laravel: la base se crea con el script SQL.

## Dónde está cada cosa (MVC)

| Diagramas | Laravel | Carpeta |
|---|---|---|
| `UI_...` (boundary) | Vistas Blade | `resources/views/` |
| `Ctrl_...` (control) | Controladores | `app/Http/Controllers/` |
| Entidades | Modelos Eloquent | `app/Models/` |
| — | Rutas (URL → controlador) | `routes/web.php` |
| — | Permisos por rol | `app/Http/Middleware/VerificarPermiso.php` + `config/modulos.php` |

## Cómo agregar un módulo (caso de uso)

1. Crea el controlador en `app/Http/Controllers/` (por ejemplo `EspecialidadController.php`).
2. Crea sus vistas en `resources/views/<modulo>/` usando `@extends('layouts.app')`.
3. Agrega sus rutas en `routes/web.php` dentro del grupo `auth`, protegidas con
   `->middleware('permiso:<clave del permiso>')`.
4. En `config/modulos.php` cambia `'ruta' => null` por el nombre de la ruta principal
   para que aparezca habilitado en el menú.
5. Registra las acciones importantes con `Bitacora::registrar('ACCION', 'tabla', 'detalle');`.
