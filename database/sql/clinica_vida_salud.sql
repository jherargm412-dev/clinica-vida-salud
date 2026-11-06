-- =====================================================================
-- Sistema web de gestión de citas médicas - Clínica Vida Salud
-- Script de base de datos - Ciclos 1 y 2 (PostgreSQL)
-- Grupo 15
--
-- Uso:  1) CREATE DATABASE clinica_vida_salud;
--       2) psql -U postgres -d clinica_vida_salud -f clinica_vida_salud.sql
-- El script se puede volver a ejecutar: borra las tablas y las crea de nuevo.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 0. Limpieza (orden inverso a las dependencias)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS movimiento_caja     CASCADE;
DROP TABLE IF EXISTS notificacion        CASCADE;
DROP TABLE IF EXISTS atencion_medica     CASCADE;
DROP TABLE IF EXISTS detalle_pago        CASCADE;
DROP TABLE IF EXISTS pago                CASCADE;
DROP TABLE IF EXISTS sesion_caja         CASCADE;
DROP TABLE IF EXISTS tarifa              CASCADE;
DROP TABLE IF EXISTS metodo_pago         CASCADE;
DROP TABLE IF EXISTS cita                CASCADE;
DROP TABLE IF EXISTS horario_atencion    CASCADE;
DROP TABLE IF EXISTS medico_especialidad CASCADE;
DROP TABLE IF EXISTS bitacora            CASCADE;
DROP TABLE IF EXISTS perfil_medico       CASCADE;
DROP TABLE IF EXISTS paciente            CASCADE;
DROP TABLE IF EXISTS medico              CASCADE;
DROP TABLE IF EXISTS rol_permiso         CASCADE;
DROP TABLE IF EXISTS usuario             CASCADE;
DROP TABLE IF EXISTS especialidad        CASCADE;
DROP TABLE IF EXISTS permiso             CASCADE;
DROP TABLE IF EXISTS rol                 CASCADE;

-- ---------------------------------------------------------------------
-- 1. Tablas independientes
-- ---------------------------------------------------------------------
CREATE TABLE rol (
    id_rol       SERIAL       PRIMARY KEY,
    nombre       VARCHAR(30)  NOT NULL,
    descripcion  VARCHAR(150),
    activo       BOOLEAN      NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_rol_nombre UNIQUE (nombre)
);

CREATE TABLE permiso (
    id_permiso   SERIAL       PRIMARY KEY,
    clave        VARCHAR(50)  NOT NULL,
    nombre       VARCHAR(80)  NOT NULL,
    descripcion  VARCHAR(150),
    CONSTRAINT uq_permiso_clave  UNIQUE (clave),
    CONSTRAINT uq_permiso_nombre UNIQUE (nombre)
);

CREATE TABLE especialidad (
    id_especialidad SERIAL       PRIMARY KEY,
    nombre          VARCHAR(60)  NOT NULL,
    descripcion     VARCHAR(200),
    activo          BOOLEAN      NOT NULL DEFAULT TRUE,
    CONSTRAINT ck_especialidad_nombre CHECK (TRIM(nombre) <> '')
);
-- Nombre único sin distinguir mayúsculas ni espacios ("Cardiología" = " cardiología ")
CREATE UNIQUE INDEX uq_especialidad_nombre ON especialidad (LOWER(TRIM(nombre)));

CREATE TABLE metodo_pago (
    id_metodo_pago  SERIAL       PRIMARY KEY,
    nombre          VARCHAR(30)  NOT NULL,
    en_linea        BOOLEAN      NOT NULL DEFAULT FALSE,
    activo          BOOLEAN      NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_metodo_pago_nombre UNIQUE (nombre)
);

