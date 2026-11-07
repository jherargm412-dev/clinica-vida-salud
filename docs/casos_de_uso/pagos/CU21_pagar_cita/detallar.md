# CU21 Pagar Cita

| Campo | Contenido |
|---|---|
| Caso de uso | CU21 Pagar Cita |
| Paquete | pagos |
| Permiso | `pagos.cobrar` (Recepcionista), `pagos.pagar_propia` (Paciente) |
| Actores | Recepcionista, Paciente |
| Actor iniciador | Recepcionista o Paciente |

## Propósito
Cobrar el monto total de la tarifa para que la cita pase de "Pendiente de pago" a "Programada".

## Descripción
Permite cobrar una cita que quedó en "Pendiente de pago", con el horario reservado durante 10 minutos. El recepcionista puede cobrar solo en efectivo, solo con pago en línea o combinando ambos. El paciente paga solo en línea. El pago en línea se procesa con la pasarela Stripe (modo de prueba) y se verifica con ella antes de aprobarlo. Al completarse el pago se genera el comprobante, la cita queda "Programada" y el efectivo ingresa a la caja. Es incluido por CU13 Registrar Cita.

## Precondición
El actor ha iniciado sesión. La cita está en "Pendiente de pago" desde hace menos de 10 minutos. La especialidad tiene una tarifa vigente y hay al menos un método de pago activo.

## Flujo principal
1. El recepcionista selecciona "Pagar" en la cita pendiente de pago. El sistema muestra el resumen de la cita (código, paciente, especialidad, médico, fecha y hora), el monto total según la tarifa vigente, el tiempo restante y los métodos de pago activos.
2. El recepcionista elige la forma de cobro: solo Efectivo, solo Pago en línea, o Efectivo + Pago en línea (combinado). El sistema registra el pago en estado "Pendiente" por el monto total.
3. Si incluye efectivo, el recepcionista ingresa el monto recibido. El sistema verifica que tenga una caja abierta y que el monto sea válido. Registra el detalle de efectivo en estado "Pendiente" y muestra el saldo.
4. Si queda saldo, el recepcionista selecciona "Pagar en línea". El sistema registra el detalle en línea en estado "Pendiente" por el saldo, crea la sesión de pago en Stripe y muestra el formulario de pago de Stripe dentro de la pantalla de pago.
5. El actor ingresa los datos de su tarjeta en el formulario de Stripe. Stripe aprueba el pago y lo devuelve al sistema.
6. El sistema verifica el pago con Stripe y guarda la referencia externa.
7. El sistema verifica que el tiempo siga vigente y que la suma de los detalles sea igual al total. Luego marca los detalles como "Aprobado", genera el número de comprobante (CP-0001), marca el pago como "Pagado" y cambia la cita a "Programada".
8. Si hubo efectivo, el sistema registra un movimiento "Ingreso" en la caja abierta por ese monto. Además, registra la acción en la bitácora y envía al paciente el aviso de cita programada (RF29).
9. El sistema muestra el comprobante (número, fecha, datos de la cita, monto por método y total) con la opción de imprimirlo.

## Flujo alternativo
- **2a.** Si el actor es el Paciente, solo se ofrece Pago en línea por el total: el sistema registra el pago en estado "Pendiente" y el caso continúa en el paso 4.
- **2b.** Si elige solo Pago en línea, no se ingresa efectivo y el caso continúa en el paso 4 por el total.
- **3a.** Si no tiene una caja abierta, el sistema muestra "Abra la caja para registrar pagos en efectivo" y el recepcionista puede elegir solo Pago en línea (2b).
- **3b.** Si el monto es menor o igual a 0, o mayor al total, el sistema muestra "El monto en efectivo debe ser mayor a 0 y no superar el total (Bs X)" y vuelve al paso 3.
- **3c.** Si eligió solo Efectivo, el monto debe ser igual al total; si no lo es, el sistema muestra "El monto debe ser igual al total (Bs X)". Si es igual, el recepcionista presiona "Confirmar cobro" y el caso continúa en el paso 7.
- **5a.** Si la tarjeta es rechazada, Stripe lo informa en el mismo formulario y el actor puede intentar con otra tarjeta. Si el actor decide no pagar en línea, el detalle en línea pasa a "Rechazado" y el sistema muestra "El pago en línea no fue aprobado". El efectivo se conserva y el actor puede reintentar (paso 4) o completar el saldo en efectivo (paso 3, solo el recepcionista).
- **6a.** Si Stripe no confirma el pago al verificarlo, el caso sigue igual que en 5a.
- **7a.** Si vence el tiempo de 10 minutos, el sistema muestra "El tiempo para pagar venció; el horario fue liberado". La cita pasa a "Cancelada" con el motivo "Pago no realizado a tiempo" y canceladaPor "Sistema", y el pago pasa a "Anulado". El efectivo se devuelve en el momento y su detalle queda "Anulado". El caso de uso termina.
- **8a.** Si el aviso push falla o el paciente no tiene una suscripción activa, se envía por correo. Si también falla, la notificación queda "Fallida", sin revertir el pago. Además se programa el recordatorio 24 h antes de la cita (RF31), salvo que falten menos de 24 h.
- **Cancelar.** Si el actor cancela antes del paso 7, el efectivo se devuelve en el momento, los detalles y el pago quedan "Anulado", la cita pasa a "Cancelada" con canceladaPor "Sistema" y el horario se libera.

## Postcondición
El pago queda "Pagado" con un número de comprobante único y sus detalles "Aprobado". El pago en línea queda verificado con Stripe. El efectivo queda como movimiento "Ingreso" en la caja abierta. La cita queda "Programada" y ocupa el horario. La acción queda en la bitácora y se envía el aviso al paciente. Si no se paga dentro de los 10 minutos o se cancela, la cita queda "Cancelada", el horario libre y el efectivo devuelto.
