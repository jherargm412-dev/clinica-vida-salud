-- =====================================================================
-- Clínica Vida Salud - Actualización: paquete ASISTENCIA_USUARIO
-- Casos de uso: CU23 Gestionar preguntas frecuentes, CU24 Consultar asistente
--               virtual (chatbot basado en reglas) y CU25 Navegar por comando de voz.
--
-- Para una base que YA existe (local o Railway): crea la tabla pregunta_frecuente,
-- agrega los permisos nuevos y carga las preguntas frecuentes iniciales SIN borrar
-- datos. Se puede ejecutar más de una vez.
--
-- Uso:  psql -U postgres -d clinica_vida_salud -f actualizacion_asistencia_usuario.sql
--
-- En una base nueva no hace falta: clinica_vida_salud.sql ya lo incluye.
-- =====================================================================

-- El archivo está guardado en UTF-8: así las tildes y la ñ se guardan bien
-- aunque el programa que lo ejecute use otra codificación (pasó en Railway).
SET client_encoding = 'UTF8';

BEGIN;

-- ---------------------------------------------------------------------
-- 1. Tabla de preguntas frecuentes (CU23). El asistente (CU24) usa solo las activas.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pregunta_frecuente (
    id_pregunta_frecuente SERIAL        PRIMARY KEY,
    categoria             VARCHAR(20)   NOT NULL,
    pregunta              VARCHAR(200)  NOT NULL,
    respuesta             VARCHAR(1000) NOT NULL,
    palabras_clave        VARCHAR(300)  NOT NULL,              -- separadas por comas, en minúsculas
    activo                BOOLEAN       NOT NULL DEFAULT TRUE,
    fecha_registro        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_pregunta_frecuente_categoria
        CHECK (categoria IN ('Citas', 'Pagos', 'Notificaciones', 'Mi cuenta', 'La clínica'))
);

-- No se repite el texto de una pregunta (sin distinguir mayúsculas). El sistema además
-- compara sin tildes antes de guardar.
CREATE UNIQUE INDEX IF NOT EXISTS uq_pregunta_frecuente_pregunta ON pregunta_frecuente (LOWER(pregunta));
CREATE INDEX IF NOT EXISTS ix_pregunta_frecuente_categoria ON pregunta_frecuente (categoria) WHERE activo;

-- ---------------------------------------------------------------------
-- 2. Permisos nuevos
-- ---------------------------------------------------------------------
INSERT INTO permiso (clave, nombre, descripcion) VALUES
    ('faq.gestionar',       'Gestionar preguntas frecuentes', 'CU23: registrar, modificar, desactivar y activar preguntas frecuentes'),
    ('asistente.consultar', 'Consultar asistente virtual',    'CU24: hacer preguntas al chatbot de la clínica'),
    ('voz.usar',            'Usar comandos de voz',           'CU25: buscar y abrir pantallas diciendo un comando de voz')
ON CONFLICT (clave) DO NOTHING;

-- Administrador: todos. Paciente: asistente virtual. Todos los roles: comandos de voz.
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM rol r
JOIN permiso p ON p.clave IN ('faq.gestionar', 'asistente.consultar', 'voz.usar')
WHERE r.nombre = 'Administrador'
   OR (r.nombre = 'Paciente' AND p.clave IN ('asistente.consultar', 'voz.usar'))
   OR (r.nombre IN ('Recepcionista', 'Medico') AND p.clave = 'voz.usar')
ON CONFLICT (id_rol, id_permiso) DO NOTHING;