-- ---------------------------------------------------------------------
-- 2. Nivel 1: dependen de las tablas independientes
-- ---------------------------------------------------------------------
CREATE TABLE usuario (
    id_usuario        SERIAL        PRIMARY KEY,
    id_rol            INTEGER       NOT NULL,
    nombre_usuario    VARCHAR(30)   NOT NULL,
    correo            VARCHAR(100),                       -- obligatorio para el personal (PHP), opcional para pacientes
    contrasena        VARCHAR(255)  NOT NULL,             -- hash de password_hash()
    nombres           VARCHAR(60)   NOT NULL,
    apellidos         VARCHAR(60)   NOT NULL,
    activo            BOOLEAN       NOT NULL DEFAULT TRUE,
    bloqueado         BOOLEAN       NOT NULL DEFAULT FALSE,
    intentos_fallidos SMALLINT      NOT NULL DEFAULT 0,
    fecha_registro    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_rol          FOREIGN KEY (id_rol) REFERENCES rol (id_rol) ON DELETE RESTRICT,
    CONSTRAINT uq_usuario_nombre       UNIQUE (nombre_usuario),
    CONSTRAINT uq_usuario_correo       UNIQUE (correo),
    CONSTRAINT ck_usuario_intentos     CHECK (intentos_fallidos BETWEEN 0 AND 3),
    CONSTRAINT ck_usuario_correo       CHECK (correo IS NULL OR correo LIKE '_%@_%._%')
);

CREATE TABLE rol_permiso (
    id_rol      INTEGER NOT NULL,
    id_permiso  INTEGER NOT NULL,
    CONSTRAINT pk_rol_permiso         PRIMARY KEY (id_rol, id_permiso),
    CONSTRAINT fk_rol_permiso_rol     FOREIGN KEY (id_rol)     REFERENCES rol (id_rol)         ON DELETE RESTRICT,
    CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (id_permiso) REFERENCES permiso (id_permiso) ON DELETE RESTRICT
);

-- ---------------------------------------------------------------------
-- 3. Nivel 2: dependen de las tablas de nivel 1
-- ---------------------------------------------------------------------
CREATE TABLE medico (
    id_medico            SERIAL       PRIMARY KEY,
    id_usuario           INTEGER      NOT NULL,
    codigo               VARCHAR(10)  NOT NULL,          -- MED-0001
    registro_profesional VARCHAR(30)  NOT NULL,
    telefono             VARCHAR(20)  NOT NULL,
    activo               BOOLEAN      NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_medico_usuario    FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE RESTRICT,
    CONSTRAINT uq_medico_usuario    UNIQUE (id_usuario),
    CONSTRAINT uq_medico_codigo     UNIQUE (codigo),
    CONSTRAINT uq_medico_registro   UNIQUE (registro_profesional)
);

CREATE TABLE paciente (
    id_paciente      SERIAL        PRIMARY KEY,
    id_usuario       INTEGER       NOT NULL,
    codigo           VARCHAR(10)   NOT NULL,             -- PAC-0001
    ci               VARCHAR(15)   NOT NULL,
    fecha_nacimiento DATE          NOT NULL,
    sexo             CHAR(1)       NOT NULL,
    telefono         VARCHAR(20)   NOT NULL,
    direccion        VARCHAR(150),
    activo           BOOLEAN       NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_paciente_usuario   FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE RESTRICT,
    CONSTRAINT uq_paciente_usuario   UNIQUE (id_usuario),
    CONSTRAINT uq_paciente_codigo    UNIQUE (codigo),
    CONSTRAINT uq_paciente_ci        UNIQUE (ci),
    CONSTRAINT ck_paciente_sexo      CHECK (sexo IN ('M', 'F')),
    CONSTRAINT ck_paciente_fecha_nac CHECK (fecha_nacimiento <= CURRENT_DATE)
);

CREATE TABLE bitacora (
    id_bitacora     SERIAL       PRIMARY KEY,
    id_usuario      INTEGER,                              -- NULL: intento de acceso con un usuario inexistente
    fecha_hora      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accion          VARCHAR(50)  NOT NULL,
    tabla_afectada  VARCHAR(50),
    detalle         TEXT,
    ip              VARCHAR(45)  NOT NULL,
    CONSTRAINT fk_bitacora_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE RESTRICT
);

