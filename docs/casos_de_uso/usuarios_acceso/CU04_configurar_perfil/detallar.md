# CU04 Configurar Perfil de Usuario

| Campo | Contenido |
|---|---|
| Caso de uso | CU04 Configurar Perfil de Usuario |
| Paquete | usuarios_acceso |
| Permiso | — (cualquier usuario con sesión iniciada) |
| Actores | Administrador, Recepcionista, Médico, Paciente |
| Actor iniciador | Cualquiera de los actores |

## Propósito
Permitir a cualquier usuario autenticado visualizar y actualizar su información personal y cambiar su contraseña.

## Descripción
El usuario consulta sus datos desde "Mi perfil", actualiza sus datos personales y puede cambiar su contraseña ingresando la actual y la nueva. El nombre de usuario y el rol solo pueden verse, porque los asigna el administrador. El cambio de contraseña queda registrado en la bitácora.

## Precondición
El usuario ha iniciado sesión.

## Flujo principal
1. El usuario selecciona "Mi perfil" en el panel principal.
2. El sistema muestra sus datos: nombres, apellidos, correo electrónico, nombre de usuario y rol (estos dos últimos solo de lectura).
3. El usuario selecciona "Editar", modifica sus nombres, apellidos o correo y presiona "Guardar".
4. El sistema valida que los campos obligatorios estén completos y que el correo no esté registrado por otro usuario.
5. El sistema guarda los cambios y muestra "Perfil actualizado".

## Flujo alternativo
- **4a.** Si algún campo obligatorio está vacío, el sistema muestra "Complete los campos obligatorios" y vuelve al paso 3.
- **4b.** Si el correo ya está registrado por otro usuario, el sistema muestra "El correo ya está registrado" y vuelve al paso 3.
- **Cambiar contraseña.** El usuario selecciona "Cambiar contraseña"; el sistema pide la contraseña actual, la nueva y su confirmación; el usuario las ingresa y presiona "Guardar"; el sistema verifica que la actual sea correcta, que la nueva tenga al menos 8 caracteres y que coincida con la confirmación; guarda la nueva contraseña cifrada, registra la acción en la bitácora y muestra "Contraseña actualizada".
- **Contraseña actual incorrecta.** El sistema muestra "La contraseña actual es incorrecta" y no realiza el cambio.
- **Contraseña nueva inválida.** Si la nueva tiene menos de 8 caracteres o no coincide con la confirmación, el sistema muestra el mensaje correspondiente y no realiza el cambio.
- **Cancelar.** En cualquier momento el usuario presiona "Cancelar"; el sistema descarta los cambios y vuelve a mostrar sus datos.
- **Configurar notificaciones (RF30, solo paciente).** En "Mi perfil" el paciente ve la sección "Notificaciones" con el estado de este dispositivo y la lista de sus dispositivos con notificaciones activas:
  1. El paciente presiona "Activar en este dispositivo".
  2. El navegador pide permiso para mostrar notificaciones y el paciente lo concede.
  3. El sistema suscribe el navegador al servicio push, guarda la suscripción del dispositivo (activa), registra la acción en la bitácora y muestra "Notificaciones activadas en este dispositivo".
  4. Para dejar de recibirlas, el paciente presiona "Desactivar" en este dispositivo o en cualquiera de la lista; el sistema marca esa suscripción como inactiva, registra la acción en la bitácora y muestra "Notificaciones desactivadas en <dispositivo>".
  - **2a.** Si el paciente bloquea el permiso, el sistema muestra "Bloqueadas" y explica cómo permitirlas desde el navegador; no se guarda nada.
  - **2b.** Si el navegador no admite notificaciones (p. ej. Safari de iPhone fuera de la pantalla de inicio), el sistema muestra "No compatible" y cómo agregar la clínica a la pantalla de inicio.
  - **Sin dispositivos activos.** Los avisos de cita (RF31) le llegan por correo.

## Postcondición
Los datos personales o la contraseña del usuario quedan actualizados, y el cambio de contraseña queda registrado en la bitácora. En el flujo "Configurar notificaciones", la suscripción del dispositivo queda activa o inactiva y la acción queda en la bitácora.

## Estado de implementación
"Configurar notificaciones" (RF30): `NotificacionesController.php` (Ctrl_Notificaciones), modelo `SuscripcionPush` (tabla `suscripcion_push`), tarjeta "Notificaciones" en `ver.blade.php`, `public/js/notificaciones.js` y el service worker `public/sw.js`. Diagrama: `secuencia_configurar_notificaciones.puml`. El envío de los avisos (RF31) está en `App\Modulos\pagos\service\Notificador`.
