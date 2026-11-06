# CU20 Gestionar Tarifas y Métodos de Pago

| Campo | Contenido |
|---|---|
| Caso de uso | CU20 Gestionar Tarifas y Métodos de Pago |
| Paquete | pagos |
| Permiso | `pagos.configurar` |
| Actores | Administrador |
| Actor iniciador | Administrador |

## Propósito
Definir cuánto se cobra por la consulta de cada especialidad y qué métodos de pago acepta la clínica.

## Descripción
El administrador registra, modifica, consulta y desactiva la tarifa de consulta de cada especialidad, con una sola tarifa activa por especialidad. También habilita o deshabilita los métodos de pago (Efectivo y Pago en línea). Las tarifas no se eliminan, se desactivan, y los pagos ya realizados conservan el monto que se cobró. Cada cambio queda registrado en la bitácora.

## Precondición
El administrador ha iniciado sesión y existe al menos una especialidad activa.

## Flujo principal
1. El administrador selecciona "Tarifas y métodos de pago" en el panel principal.
2. El sistema muestra las tarifas (especialidad, monto, vigente desde y estado) y los métodos de pago (nombre, si es en línea y estado).
3. El administrador selecciona "Nueva tarifa".
4. El sistema muestra el formulario con las especialidades activas que no tienen una tarifa activa.
5. El administrador elige la especialidad, ingresa el monto y presiona "Guardar".
6. El sistema valida el monto y que la especialidad no tenga ya una tarifa activa.
7. El sistema registra la tarifa como activa, con fecha de vigencia de hoy, y registra la acción en la bitácora.
8. El sistema muestra "Tarifa registrada" y la lista actualizada.

## Flujo alternativo
- **4a.** Si todas las especialidades activas ya tienen una tarifa activa, el sistema muestra "Todas las especialidades activas ya tienen una tarifa; modifique la existente".
- **6a.** Si el monto está vacío, no es numérico o es menor o igual a 0, el sistema muestra "Ingrese un monto mayor a 0" y vuelve al paso 5.
- **6b.** Si la especialidad ya tiene una tarifa activa, el sistema muestra "La especialidad ya tiene una tarifa activa; modifíquela" y vuelve al paso 5.
- **Modificar tarifa.** El administrador selecciona una tarifa activa y cambia el monto; el sistema valida el monto (6a), actualiza el monto y la fecha de vigencia a hoy, registra la acción en la bitácora y muestra "Tarifa actualizada". Los pagos ya realizados conservan su monto.
- **Desactivar tarifa.** El administrador selecciona una tarifa activa y confirma; el sistema verifica que no haya citas "Pendiente de pago" de esa especialidad, marca la tarifa como inactiva, registra la acción en la bitácora y muestra "Tarifa desactivada. La especialidad no podrá recibir nuevas citas hasta que tenga una tarifa activa".
  - Si hay citas "Pendiente de pago" de esa especialidad, el sistema muestra "Hay citas pendientes de pago de esta especialidad; intente en unos minutos" y no desactiva la tarifa.
- **Habilitar o deshabilitar un método de pago.** El administrador cambia el estado de "Efectivo" o de "Pago en línea"; el sistema verifica que quede al menos un método activo, actualiza el estado, registra la acción en la bitácora y muestra "Método de pago actualizado".
  - Si se intenta deshabilitar el único método activo, el sistema muestra "Debe quedar al menos un método de pago activo" y no cambia el estado.
- **Cancelar.** En cualquier momento el administrador presiona "Cancelar" y el sistema vuelve a la lista sin guardar cambios.

## Postcondición
Cada especialidad activa tiene como máximo una tarifa activa, que es la que se cobra en CU21. Los métodos de pago quedan habilitados o deshabilitados según lo elegido, con al menos uno activo. Las tarifas desactivadas se conservan como historial, los pagos anteriores conservan su monto y cada cambio queda en la bitácora.
