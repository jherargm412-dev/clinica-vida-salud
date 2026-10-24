# Decisiones de diseño vigentes

## Actores y roles
- Roles: Administrador, Recepcionista, Médico y Paciente. Todos heredan del actor Usuario (CU02 Iniciar sesión y CU04 Configurar perfil los usa cualquier usuario).
- Los permisos son fijos por rol (tabla `rol_permiso`); hay un permiso por caso de uso, con una clave (`usuarios.gestionar`, `especialidades.gestionar`, `medicos.gestionar`, `citas.registrar`, etc.).
- El rol Administrador no se puede deshabilitar ni perder el permiso de gestionar roles. Un rol con usuarios activos no se puede deshabilitar.

## Datos personales y cuentas
- Nombres, apellidos y correo se guardan **solo en Usuario**. Médico y Paciente tienen una cuenta de usuario obligatoria (Usuario 1 — 0..1 Médico/Paciente) y toman de ella esos datos.
- El correo es obligatorio para el personal (validado en PHP) y opcional para pacientes.
- Contraseñas con hash seguro (`password_hash` / `Hash::make`), mínimo 8 caracteres.
- Tras 3 intentos fallidos consecutivos la cuenta se bloquea; solo el administrador la desbloquea (CU01).
- CU06 crea la cuenta del paciente: usuario = CI, contraseña temporal de 8 caracteres mostrada en pantalla (envío por correo en el Ciclo 2).

## Bajas y estados
- No se borran registros con historial: se desactivan (`activo = false`) usuarios, roles, especialidades, médicos, pacientes y horarios.
- Las tablas puente (`rol_permiso`, `medico_especialidad`) se eliminan o sincronizan al modificar.
- Las citas cambian de estado: Programada, Confirmada, Atendida, Cancelada o Inasistencia.
- La bitácora nunca se modifica ni se elimina.

## Médicos, especialidades y horarios
- Especialidad: nombre único sin distinguir mayúsculas ni espacios (índice `LOWER(TRIM(nombre))`). Una especialidad inactiva no se ofrece para nuevos médicos ni citas.
- Especialidad: el sistema guarda el nombre en formato tipo título ("peDiaTRía" → "Pediatría"; "de", "del", "y"… en minúscula). Se hace en `EspecialidadRequest::prepareForValidation()`. La validación de duplicados ignora mayúsculas, tildes y espacios.
- Médico: código generado (`MED-0001`), registro profesional único, al menos una especialidad, cuenta de usuario con rol Médico. Un médico inactivo no recibe nuevas citas pero conserva su historial.
- Médico ◆— HorarioAtencion es composición (el horario no existe sin su médico).
- Duración de cita por defecto: 30 minutos.

## Citas
- Un médico no puede tener dos citas vigentes a la misma fecha y hora (índice único parcial `WHERE estado <> 'Cancelada'`).
- Las citas atendidas o canceladas no se reprograman; se registra una nueva.
- El paciente es actor de CU12 y CU13 (modelado en el Ciclo 1; su pantalla se implementa en el Ciclo 2).

## Arquitectura
- Laravel monolítico con Blade, sin API (indicado por el auxiliar).
- Código organizado por paquetes (`app/Modulos/<paquete>`), igual que el diagrama de paquetes.
- Base de datos creada con el script SQL; no se usan migraciones de Laravel.

## Usuarios (CU01)
- En CU01 solo se asignan roles activos **excepto Paciente** (las cuentas de pacientes se crean en el CU06 junto con su ficha). Al editar una cuenta se muestra también su rol actual.
- Correo opcional; si se ingresa, debe ser único. Contraseña obligatoria al registrar; al modificar, si se deja vacía se mantiene.
- Política de contraseñas (CU01, CU04, CU06): mínimo 8 caracteres con al menos 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial (ej.: `Clave123!`). Se pide confirmar la contraseña (campo `contrasena_confirmation`). Centralizada en `app/Modulos/compartido/Contrasena.php`.
- Nombre de usuario y correo se guardan en minúsculas; el login no distingue mayúsculas. Nombres y apellidos en formato tipo título (`app/Modulos/compartido/Texto.php`, compartido con especialidades y pacientes).
- El administrador no puede desactivar su propia cuenta.

