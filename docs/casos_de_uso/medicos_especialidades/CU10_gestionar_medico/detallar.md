# CU10 Gestionar Médico

| Campo | Contenido |
|---|---|
| Caso de uso | CU10 Gestionar Médico |
| Paquete | medicos_especialidades |
| Permiso | `medicos.gestionar` |
| Actores | Administrador |
| Actor iniciador | Administrador |

## Propósito
Permitir al administrador registrar, modificar, consultar, desactivar y activar a los médicos de la clínica.

## Descripción
El administrador registra a cada médico con su número de registro profesional, teléfono y una o varias especialidades (sus nombres, apellidos y correo electrónico se toman de su cuenta de usuario); el sistema le genera un código único. No se permiten dos médicos con el mismo número de registro profesional. El administrador vincula al médico con una cuenta de usuario con rol Médico para que pueda ingresar al sistema y consultar su agenda. Un médico inactivo no puede recibir nuevas citas, pero conserva su historial. Cada cambio queda registrado en la bitácora.

## Precondición
El administrador ha iniciado sesión y existe al menos una especialidad activa y al menos una cuenta de usuario con rol Médico sin vincular.

## Flujo principal
1. El administrador selecciona "Gestionar médicos" en el panel principal.
2. El sistema muestra la lista de médicos con su código, nombre completo, especialidades, teléfono y estado.
3. El administrador selecciona "Nuevo médico".
4. El sistema muestra el formulario con los campos registro profesional y teléfono, la lista de especialidades activas y la lista de cuentas de usuario con rol Médico sin vincular.
5. El administrador elige la cuenta de usuario (el sistema muestra sus nombres, apellidos y correo sin permitir editarlos), ingresa el registro profesional y el teléfono, marca una o más especialidades y presiona "Guardar".
6. El sistema valida que los campos obligatorios estén completos, que haya al menos una especialidad marcada, que se haya seleccionado una cuenta de usuario y que el número de registro profesional no esté registrado.
7. El sistema genera el código del médico (por ejemplo, MED-0001), lo guarda con estado activo junto con sus especialidades y la cuenta de usuario vinculada, y registra la acción en la bitácora.
8. El sistema muestra "Médico registrado" y la lista actualizada.

## Flujo alternativo
- **6a.** Si hay campos obligatorios vacíos, el sistema muestra "Complete los campos obligatorios" y vuelve al paso 5.
- **6b.** Si no se marcó ninguna especialidad, el sistema muestra "Seleccione al menos una especialidad" y vuelve al paso 5.
- **6c.** Si el número de registro profesional ya existe, el sistema muestra "El número de registro profesional ya está registrado" y vuelve al paso 5.
- **6d.** Si no se seleccionó una cuenta de usuario, el sistema muestra "Seleccione la cuenta de usuario del médico" y vuelve al paso 5.
- **Modificar.** El administrador selecciona un médico y presiona "Editar"; el sistema muestra sus datos, sus especialidades marcadas y su cuenta de usuario vinculada; el administrador hace los cambios (puede cambiar o asignar la cuenta de usuario) y presiona "Guardar"; el sistema valida (6a a 6c), guarda los cambios, registra la acción en la bitácora y muestra "Médico actualizado".
- **Consultar.** El administrador escribe un nombre o una especialidad en el buscador; el sistema muestra los médicos que coinciden o "No se encontraron médicos".
- **Desactivar.** El administrador selecciona un médico activo y presiona "Desactivar"; el sistema pide confirmación; al confirmar, cambia el estado a inactivo, registra la acción en la bitácora y muestra "Médico desactivado". Sus citas anteriores se conservan.
- **Activar.** El administrador selecciona un médico inactivo y presiona "Activar"; el sistema cambia el estado a activo, registra la acción en la bitácora y muestra "Médico activado".

## Postcondición
El médico queda registrado o actualizado con sus especialidades, su estado y su cuenta de usuario vinculada, y la acción queda registrada en la bitácora.
