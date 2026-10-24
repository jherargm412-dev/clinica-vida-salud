# Arquitectura de paquetes

| Paquete | Casos de uso | Actores |
|---|---|---|
| `usuarios_acceso` | CU01, CU02, CU03, CU04, CU05 | Administrador; Usuario (CU02, CU04) |
| `pacientes` | CU06, CU07, CU08 | Recepcionista, Paciente |
| `medicos_especialidades` | CU09, CU10, CU11 | Administrador |
| `citas` | CU12, CU13, CU14, CU15, CU16 (CU13 y CU14 incluyen CU12) | Recepcionista, Paciente |
| `agenda_medica` | CU17, CU18 | Médico; Administrador y Recepcionista (CU17) |
| `reportes` | CU19 | Administrador, Recepcionista |

## Descripción de cada paquete

- **usuarios_acceso:** Módulo responsable de controlar el acceso al sistema y la seguridad de la información. Encapsula los procesos de inicio de sesión, administración de cuentas de usuario, asignación de roles y permisos, gestión del perfil propio y consulta de la bitácora de auditoría. Operado principalmente por el actor Administrador; todos los usuarios inician sesión y gestionan su perfil.
- **pacientes:** Módulo responsable de administrar la información de los pacientes de la clínica para facilitar su atención y seguimiento. Encapsula los procesos de registro, búsqueda, modificación y desactivación de pacientes, la gestión de su perfil médico y la consulta de su historial de citas. Operado principalmente por los actores Recepcionista y Paciente.
- **medicos_especialidades:** Módulo responsable de administrar la oferta de atención médica de la clínica. Encapsula los procesos de registro de especialidades, registro de médicos con sus especialidades y definición de sus horarios de atención. Operado principalmente por el actor Administrador.
- **citas:** Módulo responsable de coordinar la programación de las citas médicas, aprovechando la disponibilidad de los médicos y evitando conflictos de horario. Encapsula los procesos de consulta de disponibilidad, registro, reprogramación y cancelación de citas, y el control de su estado y de la asistencia del paciente. Operado principalmente por los actores Recepcionista y Paciente.
- **agenda_medica:** Módulo responsable de organizar la jornada de atención de los médicos. Encapsula los procesos de consulta de la agenda de citas por médico y fecha, y el registro de la atención de cada cita. Operado principalmente por el actor Médico, con consulta del Administrador y la Recepcionista.
- **reportes:** Módulo responsable de consolidar la información de las citas para apoyar la toma de decisiones administrativas. Encapsula los procesos de generación de reportes de citas por fecha, médico, especialidad y estado. Operado principalmente por los actores Administrador y Recepcionista.

## Diagramas

| Archivo | Diagrama |
|---|---|
| `casos_de_uso_general.puml` | Diagrama general de casos de uso |
| `3.1.1_identificar_paquetes.puml` | Identificar paquetes |
| `3.1.2_relacionar_<paquete>.puml` | Relacionar paquete y casos de uso (`<<trace>>`) |
| `3.1.3_vista_<paquete>.puml` | Vista del paquete con sus actores |

## Correspondencia con el código

`app/Modulos/<paquete>/`, `resources/views/<paquete>/`, `routes/modulos/<paquete>.php`.
