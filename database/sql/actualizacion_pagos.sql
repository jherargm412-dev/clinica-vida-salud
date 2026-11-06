-- =====================================================================
-- Clínica Vida Salud - Actualización: paquete PAGOS (CU20, CU21, CU22)
--
-- Para una base que YA existe (local o Railway): agrega las tablas y
-- cambios de pagos SIN borrar datos. Se puede ejecutar más de una vez.
--
-- Uso:  psql -U postgres -d clinica_vida_salud -f actualizacion_pagos.sql
--
-- En una base nueva no hace falta: clinica_vida_salud.sql ya lo incluye.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. Cita: nuevo estado "Pendiente de pago" y quién la canceló
-- ---------------------------------------------------------------------
-- "Pendiente de pago" tiene 17 caracteres: el estado pasa de 15 a 20.
ALTER TABLE cita ALTER COLUMN estado TYPE VARCHAR(20);
ALTER TABLE cita DROP CONSTRAINT IF EXISTS ck_cita_estado;
ALTER TABLE cita ADD CONSTRAINT ck_cita_estado
    CHECK (estado IN ('Pendiente de pago', 'Programada', 'Confirmada', 'Atendida', 'Cancelada', 'Inasistencia'));

-- 'Sistema' cuando la cancela el sistema (CU21: pago no realizado a tiempo o cancelado).
-- NULL cuando la cancela un usuario (ver id_usuario_cancelacion).
ALTER TABLE cita ADD COLUMN IF NOT EXISTS cancelada_por VARCHAR(20);

-- Búsqueda rápida de reservas vencidas (CU21, 7a)
CREATE INDEX IF NOT EXISTS ix_cita_pendiente_pago
    ON cita (fecha_creacion) WHERE estado = 'Pendiente de pago';

-- ---------------------------------------------------------------------
-- 2. Tablas de pagos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tarifa (
    id_tarifa        SERIAL         PRIMARY KEY,
    id_especialidad  INTEGER        NOT NULL,
    monto            NUMERIC(10,2)  NOT NULL,
    vigente_desde    DATE           NOT NULL DEFAULT CURRENT_DATE,
    activo           BOOLEAN        NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_tarifa_especialidad FOREIGN KEY (id_especialidad) REFERENCES especialidad (id_especialidad) ON DELETE RESTRICT,
    CONSTRAINT ck_tarifa_monto        CHECK (monto > 0)
);
-- Una sola tarifa activa por especialidad (las inactivas quedan como historial)
CREATE UNIQUE INDEX IF NOT EXISTS uq_tarifa_activa_especialidad
    ON tarifa (id_especialidad) WHERE activo = TRUE;

CREATE TABLE IF NOT EXISTS metodo_pago (
    id_metodo_pago  SERIAL       PRIMARY KEY,
    nombre          VARCHAR(30)  NOT NULL,
    en_linea        BOOLEAN      NOT NULL DEFAULT FALSE,
    activo          BOOLEAN      NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_metodo_pago_nombre UNIQUE (nombre)
);

CREATE TABLE IF NOT EXISTS sesion_caja (
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
CREATE UNIQUE INDEX IF NOT EXISTS uq_sesion_caja_abierta
    ON sesion_caja (id_usuario) WHERE estado = 'Abierta';

CREATE TABLE IF NOT EXISTS pago (
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

CREATE TABLE IF NOT EXISTS detalle_pago (
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
CREATE INDEX IF NOT EXISTS ix_detalle_pago_pago ON detalle_pago (id_pago);

CREATE TABLE IF NOT EXISTS movimiento_caja (
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
CREATE INDEX IF NOT EXISTS ix_movimiento_caja_sesion ON movimiento_caja (id_sesion_caja);

CREATE TABLE IF NOT EXISTS notificacion (
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
CREATE INDEX IF NOT EXISTS ix_notificacion_cita ON notificacion (id_cita);

-- ---------------------------------------------------------------------
-- 3. Datos iniciales de pagos
-- ---------------------------------------------------------------------
INSERT INTO metodo_pago (nombre, en_linea) VALUES
    ('Efectivo',      FALSE),
    ('Pago en línea', TRUE)
ON CONFLICT (nombre) DO NOTHING;

INSERT INTO permiso (clave, nombre, descripcion) VALUES
    ('pagos.configurar',   'Configurar tarifas y métodos de pago', 'CU20: registrar, modificar y desactivar tarifas; habilitar métodos de pago'),
    ('pagos.cobrar',       'Cobrar citas',                         'CU21: cobrar citas en efectivo, en línea o combinado'),
    ('pagos.pagar_propia', 'Pagar cita propia',                    'CU21: el paciente paga en línea sus propias citas'),
    ('caja.gestionar',     'Gestionar caja',                       'CU22: abrir, consultar y cerrar la caja')
ON CONFLICT (clave) DO NOTHING;

-- El Administrador recibe todos los permisos; Recepcionista cobra y maneja la caja; Paciente paga sus citas.
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM rol r
JOIN permiso p ON p.clave IN ('pagos.configurar', 'pagos.cobrar', 'pagos.pagar_propia', 'caja.gestionar')
WHERE r.nombre = 'Administrador'
   OR (r.nombre = 'Recepcionista' AND p.clave IN ('pagos.cobrar', 'caja.gestionar'))
   OR (r.nombre = 'Paciente'      AND p.clave = 'pagos.pagar_propia')
ON CONFLICT (id_rol, id_permiso) DO NOTHING;

COMMIT;
