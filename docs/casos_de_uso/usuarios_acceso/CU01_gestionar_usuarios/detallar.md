# CU01 Gestionar Usuarios y Asignar Roles

| Campo | Contenido |
|---|---|
| Caso de uso | CU01 Gestionar Usuarios y Asignar Roles |
| Paquete | usuarios_acceso |
| Permiso | `usuarios.gestionar` |
| Actores | Administrador |
| Actor iniciador | Administrador |

## Propósito
Permitir al administrador registrar, modificar, consultar, desactivar y desbloquear las cuentas de usuario del sistema, asignando a cada una su rol.

## Descripción
El administrador registra las cuentas de las personas que usarán el sistema, con sus datos personales, credenciales y un rol. También puede modificarlas, buscarlas, desactivarlas o reactivarlas, y desbloquear las cuentas bloqueadas por intentos fallidos. Cada acción queda registrada en la bitácora.

## Precondición
El administrador ha iniciado sesión y existe al menos un rol activo.

## Flujo principal
1. El administrador selecciona "Gestionar usuarios" en el panel principal.
2. El sistema muestra la lista de usuarios con nombre, nombre de usuario, correo, rol y estado.
3. El administrador selecciona "Nuevo usuario".
4. El sistema muestra el formulario con los campos nombres, apellidos, nombre de usuario, correo electrónico (opcional), contraseña y la lista de roles activos.
5. El administrador ingresa los datos, selecciona un rol y presiona "Guardar".
6. El sistema valida que los campos obligatorios estén completos, que el nombre de usuario no exista y que el correo, si se ingresó, no esté registrado.
7. El sistema guarda el usuario con la contraseña cifrada y estado activo, y registra la acción en la bitácora.
8. El sistema muestra "Usuario registrado" y la lista actualizada.

## Flujo alternativo
- **6a.** Si algún campo obligatorio está vacío, el sistema muestra "Complete los campos obligatorios" y vuelve al paso 5.
- **6b.** Si el nombre de usuario o el correo ya existen, el sistema muestra "El nombre de usuario o correo ya está registrado" y vuelve al paso 5.
- **Modificar.** El administrador selecciona un usuario y presiona "Editar"; el sistema muestra sus datos; el administrador cambia los datos o el rol y presiona "Guardar"; el sistema valida (6a y 6b), guarda los cambios, registra la acción en la bitácora y muestra "Usuario actualizado". Si el campo contraseña se deja vacío, se mantiene la actual.
- **Consultar.** El administrador escribe un nombre, nombre de usuario o correo en el buscador; el sistema muestra los usuarios que coinciden o "No se encontraron usuarios".
- **Desactivar.** El administrador selecciona un usuario activo y presiona "Desactivar"; el sistema pide confirmación; al confirmar, cambia el estado a inactivo, registra la acción en la bitácora y muestra "Usuario desactivado". El administrador no puede desactivar su propia cuenta.
- **Reactivar.** El administrador selecciona un usuario inactivo y presiona "Activar"; el sistema cambia el estado a activo y registra la acción en la bitácora.
- **Desbloquear.** El administrador selecciona un usuario bloqueado y presiona "Desbloquear"; el sistema quita el bloqueo, reinicia el contador de intentos fallidos, registra la acción en la bitácora y muestra "Cuenta desbloqueada".

## Postcondición
El usuario queda registrado o actualizado con su rol y estado, y la acción queda registrada en la bitácora.
