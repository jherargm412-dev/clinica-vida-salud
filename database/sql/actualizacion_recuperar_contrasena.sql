-- =====================================================================
-- Clínica Vida Salud - Actualización: recuperar contraseña por correo
-- Caso de uso: CU02 Iniciar Sesión (flujo alternativo "Recuperar contraseña")
--
-- Ejecutar sobre una base existente que ya tenga el esquema principal:
--   psql -U postgres -d clinica_vida_salud -f actualizacion_recuperar_contrasena.sql
--
-- No elimina ni modifica datos existentes. Se puede ejecutar más de una vez.
-- =====================================================================

BEGIN;

-- Cada solicitud de "¿Olvidó su contraseña?" genera un enlace de un solo uso.
-- En la tabla se guarda solo el HASH (SHA-256) del token; el token real viaja únicamente
-- en el enlace del correo, así que quien lea la base no puede usar los enlaces.
CREATE TABLE IF NOT EXISTS recuperacion_contrasena (
    id_recuperacion   SERIAL       PRIMARY KEY,
    id_usuario        INTEGER      NOT NULL,
    token             VARCHAR(64)  NOT NULL,              -- hash SHA-256 del token (64 caracteres hexadecimales)
    fecha_solicitud   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion  TIMESTAMP    NOT NULL,              -- 30 minutos después de la solicitud
    usado             BOOLEAN      NOT NULL DEFAULT FALSE, -- TRUE: ya se usó o fue reemplazado por uno nuevo
    CONSTRAINT fk_recuperacion_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE RESTRICT,
    CONSTRAINT uq_recuperacion_token   UNIQUE (token),
    CONSTRAINT ck_recuperacion_fechas  CHECK (fecha_expiracion > fecha_solicitud)
);

CREATE INDEX IF NOT EXISTS ix_recuperacion_usuario ON recuperacion_contrasena (id_usuario);

COMMIT;