CREATE TABLE tarifa (
    id_tarifa        SERIAL         PRIMARY KEY,
    id_especialidad  INTEGER        NOT NULL,
    monto            NUMERIC(10,2)  NOT NULL,
    vigente_desde    DATE           NOT NULL DEFAULT CURRENT_DATE,
    activo           BOOLEAN        NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_tarifa_especialidad FOREIGN KEY (id_especialidad) REFERENCES especialidad (id_especialidad) ON DELETE RESTRICT,
    CONSTRAINT ck_tarifa_monto        CHECK (monto > 0)
);
-- Una sola tarifa activa por especialidad (las inactivas quedan como historial)
CREATE UNIQUE INDEX uq_tarifa_activa_especialidad ON tarifa (id_especialidad) WHERE activo = TRUE;

CREATE TABLE sesion_caja (
    id_sesion_caja  SERIAL         PRIMARY KEY,
    id_usuario      INTEGER        NOT NULL,              -- recepcionista dueño de la caja
    apertura        TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto_inicial   NUMERIC(10,2)  NOT NULL,
    cierre          TIMESTAMP,
    monto_contado   NUMERIC(10,2),
    diferencia      NUMERIC(10,2),                        -- monto_contado - monto esperado
    estado          VARCHAR(10)    NOT NULL DEFAULT 'Abierta',
    CONSTRAINT fk_sesion_caja_usuario  FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE RESTRICT,
    CONSTRAINT ck_sesion_caja_inicial  CHECK (monto_inicial >= 0),
    CONSTRAINT ck_sesion_caja_contado  CHECK (monto_contado IS NULL OR monto_contado >= 0),
    CONSTRAINT ck_sesion_caja_estado   CHECK (estado IN ('Abierta', 'Cerrada')),
    CONSTRAINT ck_sesion_caja_cierre   CHECK (
        (estado = 'Abierta' AND cierre IS NULL)
        OR (estado = 'Cerrada' AND cierre IS NOT NULL AND monto_contado IS NOT NULL AND diferencia IS NOT NULL))
);
-- Un recepcionista no puede tener dos cajas abiertas
CREATE UNIQUE INDEX uq_sesion_caja_abierta ON sesion_caja (id_usuario) WHERE estado = 'Abierta';

-- ---------------------------------------------------------------------
-- 4. Nivel 3: dependen de las tablas de nivel 2
-- ---------------------------------------------------------------------
CREATE TABLE medico_especialidad (
    id_medico        INTEGER NOT NULL,
    id_especialidad  INTEGER NOT NULL,
    CONSTRAINT pk_medico_especialidad PRIMARY KEY (id_medico, id_especialidad),
    CONSTRAINT fk_me_medico       FOREIGN KEY (id_medico)       REFERENCES medico (id_medico)             ON DELETE RESTRICT,
    CONSTRAINT fk_me_especialidad FOREIGN KEY (id_especialidad) REFERENCES especialidad (id_especialidad) ON DELETE RESTRICT
);

CREATE TABLE horario_atencion (
    id_horario     SERIAL    PRIMARY KEY,
    id_medico      INTEGER   NOT NULL,
    dia_semana     SMALLINT  NOT NULL,                    -- 1 = lunes ... 7 = domingo
    hora_inicio    TIME      NOT NULL,
    hora_fin       TIME      NOT NULL,
    duracion_cita  SMALLINT  NOT NULL DEFAULT 30,         -- minutos
    activo         BOOLEAN   NOT NULL DEFAULT TRUE,
    -- Composición: el horario no existe sin su médico
    CONSTRAINT fk_horario_medico   FOREIGN KEY (id_medico) REFERENCES medico (id_medico) ON DELETE CASCADE,
    CONSTRAINT ck_horario_dia      CHECK (dia_semana BETWEEN 1 AND 7),
    CONSTRAINT ck_horario_horas    CHECK (hora_fin > hora_inicio),
    CONSTRAINT ck_horario_duracion CHECK (duracion_cita > 0)
    -- La no superposición de horarios del mismo médico se valida en PHP
);

