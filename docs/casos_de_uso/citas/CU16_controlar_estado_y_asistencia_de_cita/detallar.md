# CU16 Controlar Estado y Asistencia de Cita

| Campo | Contenido |
|---|---|
| Caso de uso | CU16 Controlar Estado y Asistencia de Cita |
| Paquete | citas |
| Permiso | `citas.controlar_estado` |
| Actor | Recepcionista |

## Propósito
Confirmar citas y registrar si el paciente fue atendido o no se presentó.

## Flujo principal
1. La recepcionista abre "Controlar estado y asistencia".
2. El sistema lista citas Programadas y Confirmadas.
3. La recepcionista busca por código de cita, CI, nombre o apellido del paciente, y opcionalmente por fecha.
4. Selecciona una cita para consultar sus datos y las acciones disponibles.
5. Elige confirmar, registrar como atendida o registrar inasistencia.
6. El servidor valida la transición y, para Atención/Inasistencia, que la cita ya haya comenzado.
7. El sistema guarda el estado y registra `CAMBIAR_ESTADO_CITA` en la bitácora.

## Reglas y alternativas
- Programada puede pasar a Confirmada, Atendida o Inasistencia.
- Confirmada puede pasar a Atendida o Inasistencia.
- Atendida e Inasistencia no se pueden registrar antes del inicio de la cita, ni siquiera si se manipula la petición.
- Cada acción requiere confirmación en la interfaz.
- Las citas Pendientes de pago, Canceladas, Atendidas o con Inasistencia no admiten nuevas transiciones en este caso de uso.
- Una transición inválida deja la cita sin cambios y muestra un mensaje.

## Postcondición
La cita conserva su identidad, queda en el estado permitido y la operación se registra en la bitácora.
