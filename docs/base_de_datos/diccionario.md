# Diccionario de datos (diseño físico)

Tablas y columnas en `snake_case` (PostgreSQL). El script completo está en `database/sql/clinica_vida_salud.sql`.

## Restricciones principales

- Todas las FK son `ON DELETE RESTRICT`, salvo las composiciones con `ON DELETE CASCADE`: `horario_atencion.id_medico`, `detalle_pago.id_pago` y `movimiento_caja.id_sesion_caja`.
- Únicos: `rol.nombre`; `permiso.clave` y `permiso.nombre`; `LOWER(TRIM(especialidad.nombre))`; `usuario.nombre_usuario` y `usuario.correo`; `medico.id_usuario`, `medico.codigo`, `medico.registro_profesional`; `paciente.id_usuario`, `paciente.codigo`, `paciente.ci`; `cita.codigo`; `metodo_pago.nombre`; `pago.id_cita` y `pago.numero_comprobante`; `movimiento_caja.id_detalle_pago`; `recuperacion_contrasena.token`.
- CHECK: `usuario.intentos_fallidos` entre 0 y 3; `paciente.sexo` en ('M','F'); `paciente.fecha_nacimiento <= hoy`; `horario_atencion.dia_semana` entre 1 y 7, `hora_fin > hora_inicio`, `duracion_cita > 0`; `cita.hora_fin > hora_inicio`; `cita.estado` en ('Pendiente de pago','Programada','Confirmada','Atendida','Cancelada','Inasistencia'); montos de tarifa, pago, detalle y movimiento > 0; montos de caja >= 0; estados de `sesion_caja`, `pago`, `detalle_pago` y `notificacion` (ver cada tabla).
- Índice único parcial `uq_cita_medico_horario (id_medico, fecha, hora_inicio) WHERE estado <> 'Cancelada'`: un médico no puede tener dos citas vigentes a la misma hora.
- Índice único parcial `uq_tarifa_activa_especialidad (id_especialidad) WHERE activo = TRUE`: una sola tarifa activa por especialidad.
- Índice único parcial `uq_sesion_caja_abierta (id_usuario) WHERE estado = 'Abierta'`: un recepcionista no puede tener dos cajas abiertas.
- Validado en PHP (no en la BD): correo obligatorio para el personal, contraseña de 8 caracteres o más, horarios de un médico sin superposición, cuenta vinculada con el rol correcto (Médico/Paciente).

## Datos iniciales

- Roles: Administrador, Recepcionista, Medico, Paciente.
- 21 permisos (uno por caso de uso; CU21 tiene dos: cobrar y pagar la propia) asignados según el diagrama general: Administrador todos, Recepcionista 11, Medico 2, Paciente 5.
- Métodos de pago: Efectivo (no en línea) y Pago en línea (en línea), ambos activos.
- Usuario `admin` / `Admin2026*` con rol Administrador.

## rol

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_rol | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del rol |
| nombre | VARCHAR(30) | 30 caracteres | No | No | Nombre del rol (único) |
| descripcion | VARCHAR(150) | 150 caracteres | Sí | No | Detalle de lo que permite el rol |
| activo | BOOLEAN | 1 byte | No | No | Indica si el rol sigue en uso (por defecto TRUE) |

## permiso

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_permiso | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del permiso |
| clave | VARCHAR(50) | 50 caracteres | No | No | Código interno del permiso usado en PHP, p. ej. usuarios.gestionar (único) |
| nombre | VARCHAR(80) | 80 caracteres | No | No | Nombre visible del permiso (único) |
| descripcion | VARCHAR(150) | 150 caracteres | Sí | No | Detalle de lo que habilita el permiso |

## especialidad

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_especialidad | SERIAL | 4 bytes | No | Sí (PK) | Identificador único de la especialidad |
| nombre | VARCHAR(60) | 60 caracteres | No | No | Nombre de la especialidad (único, sin distinguir mayúsculas) |
| descripcion | VARCHAR(200) | 200 caracteres | Sí | No | Descripción de la especialidad |
| activo | BOOLEAN | 1 byte | No | No | Indica si la especialidad está habilitada (por defecto TRUE) |