CREATE TABLE cita (
    id_cita                SERIAL        PRIMARY KEY,
    id_paciente            INTEGER       NOT NULL,
    id_medico              INTEGER       NOT NULL,
    id_especialidad        INTEGER       NOT NULL,
    id_usuario_cancelacion INTEGER,
    codigo                 VARCHAR(12)   NOT NULL,        -- CIT-000001
    fecha                  DATE          NOT NULL,
    hora_inicio            TIME          NOT NULL,
    hora_fin               TIME          NOT NULL,
    motivo                 VARCHAR(200),
    estado                 VARCHAR(20)   NOT NULL DEFAULT 'Programada',
    fecha_creacion         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo_cancelacion     VARCHAR(200),
    fecha_cancelacion      TIMESTAMP,
    cancelada_por          VARCHAR(20),                   -- 'Sistema' (CU21); NULL si la canceló un usuario
    CONSTRAINT fk_cita_paciente     FOREIGN KEY (id_paciente)            REFERENCES paciente (id_paciente)         ON DELETE RESTRICT,
    CONSTRAINT fk_cita_medico       FOREIGN KEY (id_medico)              REFERENCES medico (id_medico)             ON DELETE RESTRICT,
    CONSTRAINT fk_cita_especialidad FOREIGN KEY (id_especialidad)        REFERENCES especialidad (id_especialidad) ON DELETE RESTRICT,
    CONSTRAINT fk_cita_usuario_canc FOREIGN KEY (id_usuario_cancelacion) REFERENCES usuario (id_usuario)           ON DELETE RESTRICT,
    CONSTRAINT uq_cita_codigo       UNIQUE (codigo),
    CONSTRAINT ck_cita_horas        CHECK (hora_fin > hora_inicio),
    CONSTRAINT ck_cita_estado       CHECK (estado IN ('Pendiente de pago', 'Programada', 'Confirmada', 'Atendida', 'Cancelada', 'Inasistencia'))
);

-- Un médico no puede tener dos citas vigentes en la misma fecha y hora.
-- Las citas canceladas no cuentan, así ese horario se puede volver a usar.
CREATE UNIQUE INDEX uq_cita_medico_horario
    ON cita (id_medico, fecha, hora_inicio)
    WHERE estado <> 'Cancelada';

-- ---------------------------------------------------------------------
-- 4a. Perfil médico y atención clínica (CU07 y CU18)
-- ---------------------------------------------------------------------
CREATE TABLE perfil_medico (
    id_perfil_medico                 SERIAL       PRIMARY KEY,
    id_paciente                      INTEGER      NOT NULL,
    tipo_sangre                      VARCHAR(3),
    alergias                         VARCHAR(500),
    enfermedades_cronicas            VARCHAR(500),
    medicamentos                     VARCHAR(500),
    nombre_contacto_emergencia       VARCHAR(500),
    parentesco_contacto_emergencia   VARCHAR(500),
    telefono_contacto_emergencia     VARCHAR(20),
    fecha_actualizacion              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_perfil_medico_paciente
        FOREIGN KEY (id_paciente) REFERENCES paciente (id_paciente) ON DELETE RESTRICT,
    CONSTRAINT uq_perfil_medico_paciente UNIQUE (id_paciente),
    CONSTRAINT ck_perfil_medico_tipo_sangre CHECK (
        tipo_sangre IS NULL
        OR tipo_sangre IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')
    ),
    CONSTRAINT ck_perfil_medico_contacto_completo CHECK (
        (
            NULLIF(BTRIM(nombre_contacto_emergencia), '') IS NULL
            AND NULLIF(BTRIM(parentesco_contacto_emergencia), '') IS NULL
            AND NULLIF(BTRIM(telefono_contacto_emergencia), '') IS NULL
        )
        OR
        (
            NULLIF(BTRIM(nombre_contacto_emergencia), '') IS NOT NULL
            AND NULLIF(BTRIM(parentesco_contacto_emergencia), '') IS NOT NULL
            AND NULLIF(BTRIM(telefono_contacto_emergencia), '') IS NOT NULL
        )
    ),
    CONSTRAINT ck_perfil_medico_telefono CHECK (
        telefono_contacto_emergencia IS NULL
        OR telefono_contacto_emergencia ~ '^[0-9+[:space:]-]{7,20}$'
    )
);

