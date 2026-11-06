-- =====================================================================
-- Clínica Vida Salud - Actualización: perfil médico y atención clínica
-- Casos de uso: CU07 Gestionar Perfil Médico y CU18 Registrar Atención
--
-- Ejecutar sobre una base existente que ya tenga el esquema principal:
--   psql -U postgres -d clinica_vida_salud -f actualizacion_ciclo2_perfil_medico_atencion.sql
--
-- No elimina ni modifica datos existentes. Se puede ejecutar más de una vez.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- CU07: perfil médico opcional, con un perfil como máximo por paciente
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS perfil_medico (
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

-- ---------------------------------------------------------------------
-- CU18: una sola atención clínica por cita
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS atencion_medica (
    id_atencion        SERIAL        PRIMARY KEY,
    id_cita            INTEGER       NOT NULL,
    id_medico          INTEGER       NOT NULL,
    diagnostico        VARCHAR(500)  NOT NULL,
    notas_clinicas     VARCHAR(2000),
    fecha_hora_registro TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_atencion_medica_cita
        FOREIGN KEY (id_cita) REFERENCES cita (id_cita) ON DELETE RESTRICT,
    CONSTRAINT fk_atencion_medica_medico
        FOREIGN KEY (id_medico) REFERENCES medico (id_medico) ON DELETE RESTRICT,
    CONSTRAINT uq_atencion_medica_cita UNIQUE (id_cita),
    CONSTRAINT ck_atencion_medica_diagnostico CHECK (BTRIM(diagnostico) <> '')
);

CREATE INDEX IF NOT EXISTS ix_atencion_medica_medico
    ON atencion_medica (id_medico);

COMMIT;