## usuario

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_usuario | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del usuario |
| id_rol | INTEGER | 4 bytes | No | Sí (FK) | Rol asignado al usuario |
| nombre_usuario | VARCHAR(30) | 30 caracteres | No | No | Nombre para iniciar sesión (único); en pacientes es su CI |
| correo | VARCHAR(100) | 100 caracteres | Sí | No | Correo electrónico (único); obligatorio para el personal y opcional para pacientes |
| contrasena | VARCHAR(255) | 255 caracteres | No | No | Contraseña cifrada con password_hash() |
| nombres | VARCHAR(60) | 60 caracteres | No | No | Nombres de la persona |
| apellidos | VARCHAR(60) | 60 caracteres | No | No | Apellidos de la persona |
| activo | BOOLEAN | 1 byte | No | No | Indica si la cuenta está habilitada (por defecto TRUE) |
| bloqueado | BOOLEAN | 1 byte | No | No | Indica si la cuenta se bloqueó por 3 intentos fallidos (por defecto FALSE) |
| intentos_fallidos | SMALLINT | 2 bytes | No | No | Intentos fallidos consecutivos, de 0 a 3 (por defecto 0) |
| fecha_registro | TIMESTAMP | 8 bytes | No | No | Fecha y hora de creación de la cuenta (por defecto la actual) |

## rol_permiso

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_rol | INTEGER | 4 bytes | No | Sí (PK/FK) | Rol al que se asigna el permiso |
| id_permiso | INTEGER | 4 bytes | No | Sí (PK/FK) | Permiso asignado al rol |

## medico

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_medico | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del médico |
| id_usuario | INTEGER | 4 bytes | No | Sí (FK) | Cuenta de usuario del médico (única) |
| codigo | VARCHAR(10) | 10 caracteres | No | No | Código interno del médico, p. ej. MED-0001 (único) |
| registro_profesional | VARCHAR(30) | 30 caracteres | No | No | Matrícula o registro profesional (único) |
| telefono | VARCHAR(20) | 20 caracteres | No | No | Teléfono de contacto |
| activo | BOOLEAN | 1 byte | No | No | Indica si el médico está habilitado (por defecto TRUE) |

## paciente

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_paciente | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del paciente |
| id_usuario | INTEGER | 4 bytes | No | Sí (FK) | Cuenta de usuario del paciente (única) |
| codigo | VARCHAR(10) | 10 caracteres | No | No | Código interno del paciente, p. ej. PAC-0001 (único) |
| ci | VARCHAR(15) | 15 caracteres | No | No | Documento de identidad (único) |
| fecha_nacimiento | DATE | 4 bytes | No | No | Fecha de nacimiento |
| sexo | CHAR(1) | 1 carácter | No | No | Sexo del paciente: M o F |
| telefono | VARCHAR(20) | 20 caracteres | No | No | Teléfono de contacto |
| direccion | VARCHAR(150) | 150 caracteres | Sí | No | Domicilio del paciente |
| activo | BOOLEAN | 1 byte | No | No | Indica si el paciente está habilitado (por defecto TRUE) |

## bitacora

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_bitacora | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del registro |
| id_usuario | INTEGER | 4 bytes | Sí | Sí (FK) | Usuario que realizó la acción (nulo si el usuario no existe, p. ej. un intento de acceso fallido) |
| fecha_hora | TIMESTAMP | 8 bytes | No | No | Fecha y hora de la acción (por defecto la actual) |
| accion | VARCHAR(50) | 50 caracteres | No | No | Acción realizada, p. ej. INICIO_SESION o REGISTRAR |
| tabla_afectada | VARCHAR(50) | 50 caracteres | Sí | No | Tabla sobre la que se realizó la acción |
| detalle | TEXT | Variable | Sí | No | Descripción de la acción o del cambio realizado |
| ip | VARCHAR(45) | 45 caracteres | No | No | Dirección IP de origen (IPv4 o IPv6) |

## recuperacion_contrasena

Enlaces de un solo uso de CU02 "Recuperar contraseña". Base existente: `database/sql/actualizacion_recuperar_contrasena.sql`.

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_recuperacion | SERIAL | 4 bytes | No | Sí (PK) | Identificador único de la solicitud |
| id_usuario | INTEGER | 4 bytes | No | Sí (FK) | Cuenta que pidió restablecer su contraseña |
| token | VARCHAR(64) | 64 caracteres | No | No (único) | Hash SHA-256 del token enviado por correo (el token real no se guarda) |
| fecha_solicitud | TIMESTAMP | 8 bytes | No | No | Fecha y hora de la solicitud |
| fecha_expiracion | TIMESTAMP | 8 bytes | No | No | Vencimiento del enlace (30 minutos después; CHECK > fecha_solicitud) |
| usado | BOOLEAN | 1 byte | No | No | TRUE si ya se usó o fue reemplazado por un enlace nuevo (por defecto FALSE) |

