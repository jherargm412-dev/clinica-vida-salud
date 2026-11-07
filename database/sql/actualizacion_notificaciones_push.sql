-- =====================================================================
-- Clínica Vida Salud - Actualización: notificaciones push (RF30 y RF31)
-- Casos de uso: CU04 Configurar perfil (activar/desactivar push) y avisos de
-- CU21 (cita programada), CU14 (reprogramada), CU15 (cancelada) y recordatorio 24 h.
--
-- Ejecutar sobre una base existente que ya tenga el esquema principal:
--   psql -U postgres -d clinica_vida_salud -f actualizacion_notificaciones_push.sql
--
-- No elimina ni modifica datos existentes. Se puede ejecutar más de una vez.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- RF30: una fila por cada navegador o dispositivo donde el paciente activó las notificaciones.
-- Los tres datos (endpoint, clave_p256dh, clave_auth) los entrega el navegador al suscribirse;
-- con ellos el servidor puede enviarle avisos cifrados aunque la página esté cerrada.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS suscripcion_push (
    id_suscripcion   SERIAL        PRIMARY KEY,
    id_usuario       INTEGER       NOT NULL,
    endpoint         TEXT          NOT NULL,              -- dirección del servicio push del navegador (Google, Mozilla o Apple)
    clave_p256dh     VARCHAR(255)  NOT NULL,              -- clave pública del navegador para cifrar el mensaje
    clave_auth       VARCHAR(255)  NOT NULL,              -- secreto de autenticación del navegador
    dispositivo      VARCHAR(100),                        -- descripción legible, p. ej. "Chrome en Windows"
    activo           BOOLEAN       NOT NULL DEFAULT TRUE, -- FALSE: el paciente la desactivó o el navegador ya no la acepta
    fecha_registro   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_suscripcion_usuario  FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE RESTRICT,
    CONSTRAINT uq_suscripcion_endpoint UNIQUE (endpoint)
);

CREATE INDEX IF NOT EXISTS ix_suscripcion_usuario ON suscripcion_push (id_usuario) WHERE activo;

-- ---------------------------------------------------------------------
-- RF31: nuevo estado "Anulada" para los recordatorios de 24 h que ya no deben enviarse
-- (la cita se canceló o se reprogramó y se creó un recordatorio nuevo).
-- ---------------------------------------------------------------------
ALTER TABLE notificacion DROP CONSTRAINT IF EXISTS ck_notificacion_estado;
ALTER TABLE notificacion ADD CONSTRAINT ck_notificacion_estado
    CHECK (estado IN ('Pendiente', 'Enviada', 'Fallida', 'Anulada'));

-- Un solo recordatorio pendiente por cita
CREATE UNIQUE INDEX IF NOT EXISTS uq_notificacion_recordatorio_pendiente
    ON notificacion (id_cita) WHERE tipo = 'Recordatorio' AND estado = 'Pendiente';

-- Para que el comando del scheduler encuentre rápido los avisos que ya toca enviar
CREATE INDEX IF NOT EXISTS ix_notificacion_pendiente
    ON notificacion (programada_para) WHERE estado = 'Pendiente';

COMMIT;
