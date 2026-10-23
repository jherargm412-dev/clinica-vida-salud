# CU03 Gestionar Roles y Permisos

| Campo | Contenido |
|---|---|
| Caso de uso | CU03 Gestionar Roles y Permisos |
| Paquete | usuarios_acceso |
| Permiso | `roles.gestionar` |
| Actores | Administrador |
| Actor iniciador | Administrador |

## Propósito
Permitir al administrador registrar, modificar, consultar y deshabilitar roles, y definir qué permisos tiene cada uno.

## Descripción
El administrador crea roles y les asigna permisos sobre las funciones del sistema, que vienen predefinidas. Los usuarios ven en el menú solo las opciones que permite su rol. Un rol no puede deshabilitarse si tiene usuarios activos asignados. Cada cambio queda registrado en la bitácora.

## Precondición
El administrador ha iniciado sesión y los permisos del sistema están registrados (uno por cada función del sistema).

## Flujo principal
1. El administrador selecciona "Gestionar roles y permisos" en el panel principal.
2. El sistema muestra la lista de roles con nombre, descripción y estado.
3. El administrador selecciona "Nuevo rol".
4. El sistema muestra el formulario con los campos nombre y descripción, y la lista de permisos disponibles para marcar.
5. El administrador ingresa el nombre y la descripción, marca los permisos del rol y presiona "Guardar".
6. El sistema valida que el nombre esté completo y no exista otro rol con el mismo nombre, y que se haya marcado al menos un permiso.
7. El sistema guarda el rol con estado activo y sus permisos, y registra la acción en la bitácora.
8. El sistema muestra "Rol registrado" y la lista actualizada.

## Flujo alternativo
- **6a.** Si el nombre está vacío, el sistema muestra "Ingrese el nombre del rol" y vuelve al paso 5.
- **6b.** Si ya existe un rol con ese nombre, el sistema muestra "Ya existe un rol con ese nombre" y vuelve al paso 5.
- **6c.** Si no se marcó ningún permiso, el sistema muestra "Seleccione al menos un permiso" y vuelve al paso 5.
- **Modificar.** El administrador selecciona un rol y presiona "Editar"; el sistema muestra su nombre, descripción y permisos marcados; el administrador hace los cambios y presiona "Guardar"; el sistema valida (6a a 6c), guarda los cambios, registra la acción en la bitácora y muestra "Rol actualizado". Los cambios se aplican a los usuarios de ese rol en su próximo inicio de sesión.
- **Consultar.** El administrador selecciona un rol y presiona "Ver"; el sistema muestra sus datos y la lista de sus permisos.
- **Deshabilitar.** El administrador selecciona un rol activo y presiona "Deshabilitar"; el sistema pide confirmación; al confirmar, verifica que el rol no tenga usuarios activos asignados, cambia su estado a inactivo, registra la acción en la bitácora y muestra "Rol deshabilitado".
- **Deshabilitar con usuarios.** Si el rol tiene usuarios activos asignados, el sistema muestra "No se puede deshabilitar un rol con usuarios activos" y no realiza el cambio.
- **Rol Administrador.** El sistema no permite deshabilitar el rol Administrador ni quitarle el permiso de gestionar roles y permisos.
- **Habilitar.** El administrador selecciona un rol inactivo y presiona "Habilitar"; el sistema cambia su estado a activo y registra la acción en la bitácora.

## Postcondición
El rol queda registrado o actualizado con sus permisos y estado, y la acción queda registrada en la bitácora.
