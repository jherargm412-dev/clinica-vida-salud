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

## Organización del código (por paquetes)

El código sigue el **diagrama de paquetes**: primero el paquete, luego la capa y luego el caso de uso.

```
app/Modulos/<paquete>/
├── controller/<caso_de_uso>/   Controladores (Ctrl_ en los diagramas)
├── request/<caso_de_uso>/      Validación de formularios
├── service/                    Lógica compleja (solo si hace falta)
├── middleware/                 (solo usuarios_acceso: permisos y cuenta activa)
└── models/                     Modelos Eloquent = entidades (tabla de la BD)
resources/views/<paquete>/<caso_de_uso>/   Vistas Blade (UI_ en los diagramas)
resources/views/compartido/                Plantilla base, menú e inicio
routes/modulos/<paquete>.php               Rutas de cada paquete
```

| Paquete | Casos de uso | Modelos |
|---|---|---|
| `usuarios_acceso` | CU01–CU05 | Usuario, Rol, Permiso, Bitacora |
| `pacientes` | CU06–CU08 | Paciente |
| `medicos_especialidades` | CU09–CU11 | Especialidad, Medico, HorarioAtencion |
| `citas` | CU12–CU16 | Cita |
| `agenda_medica` | CU17–CU18 | — |
| `reportes` | CU19 | — |

Los nombres de carpetas van en `snake_case` y deben coincidir **exactamente** con el `namespace`
(por ejemplo `App\Modulos\usuarios_acceso\models\Usuario`), porque en Linux (Railway) importan las mayúsculas.

## Cómo agregar un caso de uso

1. Crea el controlador en `app/Modulos/<paquete>/controller/<caso_de_uso>/`.
2. Crea sus vistas en `resources/views/<paquete>/<caso_de_uso>/` usando `@extends('compartido.app')`.
3. Agrega sus rutas en `routes/modulos/<paquete>.php`, protegidas con `->middleware('permiso:<clave>')`.
4. En `config/modulos.php` cambia `'ruta' => null` por el nombre de la ruta principal para habilitarlo en el menú.
5. Registra las acciones importantes con `Bitacora::registrar('ACCION', 'tabla', 'detalle');`.
