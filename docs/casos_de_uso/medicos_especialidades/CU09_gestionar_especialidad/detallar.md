# CU09 Gestionar Especialidad

| Campo | Contenido |
|---|---|
| Caso de uso | CU09 Gestionar Especialidad |
| Paquete | medicos_especialidades |
| Permiso | `especialidades.gestionar` |
| Actores | Administrador |
| Actor iniciador | Administrador |

## Propósito
Permitir al administrador registrar, modificar, consultar, desactivar y activar las especialidades que ofrece la clínica.

## Descripción
El administrador registra cada especialidad con su nombre, una descripción opcional y su estado. No se permiten dos especialidades con el mismo nombre normalizado (sin distinguir mayúsculas, tildes ni espacios extra). Una especialidad inactiva no se ofrece para asignar médicos ni para nuevas citas. Cada cambio queda registrado en la bitácora.

## Precondición
El administrador ha iniciado sesión.

## Flujo principal
1. El administrador selecciona "Gestionar especialidades" en el panel principal.
2. El sistema muestra la lista de especialidades con su nombre, descripción y estado.
3. El administrador selecciona "Nueva especialidad".
4. El sistema muestra el formulario con los campos nombre y descripción.
5. El administrador ingresa el nombre y, opcionalmente, la descripción, y presiona "Guardar".
6. El sistema valida que el nombre no esté vacío y que no exista otra especialidad con el mismo nombre normalizado.
7. El sistema guarda la especialidad con estado activo y registra la acción en la bitácora.
8. El sistema muestra "Especialidad registrada" y la lista actualizada.

## Flujo alternativo
- **6a.** Si el nombre está vacío, el sistema muestra "Ingrese el nombre de la especialidad" y vuelve al paso 5.
- **6b.** Si ya existe una especialidad con ese nombre, el sistema muestra "Ya existe una especialidad con ese nombre" y vuelve al paso 5.
- **Modificar.** El administrador selecciona una especialidad y presiona "Editar"; el sistema muestra sus datos; el administrador hace los cambios y presiona "Guardar"; el sistema valida (6a y 6b), guarda los cambios, registra la acción en la bitácora y muestra "Especialidad actualizada".
- **Consultar.** El administrador escribe un nombre en el buscador; el sistema muestra las especialidades que coinciden o "No se encontraron especialidades".
- **Desactivar.** El administrador selecciona una especialidad activa y presiona "Desactivar"; el sistema pide confirmación; al confirmar, cambia el estado a inactivo, registra la acción en la bitácora y muestra "Especialidad desactivada". Los médicos y citas que ya la tienen se conservan.
- **Activar.** El administrador selecciona una especialidad inactiva y presiona "Activar"; el sistema cambia el estado a activo, registra la acción en la bitácora y muestra "Especialidad activada".

## Postcondición
La especialidad queda registrada o actualizada con su estado, y la acción queda registrada en la bitácora.