## medico_especialidad

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_medico | INTEGER | 4 bytes | No | Sí (PK/FK) | Médico que tiene la especialidad |
| id_especialidad | INTEGER | 4 bytes | No | Sí (PK/FK) | Especialidad asignada al médico |

## horario_atencion

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_horario | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del horario |
| id_medico | INTEGER | 4 bytes | No | Sí (FK) | Médico al que pertenece el horario |
| dia_semana | SMALLINT | 2 bytes | No | No | Día de atención: 1 = lunes … 7 = domingo |
| hora_inicio | TIME | 8 bytes | No | No | Hora de inicio de la atención |
| hora_fin | TIME | 8 bytes | No | No | Hora de fin de la atención (mayor que hora_inicio) |
| duracion_cita | SMALLINT | 2 bytes | No | No | Duración de cada cita en minutos (mayor que 0, por defecto 30) |
| activo | BOOLEAN | 1 byte | No | No | Indica si el horario está vigente (por defecto TRUE) |

## cita

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_cita | SERIAL | 4 bytes | No | Sí (PK) | Identificador único de la cita |
| id_paciente | INTEGER | 4 bytes | No | Sí (FK) | Paciente de la cita |
| id_medico | INTEGER | 4 bytes | No | Sí (FK) | Médico que atiende la cita |
| id_especialidad | INTEGER | 4 bytes | No | Sí (FK) | Especialidad de la consulta |
| id_usuario_cancelacion | INTEGER | 4 bytes | Sí | Sí (FK) | Usuario que canceló la cita |
| codigo | VARCHAR(12) | 12 caracteres | No | No | Código de la cita, p. ej. CIT-000001 (único) |
| fecha | DATE | 4 bytes | No | No | Fecha de la cita |
| hora_inicio | TIME | 8 bytes | No | No | Hora de inicio de la cita |
| hora_fin | TIME | 8 bytes | No | No | Hora de fin de la cita |
| motivo | VARCHAR(200) | 200 caracteres | Sí | No | Motivo de la consulta |
| estado | VARCHAR(20) | 20 caracteres | No | No | Pendiente de pago, Programada, Confirmada, Atendida, Cancelada o Inasistencia (por defecto Programada) |
| fecha_creacion | TIMESTAMP | 8 bytes | No | No | Fecha y hora de registro de la cita (por defecto la actual) |
| motivo_cancelacion | VARCHAR(200) | 200 caracteres | Sí | No | Motivo por el que se canceló la cita |
| fecha_cancelacion | TIMESTAMP | 8 bytes | Sí | No | Fecha y hora de la cancelación |
| cancelada_por | VARCHAR(20) | 20 caracteres | Sí | No | 'Sistema' si la canceló el sistema (CU21: pago no realizado a tiempo o cancelado); nulo si la canceló un usuario |

## tarifa

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_tarifa | SERIAL | 4 bytes | No | Sí (PK) | Identificador único de la tarifa |
| id_especialidad | INTEGER | 4 bytes | No | Sí (FK) | Especialidad a la que se aplica |
| monto | NUMERIC(10,2) | 10 dígitos, 2 decimales | No | No | Monto de la consulta en Bs (mayor a 0) |
| vigente_desde | DATE | 4 bytes | No | No | Fecha desde la que rige el monto (se actualiza al modificarlo) |
| activo | BOOLEAN | 1 byte | No | No | Tarifa vigente; solo una activa por especialidad (por defecto TRUE) |

## metodo_pago

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_metodo_pago | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del método |
| nombre | VARCHAR(30) | 30 caracteres | No | No | Efectivo o Pago en línea (único) |
| en_linea | BOOLEAN | 1 byte | No | No | Indica si se cobra por la pasarela en línea (Stripe) |
| activo | BOOLEAN | 1 byte | No | No | Indica si el método está habilitado; al menos uno activo (por defecto TRUE) |

