-- =====================================================================
-- Clínica Vida Salud - Actualización: paquete REPORTES (CU19)
--
-- Para una base que YA existe (local o Railway): agrega el permiso
-- "reportes.generar_todos" SIN borrar datos. Se puede ejecutar más de una vez.
-- No crea tablas: los reportes solo consultan las que ya existen.
--
-- Uso:  psql -U postgres -d clinica_vida_salud -f actualizacion_reportes.sql
--
-- En una base nueva no hace falta: clinica_vida_salud.sql ya lo incluye.
-- =====================================================================

BEGIN;

-- reportes.generar        (ya existía): Reporte de citas por fecha y estado + Agenda diaria.
-- reportes.generar_todos  (nuevo):      los 6 reportes con todos sus filtros.
UPDATE permiso
   SET descripcion = 'CU19: reporte de citas (por fecha y estado) y agenda diaria'
 WHERE clave = 'reportes.generar';

INSERT INTO permiso (clave, nombre, descripcion) VALUES
    ('reportes.generar_todos', 'Generar todos los reportes',
     'CU19: además asistencia, pacientes, ingresos, cierres de caja y todos los filtros')
ON CONFLICT (clave) DO NOTHING;

-- Solo el Administrador ve todos los reportes (el Recepcionista conserva reportes.generar).
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM rol r
JOIN permiso p ON p.clave = 'reportes.generar_todos'
WHERE r.nombre = 'Administrador'
ON CONFLICT (id_rol, id_permiso) DO NOTHING;

COMMIT;
