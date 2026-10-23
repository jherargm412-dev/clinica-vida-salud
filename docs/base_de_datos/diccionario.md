# Diccionario de datos (diseño físico)

Tablas y columnas en `snake_case` (PostgreSQL). El script completo está en `database/sql/clinica_vida_salud.sql`.

## Restricciones principales

- Todas las FK son `ON DELETE RESTRICT`, salvo `horario_atencion.id_medico` → `ON DELETE CASCADE` (composición).
- Únicos: `rol.nombre`; `permiso.clave` y `permiso.nombre`; `LOWER(TRIM(especialidad.nombre))`; `usuario.nombre_usuario` y `usuario.correo`; `medico.id_usuario`, `medico.codigo`, `medico.registro_profesional`; `paciente.id_usuario`, `paciente.codigo`, `paciente.ci`; `cita.codigo`.
- CHECK: `usuario.intentos_fallidos` entre 0 y 3; `paciente.sexo` en ('M','F'); `paciente.fecha_nacimiento <= hoy`; `horario_atencion.dia_semana` entre 1 y 7, `hora_fin > hora_inicio`, `duracion_cita > 0`; `cita.hora_fin > hora_inicio`; `cita.estado` en ('Programada','Confirmada','Atendida','Cancelada','Inasistencia').
- Índice único parcial `uq_cita_medico_horario (id_medico, fecha, hora_inicio) WHERE estado <> 'Cancelada'`: un médico no puede tener dos citas vigentes a la misma hora.
- Validado en PHP (no en la BD): correo obligatorio para el personal, contraseña de 8 caracteres o más, horarios de un médico sin superposición, cuenta vinculada con el rol correcto (Médico/Paciente).

## Datos iniciales

- Roles: Administrador, Recepcionista, Medico, Paciente.
- 17 permisos (uno por caso de uso) asignados según el diagrama general: Administrador 8, Recepcionista 9, Medico 2, Paciente 4.
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
| estado | VARCHAR(15) | 15 caracteres | No | No | Programada, Confirmada, Atendida, Cancelada o Inasistencia (por defecto Programada) |
| fecha_creacion | TIMESTAMP | 8 bytes | No | No | Fecha y hora de registro de la cita (por defecto la actual) |
| motivo_cancelacion | VARCHAR(200) | 200 caracteres | Sí | No | Motivo por el que se canceló la cita |
| fecha_cancelacion | TIMESTAMP | 8 bytes | Sí | No | Fecha y hora de la cancelación |
