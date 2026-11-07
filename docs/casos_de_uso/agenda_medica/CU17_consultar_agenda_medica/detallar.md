# CU17 Consultar Agenda Médica

| Campo | Contenido |
|---|---|
| Caso de uso | CU17 Consultar Agenda Médica |
| Paquete | agenda_medica |
| Permiso | `agenda.consultar` |
| Actores | Administrador, recepcionista y médico con ficha asociada |

## Propósito
Consultar las citas de un médico por fecha y estado, mostrando solo la información permitida al usuario.

## Flujo principal
1. Un administrador o recepcionista abre "Agenda médica", selecciona un médico activo y consulta su agenda.
2. Un usuario asociado a un médico accede directamente a la agenda de ese médico, sin poder elegir otro.
3. El sistema muestra por defecto la fecha actual y permite filtrar por fecha y estado.
4. El sistema presenta las citas ordenadas por fecha y hora con código, horario, paciente, especialidad y estado.
5. El motivo se muestra únicamente cuando el usuario tiene `citas.registrar_atencion`.

## Reglas y alternativas
- El acceso se decide por el permiso y la existencia de una ficha médica asociada, no por el nombre del rol.
- Si no se indica fecha, se consulta la fecha actual; una fecha inválida o un estado desconocido se rechazan.
- Se incluyen las citas Pendientes de pago solo mientras su reserva de diez minutos siga vigente.
- Sin resultados, el sistema informa que no hay citas para los criterios seleccionados.
- El listado de médicos para selección solo incluye médicos y cuentas activos.

## Postcondición
Se muestra la agenda autorizada sin modificar citas ni exponer el motivo a usuarios sin permiso.
