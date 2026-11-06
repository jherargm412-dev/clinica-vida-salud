# CU02 Iniciar Sesión

| Campo | Contenido |
|---|---|
| Caso de uso | CU02 Iniciar Sesión |
| Paquete | usuarios_acceso |
| Permiso | — (página pública; el menú depende de los permisos del rol) |
| Actores | Administrador, Recepcionista, Médico, Paciente |
| Actor iniciador | Cualquiera de los actores |

## Propósito
Permitir que un usuario se autentique para acceder a las funciones que le corresponden según su rol.

## Descripción
El usuario ingresa su nombre de usuario o correo electrónico y su contraseña. El sistema valida las credenciales y el estado de la cuenta y, si son correctos, crea la sesión y le muestra únicamente las opciones de su rol. Desde cualquier pantalla, el usuario puede cerrar su sesión. Después de tres intentos fallidos consecutivos, la cuenta se bloquea.

## Precondición
El usuario está registrado en el sistema y su cuenta está activa y no bloqueada.

## Flujo principal
1. El usuario ingresa a la página de inicio de sesión.
2. El sistema muestra el formulario con los campos usuario o correo electrónico y contraseña.
3. El usuario ingresa sus credenciales y presiona "Ingresar".
4. El sistema valida que los campos no estén vacíos.
5. El sistema verifica que las credenciales sean correctas y que la cuenta esté activa y no bloqueada.
6. El sistema reinicia el contador de intentos fallidos, crea la sesión del usuario y registra el inicio de sesión en la bitácora.
7. El sistema muestra el menú principal con las opciones correspondientes al rol del usuario.

## Flujo alternativo
- **4a.** Si algún campo está vacío, el sistema muestra "Complete todos los campos" y vuelve al paso 3.
- **5a.** Si las credenciales son incorrectas, el sistema incrementa el contador de intentos fallidos, registra el intento en la bitácora, muestra "Usuario o contraseña incorrectos" y vuelve al paso 3.
- **5b.** Si la cuenta está desactivada, el sistema muestra "Su cuenta está desactivada, contacte al administrador" y el caso de uso termina.
- **5c.** Si es el tercer intento fallido consecutivo, el sistema bloquea la cuenta, registra el bloqueo en la bitácora, muestra "Su cuenta fue bloqueada por intentos fallidos; restablezca su contraseña o contacte al administrador" y el caso de uso termina.
- **5d.** Si la cuenta está bloqueada, el sistema muestra "Su cuenta está bloqueada; restablezca su contraseña o contacte al administrador" y el caso de uso termina.
- **Cerrar sesión.** En cualquier momento, el usuario selecciona "Cerrar sesión"; el sistema elimina la sesión y muestra la página de inicio de sesión.
- **3a. Recuperar contraseña.** En el paso 3, el usuario selecciona "¿Olvidó su contraseña?":
  1. El sistema muestra un formulario que pide el correo electrónico de la cuenta.
  2. El usuario ingresa su correo y presiona "Enviar enlace".
  3. El sistema valida que el correo no esté vacío y tenga un formato válido.
  4. Si el correo pertenece a una cuenta activa, el sistema genera un enlace de un solo uso que vence en 30 minutos, lo envía a ese correo y registra la solicitud en la bitácora.
  5. El sistema muestra "Si el correo está registrado, le enviamos un enlace para restablecer su contraseña" (el mismo mensaje aunque el correo no exista, para no revelar qué correos están registrados) y vuelve a la página de inicio de sesión.
  6. El usuario abre el enlace del correo; el sistema verifica que sea válido y muestra el formulario de nueva contraseña.
  7. El usuario ingresa la nueva contraseña y su confirmación y presiona "Guardar contraseña".
  8. El sistema valida la política de contraseñas (mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial) y que ambas coincidan.
  9. El sistema guarda la nueva contraseña cifrada, desbloquea la cuenta, reinicia los intentos fallidos, anula el enlace, registra el cambio en la bitácora y muestra "Su contraseña fue restablecida. Ya puede iniciar sesión". Vuelve al paso 2.
  - **3a.3a.** Si el correo está vacío o no tiene formato válido, el sistema muestra "Ingrese un correo electrónico válido" y vuelve al paso 3a.2.
  - **3a.4a.** Si el correo no está registrado, la cuenta está desactivada o ya se pidió un enlace hace menos de un minuto, no se envía el correo; el sistema muestra el mismo mensaje del paso 3a.5.
  - **3a.4b.** Si no se pudo enviar el correo, el sistema anula el enlace, muestra "No se pudo enviar el correo. Intente nuevamente más tarde" y vuelve al paso 3a.2.
  - **3a.6a.** Si el enlace no existe, ya se usó, fue reemplazado por uno más nuevo, venció o la cuenta fue desactivada, el sistema muestra "El enlace no es válido o ya venció. Solicite uno nuevo" y vuelve al paso 3a.1.
  - **3a.8a.** Si la contraseña no cumple la política o no coincide con la confirmación, el sistema muestra el mensaje correspondiente y vuelve al paso 3a.7.

## Postcondición
El usuario tiene una sesión activa, accede solo a las opciones de su rol y el ingreso queda registrado en la bitácora. En el flujo 3a, la contraseña queda cambiada, la cuenta desbloqueada y el cambio registrado en la bitácora.

## Estado de implementación
Implementado en `app/Modulos/usuarios_acceso/controller/iniciar_sesion/AuthController.php` y `resources/views/usuarios_acceso/iniciar_sesion/login.blade.php`. Los textos de algunos mensajes (4a, 5b, 5c, 5d) varían levemente respecto a este detallar; se aceptó así porque no cambian el sentido.

Flujo 3a (Recuperar contraseña): `RecuperarContrasenaController.php` (Ctrl_RecuperarContrasena), modelo `RecuperacionContrasena` (tabla `recuperacion_contrasena`) y vistas `recuperar.blade.php`, `restablecer.blade.php` y `correo_recuperar.blade.php` en `resources/views/usuarios_acceso/iniciar_sesion/`. El correo se envía con Brevo. Diagrama: `secuencia_recuperar_contrasena.puml`.