## sesion_caja

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_sesion_caja | SERIAL | 4 bytes | No | Sí (PK) | Identificador único de la sesión de caja |
| id_usuario | INTEGER | 4 bytes | No | Sí (FK) | Recepcionista dueño de la caja |
| apertura | TIMESTAMP | 8 bytes | No | No | Fecha y hora de apertura (por defecto la actual) |
| monto_inicial | NUMERIC(10,2) | 10 dígitos, 2 decimales | No | No | Efectivo con el que se abre la caja (>= 0) |
| cierre | TIMESTAMP | 8 bytes | Sí | No | Fecha y hora de cierre |
| monto_contado | NUMERIC(10,2) | 10 dígitos, 2 decimales | Sí | No | Efectivo contado al cerrar (>= 0) |
| diferencia | NUMERIC(10,2) | 10 dígitos, 2 decimales | Sí | No | Monto contado − monto esperado (sobrante > 0, faltante < 0) |
| estado | VARCHAR(10) | 10 caracteres | No | No | Abierta o Cerrada (por defecto Abierta) |

## pago

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_pago | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del pago |
| id_cita | INTEGER | 4 bytes | No | Sí (FK) | Cita que se paga (única: una cita tiene como máximo un pago) |
| id_usuario | INTEGER | 4 bytes | No | Sí (FK) | Usuario que registró el cobro (recepcionista o paciente) |
| numero_comprobante | VARCHAR(12) | 12 caracteres | Sí | No | Número de comprobante, p. ej. CP-0001 (único; se genera al quedar Pagado) |
| monto_total | NUMERIC(10,2) | 10 dígitos, 2 decimales | No | No | Total según la tarifa al momento del cobro; no cambia si la tarifa cambia |
| fecha | TIMESTAMP | 8 bytes | No | No | Fecha y hora del pago |
| estado | VARCHAR(10) | 10 caracteres | No | No | Pendiente, Pagado o Anulado (por defecto Pendiente) |

## detalle_pago

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_detalle_pago | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del detalle |
| id_pago | INTEGER | 4 bytes | No | Sí (FK) | Pago al que pertenece (composición) |
| id_metodo_pago | INTEGER | 4 bytes | No | Sí (FK) | Método con el que se cobró esta parte |
| monto | NUMERIC(10,2) | 10 dígitos, 2 decimales | No | No | Monto cobrado con este método (> 0) |
| referencia_externa | VARCHAR(100) | 100 caracteres | Sí | No | Referencia de la pasarela (payment_intent de Stripe) |
| id_sesion_pasarela | VARCHAR(255) | 255 caracteres | Sí | No | Id de la sesión de Stripe Checkout, para verificar el pago al volver |
| estado | VARCHAR(10) | 10 caracteres | No | No | Pendiente, Aprobado, Rechazado o Anulado (por defecto Pendiente) |

## movimiento_caja

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_movimiento_caja | SERIAL | 4 bytes | No | Sí (PK) | Identificador único del movimiento |
| id_sesion_caja | INTEGER | 4 bytes | No | Sí (FK) | Caja en la que ingresó el efectivo (composición) |
| id_detalle_pago | INTEGER | 4 bytes | No | Sí (FK) | Detalle en efectivo que lo generó (único) |
| tipo | VARCHAR(10) | 10 caracteres | No | No | Ingreso |
| monto | NUMERIC(10,2) | 10 dígitos, 2 decimales | No | No | Monto del movimiento (> 0) |
| fecha | TIMESTAMP | 8 bytes | No | No | Fecha y hora del movimiento (por defecto la actual) |

## notificacion

| Atributo | Tipo de Dato | Longitud | Nulo | Es llave | Descripción |
|---|---|---|---|---|---|
| id_notificacion | SERIAL | 4 bytes | No | Sí (PK) | Identificador único de la notificación |
| id_cita | INTEGER | 4 bytes | No | Sí (FK) | Cita sobre la que se avisa |
| tipo | VARCHAR(30) | 30 caracteres | No | No | Tipo de aviso, p. ej. Cita programada |
| canal | VARCHAR(10) | 10 caracteres | No | No | Push o Correo |
| programada_para | TIMESTAMP | 8 bytes | No | No | Cuándo debe enviarse (por defecto la actual) |
| enviada_en | TIMESTAMP | 8 bytes | Sí | No | Cuándo se envió |
| estado | VARCHAR(10) | 10 caracteres | No | No | Pendiente, Enviada o Fallida (por defecto Pendiente) |