CREATE TABLE atencion_medica (
    id_atencion         SERIAL        PRIMARY KEY,
    id_cita             INTEGER       NOT NULL,
    id_medico           INTEGER       NOT NULL,
    diagnostico         VARCHAR(500)  NOT NULL,
    notas_clinicas      VARCHAR(2000),
    fecha_hora_registro TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_atencion_medica_cita
        FOREIGN KEY (id_cita) REFERENCES cita (id_cita) ON DELETE RESTRICT,
    CONSTRAINT fk_atencion_medica_medico
        FOREIGN KEY (id_medico) REFERENCES medico (id_medico) ON DELETE RESTRICT,
    CONSTRAINT uq_atencion_medica_cita UNIQUE (id_cita),
    CONSTRAINT ck_atencion_medica_diagnostico CHECK (BTRIM(diagnostico) <> '')
);

-- ---------------------------------------------------------------------
-- 4b. Pagos: dependen de la cita
-- ---------------------------------------------------------------------
CREATE TABLE pago (
    id_pago             SERIAL         PRIMARY KEY,
    id_cita             INTEGER        NOT NULL,
    id_usuario          INTEGER        NOT NULL,          -- quien registró el cobro (recepcionista o paciente)
    numero_comprobante  VARCHAR(12),                      -- CP-0001; se genera al quedar "Pagado"
    monto_total         NUMERIC(10,2)  NOT NULL,          -- se conserva aunque la tarifa cambie después
    fecha               TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado              VARCHAR(10)    NOT NULL DEFAULT 'Pendiente',
    CONSTRAINT fk_pago_cita        FOREIGN KEY (id_cita)    REFERENCES cita (id_cita)       ON DELETE RESTRICT,
    CONSTRAINT fk_pago_usuario     FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE RESTRICT,
    CONSTRAINT uq_pago_cita        UNIQUE (id_cita),      -- Cita 1 — 0..1 Pago
    CONSTRAINT uq_pago_comprobante UNIQUE (numero_comprobante),
    CONSTRAINT ck_pago_monto       CHECK (monto_total > 0),
    CONSTRAINT ck_pago_estado      CHECK (estado IN ('Pendiente', 'Pagado', 'Anulado')),
    CONSTRAINT ck_pago_pagado      CHECK (estado <> 'Pagado' OR numero_comprobante IS NOT NULL)
);

CREATE TABLE detalle_pago (
    id_detalle_pago     SERIAL         PRIMARY KEY,
    id_pago             INTEGER        NOT NULL,
    id_metodo_pago      INTEGER        NOT NULL,
    monto               NUMERIC(10,2)  NOT NULL,
    referencia_externa  VARCHAR(100),                     -- payment_intent de Stripe (solo en línea)
    id_sesion_pasarela  VARCHAR(255),                     -- id de la sesión de Stripe Checkout (solo en línea)
    estado              VARCHAR(10)    NOT NULL DEFAULT 'Pendiente',
    -- Composición: el detalle no existe sin su pago
    CONSTRAINT fk_detalle_pago_pago   FOREIGN KEY (id_pago)        REFERENCES pago (id_pago)               ON DELETE CASCADE,
    CONSTRAINT fk_detalle_pago_metodo FOREIGN KEY (id_metodo_pago) REFERENCES metodo_pago (id_metodo_pago) ON DELETE RESTRICT,
    CONSTRAINT ck_detalle_pago_monto  CHECK (monto > 0),
    CONSTRAINT ck_detalle_pago_estado CHECK (estado IN ('Pendiente', 'Aprobado', 'Rechazado', 'Anulado'))
);

