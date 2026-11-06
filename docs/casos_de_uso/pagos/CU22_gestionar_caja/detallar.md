# CU22 Gestionar Caja

| Campo | Contenido |
|---|---|
| Caso de uso | CU22 Gestionar Caja |
| Paquete | pagos |
| Permiso | `caja.gestionar` |
| Actores | Recepcionista |
| Actor iniciador | Recepcionista |

## Propósito
Controlar el efectivo que recibe el recepcionista: abrir la caja, consultar sus ingresos y cerrarla comparando el efectivo contado con el esperado.

## Descripción
El recepcionista abre su caja con el efectivo inicial, consulta los ingresos en efectivo y la cierra ingresando el efectivo que contó. Los ingresos se registran automáticamente cuando se completa un pago con efectivo (CU21). Al cerrar, el sistema compara el monto contado con el monto esperado (monto inicial + ingresos) y muestra si hay sobrante, faltante o si cuadra. Sin caja abierta no se aceptan pagos en efectivo. Cada apertura y cierre quedan en la bitácora.

## Precondición
El recepcionista ha iniciado sesión y tiene permiso para gestionar la caja.

## Flujo principal
1. El recepcionista selecciona "Gestionar caja".
2. El sistema verifica que el recepcionista no tenga una caja abierta y muestra el formulario de apertura.
3. El recepcionista ingresa el efectivo con el que abre la caja (monto inicial) y presiona "Abrir caja".
4. El sistema valida el monto y registra la sesión de caja en estado "Abierta", con la fecha y hora de apertura. Registra la acción en la bitácora.
5. El sistema muestra la caja abierta: apertura, monto inicial, lista de ingresos (fecha, comprobante y monto), total de ingresos y monto esperado (monto inicial + ingresos).
6. El recepcionista selecciona "Cerrar caja", cuenta el efectivo que tiene en la caja e ingresa ese total como monto contado.
7. El sistema calcula la diferencia (monto contado − monto esperado), muestra el resumen y pide confirmación.
8. El recepcionista confirma el cierre.
9. El sistema verifica que no haya un pago en efectivo en curso. Registra la fecha y hora de cierre, el monto contado y la diferencia, y cambia la sesión a "Cerrada". Registra la acción en la bitácora y muestra el resumen del cierre (sobrante, faltante o sin diferencia).

## Flujo alternativo
- **2a.** Si el recepcionista ya tiene una caja abierta, el caso continúa en el paso 5.
- **4a.** Si el monto inicial está vacío o es negativo, el sistema muestra "Ingrese el efectivo con el que abre la caja" y vuelve al paso 3.
- **5a.** Si la caja no tiene ingresos, el sistema muestra "Aún no hay ingresos en esta caja" y el monto esperado es igual al monto inicial.
- **7a.** Si el monto contado está vacío o es negativo, el sistema muestra "Ingrese el total de efectivo que contó en la caja" y vuelve al paso 6.
- **8a.** Si el recepcionista no confirma, el sistema vuelve al paso 5 sin cambios.
- **9a.** Si hay un pago en efectivo en curso, el sistema muestra "Hay un pago en efectivo en curso; complételo o anúlelo antes de cerrar la caja", no cierra la caja y vuelve al paso 5.
- **Volver.** Si el recepcionista sale sin cerrar, la caja sigue "Abierta" y sigue recibiendo los ingresos en efectivo.

## Postcondición
Al abrir, la sesión de caja queda "Abierta" con su monto inicial y el recepcionista puede recibir pagos en efectivo. Al cerrar, queda "Cerrada" con el monto contado y la diferencia calculada por el sistema, y ya no acepta más ingresos. Cada apertura y cierre quedan en la bitácora.