-- ---------------------------------------------------------------------
-- 3. Preguntas frecuentes iniciales (el administrador las cambia en CU23).
--    Los datos de "La clínica" (dirección, horario y teléfono) son DE EJEMPLO.
-- ---------------------------------------------------------------------
INSERT INTO pregunta_frecuente (categoria, pregunta, respuesta, palabras_clave) VALUES
    -- Citas
    ('Citas', '¿Cómo saco una cita?',
     'Ingrese a "Registrar cita", elija la especialidad, el médico, la fecha y un horario libre, y confirme el pago. La cita queda programada al completar el pago.',
     'sacar, saco, reservar, reservo, agendar, agendo, nueva cita, registrar cita, pedir cita, turno, ficha'),
    ('Citas', '¿Cómo veo mis citas?',
     'En "Mis citas" encontrará todas sus citas con su fecha, hora, médico, especialidad y estado.',
     'mis citas, ver citas, ver mis citas, historial, proximas citas, consultar citas'),
    ('Citas', '¿Puedo cambiar la fecha de mi cita?',
     'Sí, sin costo, si lo solicita en recepción con 24 horas o más de anticipación.',
     'reprogramar, reprogramo, cambiar fecha, cambiar hora, cambiar cita, cambiar mi cita, mover cita, postergar, otro dia'),
    ('Citas', '¿Cómo cancelo mi cita?',
     'Comuníquese con recepción. Recuerde que, si cancela una cita pagada, el pago no se devuelve.',
     'cancelar, cancelo, anular, anulo, no puedo ir, no podre ir, suspender'),
    ('Citas', '¿Qué significa el estado de mi cita?',
     'Pendiente de pago: falta pagarla. Programada: pagada y reservada. Confirmada: la clínica confirmó su asistencia. Atendida: ya fue atendido. Cancelada: fue anulada. Inasistencia: no asistió.',
     'estado, estados, pendiente, programada, confirmada, atendida, cancelada, significa'),
    ('Citas', '¿Qué pasa si no asisto a mi cita?',
     'La cita se marca como "Inasistencia" y el pago no se devuelve. Si no puede asistir, solicite reprogramarla con 24 horas de anticipación.',
     'no asisto, no asisti, faltar, falte, inasistencia, no fui, no voy'),
    ('Citas', '¿Puedo tener dos citas a la misma hora?',
     'No. El sistema no permite registrar dos citas suyas en el mismo horario.',
     'dos citas, misma hora, mismo horario, otra cita'),
    -- Pagos
    ('Pagos', '¿Cómo pago mi cita?',
     'Desde el sistema puede pagar en línea con tarjeta. En recepción también puede pagar en efectivo o combinando efectivo y tarjeta.',
     'pagar, pago, pagos, pagar cita, tarjeta, como pago, metodos de pago, formas de pago'),
    ('Pagos', '¿Cuánto tiempo tengo para pagar?',
     'El horario queda reservado 10 minutos. Si no paga en ese tiempo, la cita se cancela y el horario se libera.',
     'tiempo, minutos, plazo, reserva, cuanto tiempo, vence, vencio'),
    ('Pagos', '¿Me devuelven el dinero?',
     'La clínica no realiza devoluciones. Si la clínica no puede atenderlo, su cita se reprograma sin costo.',
     'devolucion, devoluciones, devolver, devuelven, reembolso, reembolsar, dinero'),
    ('Pagos', '¿Dónde veo mi comprobante?',
     'En "Pagar cita", pestaña "Pagadas", puede abrir e imprimir el comprobante de cada cita.',
     'comprobante, comprobantes, recibo, factura, imprimir comprobante, constancia'),
    ('Pagos', '¿Puedo pagar en efectivo desde el sistema?',
     'No. El pago en efectivo solo se realiza en recepción.',
     'efectivo, cash, pagar en efectivo, billete'),
    -- Notificaciones
    ('Notificaciones', '¿Cómo activo las notificaciones?',
     'En "Mi perfil", sección "Notificaciones", presione "Activar en este dispositivo". Hágalo en cada celular o computadora donde quiera recibirlas.',
     'notificaciones, notificacion, avisos, alertas, activar, activar notificaciones'),
    ('Notificaciones', '¿Qué avisos voy a recibir?',
     'Un aviso cuando su cita quede programada, reprogramada o cancelada, y un recordatorio 24 horas antes de la cita.',
     'que avisos, recordatorio, recordatorios, me avisan, mensajes'),
    ('Notificaciones', 'No me llegan notificaciones en iPhone',
     'En iPhone, primero agregue la página a la pantalla de inicio (Compartir > "Agregar a inicio") y active las notificaciones desde allí. Si no tiene notificaciones activas, los avisos le llegan por correo.',
     'iphone, ipad, apple, ios, no llegan, no me llegan'),
    -- Mi cuenta
    ('Mi cuenta', 'Olvidé mi contraseña',
     'En la pantalla de inicio de sesión presione "¿Olvidó su contraseña?". Le llegará al correo un enlace válido por 30 minutos para crear una nueva.',
     'olvide, olvido, contrasena, clave, recuperar, restablecer, password'),
    ('Mi cuenta', 'Mi cuenta está bloqueada',
     'La cuenta se bloquea tras 3 intentos fallidos. Restablezca su contraseña con "¿Olvidó su contraseña?" o comuníquese con la clínica.',
     'bloqueada, bloqueo, bloquearon, no puedo entrar, no puedo ingresar, intentos'),
    ('Mi cuenta', '¿Cómo cambio mis datos?',
     'En "Mi perfil" puede actualizar su nombre, apellidos, correo y contraseña. Para corregir su CI u otros datos de registro, acuda a recepción.',
     'cambiar datos, actualizar datos, mis datos, correo, nombre, editar perfil, mi perfil'),
    ('Mi cuenta', '¿Qué requisitos tiene la contraseña?',
     'Al menos 8 caracteres, con una mayúscula, una minúscula, un número y un carácter especial (por ejemplo: Clave123!).',
     'requisitos contrasena, contrasena segura, caracteres, mayuscula, nueva contrasena'),
    ('Mi cuenta', '¿Para qué sirve mi perfil médico?',
     'Ahí registra su tipo de sangre, alergias, enfermedades crónicas, medicamentos y un contacto de emergencia. Su médico lo consulta al atenderlo.',
     'perfil medico, alergias, alergia, tipo de sangre, sangre, enfermedades, medicamentos, contacto de emergencia'),
    -- La clínica (datos de ejemplo)
    ('La clínica', '¿Dónde están ubicados?',
     'La clínica se encuentra en la Av. Principal N° 100, zona Centro.',
     'direccion, ubicacion, ubicados, ubicada, donde estan, donde queda, donde quedan, quedan, llegar, mapa'),
    ('La clínica', '¿Cuál es el horario de atención?',
     'Atendemos de lunes a viernes de 07:00 a 20:00 y los sábados de 08:00 a 13:00. El horario de cada médico se ve al registrar una cita.',
     'horario, horarios, atienden, abren, cierran, hora de atencion, sabado, domingo'),
    ('La clínica', '¿Cuál es el teléfono de contacto?',
     'Puede comunicarse con recepción al teléfono 3-3000000 o al WhatsApp 70000000.',
     'telefono, contacto, llamar, whatsapp, numero, celular, comunicarme'),
    ('La clínica', '¿Qué debo llevar a mi consulta?',
     'Traiga su carnet de identidad y, si los tiene, sus estudios o recetas anteriores. Llegue 10 minutos antes de su horario.',
     'llevar, traer, documentos, carnet, que necesito, requisitos consulta')
ON CONFLICT ((LOWER(pregunta))) DO NOTHING;

COMMIT;