CREATE TABLE movimiento_caja (
    id_movimiento_caja  SERIAL         PRIMARY KEY,
    id_sesion_caja      INTEGER        NOT NULL,
    id_detalle_pago     INTEGER        NOT NULL,          -- detalle en efectivo que lo generó
    tipo                VARCHAR(10)    NOT NULL DEFAULT 'Ingreso',
    monto               NUMERIC(10,2)  NOT NULL,
    fecha               TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Composición: el movimiento no existe sin su sesión de caja
    CONSTRAINT fk_movimiento_caja_sesion  FOREIGN KEY (id_sesion_caja)  REFERENCES sesion_caja (id_sesion_caja)   ON DELETE CASCADE,
    CONSTRAINT fk_movimiento_caja_detalle FOREIGN KEY (id_detalle_pago) REFERENCES detalle_pago (id_detalle_pago) ON DELETE RESTRICT,
    CONSTRAINT uq_movimiento_caja_detalle UNIQUE (id_detalle_pago),   -- DetallePago 1 — 0..1 MovimientoCaja
    CONSTRAINT ck_movimiento_caja_tipo    CHECK (tipo IN ('Ingreso')),
    CONSTRAINT ck_movimiento_caja_monto   CHECK (monto > 0)
);

CREATE TABLE notificacion (
    id_notificacion  SERIAL       PRIMARY KEY,
    id_cita          INTEGER      NOT NULL,
    tipo             VARCHAR(30)  NOT NULL,               -- p. ej. 'Cita programada'
    canal            VARCHAR(10)  NOT NULL,               -- 'Push' o 'Correo'
    programada_para  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    enviada_en       TIMESTAMP,
    estado           VARCHAR(10)  NOT NULL DEFAULT 'Pendiente',
    CONSTRAINT fk_notificacion_cita   FOREIGN KEY (id_cita) REFERENCES cita (id_cita) ON DELETE RESTRICT,
    CONSTRAINT ck_notificacion_canal  CHECK (canal IN ('Push', 'Correo')),
    CONSTRAINT ck_notificacion_estado CHECK (estado IN ('Pendiente', 'Enviada', 'Fallida'))
);

-- ---------------------------------------------------------------------
-- 5. Índices de apoyo para búsquedas frecuentes
-- ---------------------------------------------------------------------
CREATE INDEX ix_usuario_rol          ON usuario (id_rol);
CREATE INDEX ix_bitacora_fecha       ON bitacora (fecha_hora);
CREATE INDEX ix_bitacora_usuario     ON bitacora (id_usuario);
CREATE INDEX ix_horario_medico       ON horario_atencion (id_medico, dia_semana);
CREATE INDEX ix_cita_fecha           ON cita (fecha);
CREATE INDEX ix_cita_paciente        ON cita (id_paciente);
CREATE INDEX ix_cita_especialidad    ON cita (id_especialidad);
CREATE INDEX ix_cita_estado          ON cita (estado);
CREATE INDEX ix_usuario_apellidos    ON usuario (apellidos, nombres);
CREATE INDEX ix_cita_pendiente_pago  ON cita (fecha_creacion) WHERE estado = 'Pendiente de pago';
CREATE INDEX ix_atencion_medica_medico ON atencion_medica (id_medico);
CREATE INDEX ix_detalle_pago_pago    ON detalle_pago (id_pago);
CREATE INDEX ix_movimiento_caja_sesion ON movimiento_caja (id_sesion_caja);
CREATE INDEX ix_notificacion_cita    ON notificacion (id_cita);

-- ---------------------------------------------------------------------
-- 6. Datos iniciales
-- ---------------------------------------------------------------------
INSERT INTO rol (nombre, descripcion) VALUES
    ('Administrador', 'Configura y supervisa el sistema: usuarios, roles, médicos, especialidades y reportes'),
    ('Recepcionista', 'Gestiona pacientes y citas: programar, reprogramar, cancelar y registrar asistencia'),
    ('Medico',        'Consulta su agenda y registra la atención de sus citas'),
    ('Paciente',      'Consulta su información, su historial y sus citas');