## Médicos (CU10)
- La cuenta del médico se elige entre cuentas **activas** con rol Medico sin vincular (al editar se incluye la actual). Nombre y correo se muestran de la cuenta, sin editarse.
- Al editar se muestran las especialidades activas y además las que el médico ya tiene aunque estén inactivas ("(inactiva)"), para no quitárselas sin querer. Se guardan con `sync()` en `medico_especialidad`.
- Código `MED-0001` generado con el mayor número existente + 1. Registro profesional en mayúsculas y único (sin distinguir mayúsculas). Teléfono: números, espacios, + o -; 7 a 20 caracteres.
- Desactivar médico (CU10) no desactiva su cuenta: puede seguir entrando a ver su agenda e historial, pero no recibe nuevas citas.
- Desactivar la cuenta (CU01) de un médico también lo desactiva como médico. Reactivar la cuenta NO reactiva al médico. No se puede activar un médico cuya cuenta está desactivada ("Active primero la cuenta de usuario del médico").

## Filtros en las listas
- Especialidades: Estado. Usuarios: Rol (incluye Paciente) y Estado (Activo/Inactivo/Bloqueado). Médicos: Especialidad (incluye inactivas) y Estado. Se combinan con el buscador, se mantienen al paginar y "Limpiar" los quita.

## Roles y permisos (CU03)
- Los 4 roles del sistema (Administrador, Recepcionista, Medico, Paciente) no se pueden renombrar (el código los busca por nombre); sí su descripción y permisos. Los roles nuevos sí se renombran.
- El rol Administrador no se puede deshabilitar ni perder el permiso `roles.gestionar` (casilla bloqueada + validación en el servidor).
- No se deshabilita un rol con usuarios activos ("No se puede deshabilitar un rol con usuarios activos"). Habilitar muestra "Rol habilitado".
- Los cambios de permisos se aplican de inmediato (se leen en cada petición).
- Permisos agrupados por paquete en el formulario y en "Ver" (`Permiso::GRUPOS`). Nombre del rol en formato tipo título, único sin distinguir mayúsculas ni tildes.

## Bitácora (CU05)
- Solo lectura: una sola ruta GET; el modelo `Bitacora` cancela cualquier update/delete.
- Filtros: Desde, Hasta (4a: "La fecha final no puede ser anterior a la fecha inicial"), Usuario (incluye "Desconocido" = intentos con usuario inexistente), Acción y Tabla afectada. 20 registros por página, del más reciente al más antiguo.
- Al abrir la bitácora, Desde y Hasta vienen con la fecha de hoy; "Limpiar filtros" muestra todos los registros. La IP se ve en la lista.
- Zona horaria America/La_Paz en Laravel (`config/app.php`) y en la sesión de PostgreSQL (`config/database.php`), para que "hoy" y `fecha_hora` coincidan también en Railway.
- Ver detalle en un modal (fecha y hora, usuario y correo, acción, detalle, tabla afectada, IP).
- Ciclo 2: "Datos anteriores / Datos nuevos" (ver ideas del Ciclo 2).

## Configurar perfil de usuario (CU04)
- Renombrado de "Gestionar Perfil de Usuario" a "Configurar Perfil de Usuario" (24/10). Acceso desde el panel izquierdo ("Mi perfil", para todos) y desde el menú del nombre de usuario.
- Una sola pantalla: encabezado (iniciales, nombre, correo, rol), Datos personales y Cambiar contraseña lado a lado ("Mostrar contraseñas"), Información de la cuenta (usuario, rol, estado, registro; si es médico: código, registro profesional, teléfono y especialidades) e Inicios de sesión recientes (últimos 5 de la bitácora). Teléfono y fecha de nacimiento NO se agregan a Usuario (no están en la BD).
- Sin permiso especial: cualquier usuario con sesión; siempre trabaja con su propia cuenta. Usuario y rol solo lectura.
- Correo opcional y único (4b "El correo ya está registrado"). Nombres/apellidos tipo título.
- Nueva contraseña con la misma política del CU01 (Contrasena::reglas, con confirmación y checklist verde) y distinta de la actual ("La nueva contraseña debe ser diferente de la actual").
- Bitácora: ACTUALIZAR_PERFIL y CAMBIAR_CONTRASENA.

