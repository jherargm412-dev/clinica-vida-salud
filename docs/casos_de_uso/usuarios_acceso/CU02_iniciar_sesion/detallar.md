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
- **5c.** Si es el tercer intento fallido consecutivo, el sistema bloquea la cuenta, registra el bloqueo en la bitácora, muestra "Su cuenta fue bloqueada por intentos fallidos, contacte al administrador" y el caso de uso termina.
- **5d.** Si la cuenta está bloqueada, el sistema muestra "Su cuenta está bloqueada, contacte al administrador" y el caso de uso termina.
- **Cerrar sesión.** En cualquier momento, el usuario selecciona "Cerrar sesión"; el sistema elimina la sesión y muestra la página de inicio de sesión.

## Postcondición
El usuario tiene una sesión activa, accede solo a las opciones de su rol y el ingreso queda registrado en la bitácora.

## Estado de implementación
Implementado en `app/Modulos/usuarios_acceso/controller/iniciar_sesion/AuthController.php` y `resources/views/usuarios_acceso/iniciar_sesion/login.blade.php`. Los textos de algunos mensajes (4a, 5b, 5c, 5d) varían levemente respecto a este detallar; se aceptó así porque no cambian el sentido.