INSERT INTO permiso (clave, nombre, descripcion) VALUES
    ('usuarios.gestionar',       'Gestionar usuarios',               'CU01: registrar, modificar, consultar, desactivar y desbloquear usuarios'),
    ('roles.gestionar',          'Gestionar roles y permisos',       'CU03: registrar roles y asignarles permisos'),
    ('bitacora.consultar',       'Consultar bitácora',               'CU05: ver las acciones registradas en la bitácora'),
    ('pacientes.gestionar',      'Gestionar pacientes',              'CU06: registrar, buscar, modificar y desactivar pacientes'),
    ('perfil_medico.gestionar',  'Gestionar perfil médico',          'CU07: registrar la información médica del paciente'),
    ('historial.consultar',      'Consultar historial de citas',     'CU08: ver el historial de citas de un paciente'),
    ('especialidades.gestionar', 'Gestionar especialidades',         'CU09: registrar, modificar y desactivar especialidades'),
    ('medicos.gestionar',        'Gestionar médicos',                'CU10: registrar, modificar y desactivar médicos'),
    ('horarios.gestionar',       'Gestionar horarios de atención',   'CU11: definir los horarios de atención de los médicos'),
    ('disponibilidad.consultar', 'Consultar disponibilidad',         'CU12: ver los horarios libres de un médico o especialidad'),
    ('citas.registrar',          'Registrar cita',                   'CU13: programar una nueva cita'),
    ('citas.reprogramar',        'Reprogramar cita',                 'CU14: cambiar la fecha u hora de una cita'),
    ('citas.cancelar',           'Cancelar cita',                    'CU15: cancelar una cita indicando el motivo'),
    ('citas.controlar_estado',   'Controlar estado y asistencia',    'CU16: confirmar citas y registrar asistencia o inasistencia'),
    ('agenda.consultar',         'Consultar agenda médica',          'CU17: ver las citas programadas por médico y fecha'),
    ('citas.registrar_atencion', 'Registrar atención de cita',       'CU18: marcar una cita como atendida'),
    ('reportes.generar',         'Generar reportes',                 'CU19: reportes de citas por fecha, médico, especialidad y estado'),
    ('pagos.configurar',         'Configurar tarifas y métodos de pago', 'CU20: registrar, modificar y desactivar tarifas; habilitar métodos de pago'),
    ('pagos.cobrar',             'Cobrar citas',                     'CU21: cobrar citas en efectivo, en línea o combinado'),
    ('pagos.pagar_propia',       'Pagar cita propia',                'CU21: el paciente paga en línea sus propias citas'),
    ('caja.gestionar',           'Gestionar caja',                   'CU22: abrir, consultar y cerrar la caja');

-- Métodos de pago que acepta la clínica (CU20 los habilita o deshabilita)
INSERT INTO metodo_pago (nombre, en_linea) VALUES
    ('Efectivo',      FALSE),
    ('Pago en línea', TRUE);

-- Permisos por rol, según el diagrama de casos de uso general.
-- El Administrador recibe TODOS los permisos (puede usar cualquier función del sistema).
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM rol r
JOIN permiso p ON
     (r.nombre = 'Administrador')
  OR (r.nombre = 'Recepcionista' AND p.clave IN (
         'pacientes.gestionar', 'historial.consultar', 'disponibilidad.consultar',
         'citas.registrar', 'citas.reprogramar', 'citas.cancelar',
         'citas.controlar_estado', 'agenda.consultar', 'reportes.generar',
         'pagos.cobrar', 'caja.gestionar'))
  OR (r.nombre = 'Medico' AND p.clave IN (
         'agenda.consultar', 'citas.registrar_atencion'))
  OR (r.nombre = 'Paciente' AND p.clave IN (
         'perfil_medico.gestionar', 'historial.consultar',
         'disponibilidad.consultar', 'citas.registrar', 'pagos.pagar_propia'));

-- Administrador inicial
--   usuario: admin   contraseña: Admin2026*   (cambiarla en el primer ingreso)
--   hash generado con password_hash('Admin2026*', PASSWORD_DEFAULT)
INSERT INTO usuario (id_rol, nombre_usuario, correo, contrasena, nombres, apellidos)
SELECT id_rol, 'admin', 'admin@vidasalud.com',
       '$2y$10$F1jYK7X0Paqf84fJ7hfRheLnjGb/8j4EHOu8Q36VDA6dzftGu5Ob6',
       'Administrador', 'del Sistema'
FROM rol WHERE nombre = 'Administrador';

COMMIT;
