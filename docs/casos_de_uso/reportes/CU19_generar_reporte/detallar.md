# CU19 Generar Reporte

| Campo | Contenido |
|---|---|
| Caso de uso | CU19 Generar Reporte |
| Paquete | reportes |
| Permiso | `reportes.generar` |
| Actores | Administrador, Recepcionista |
| Actor iniciador | Administrador, Recepcionista |

## Propósito
Obtener información resumida de las citas, los pacientes, los ingresos y la caja de un período, para controlar el funcionamiento de la clínica y tomar decisiones.

## Descripción
Permite al administrador generar reportes de citas, de asistencia, de agenda diaria por médico, de pacientes registrados, de ingresos y de cierres de caja, aplicando filtros por fechas y otros criterios, y ver los resultados con sus totales. Los reportes se pueden exportar en PDF y Excel, excepto la agenda diaria, que se descarga en PDF lista para imprimir. El recepcionista solo puede consultar las citas por fecha y estado y generar la agenda diaria de cada médico. Los reportes solo consultan información y no modifican ningún dato.

## Precondición
El actor inició sesión y tiene permiso para generar reportes.

## Flujo principal
1. El actor selecciona “Reportes”.
2. El sistema muestra los reportes disponibles agrupados en Citas, Pacientes y Pagos y caja.
3. El actor selecciona “Reporte de citas”.
4. El sistema muestra los filtros: período (diario, semanal, mensual o rango de fechas), médico, especialidad y estado.
5. El actor ingresa los filtros y presiona “Generar”.
6. El sistema valida los filtros, obtiene las citas y muestra el total de citas, el total por estado y el detalle (fecha, hora, paciente, médico, especialidad y estado).
7. El actor presiona “Exportar PDF” o “Exportar Excel”.
8. El sistema genera el archivo con el nombre de la clínica, el tipo de reporte, los filtros aplicados, la fecha y hora de generación y el usuario que lo generó. Descarga el archivo y registra la acción en la bitácora.

## Flujo alternativo
- **2a.** Si el actor es Recepcionista, el sistema solo muestra “Reporte de citas”, con los filtros de fecha y estado, y “Agenda diaria”.
- **3a. Reporte de asistencia.** El administrador selecciona “Asistencia” y elige el período. El sistema muestra, por día, la cantidad de citas atendidas, canceladas e inasistencias, con sus totales. Continúa en el paso 7.
- **3b. Agenda diaria.** El actor selecciona “Agenda diaria” y elige la fecha, el médico y si quiere la agenda del día o de la semana. El sistema muestra las citas del día, o de lunes a domingo de esa semana, ordenadas por día y hora (día, hora, paciente, CI, especialidad y estado). Solo permite “Exportar PDF”, listo para imprimir. Continúa en el paso 8.
- **3c. Reporte de pacientes registrados.** El administrador selecciona “Pacientes registrados” y filtra por estado (activo o inactivo) y por rango de fechas de registro. El sistema muestra el total de pacientes, cuántos están activos e inactivos, y el listado (nombre, CI, teléfono, fecha de registro y estado). Continúa en el paso 7.
- **3d. Reporte de ingresos.** El administrador selecciona “Ingresos” y filtra por período, médico, especialidad y método de pago. El sistema muestra el total cobrado, el total por método de pago, el número de pagos y el detalle (fecha, paciente, especialidad, método, monto y quién cobró, o “En línea”). Continúa en el paso 7.
- **3e. Reporte de cierres de caja.** El administrador selecciona “Cierres de caja” y filtra por período y recepcionista. El sistema muestra cada sesión cerrada (fecha, recepcionista, monto de apertura, monto esperado, monto contado y diferencia), con sus totales. Continúa en el paso 7.
- **6a.** Si la fecha de inicio es mayor que la fecha de fin, el sistema muestra “La fecha de inicio no puede ser mayor que la fecha de fin” y vuelve al paso 5.
- **6b.** Si no hay datos para los filtros elegidos, el sistema muestra “No se encontraron resultados para los filtros seleccionados”, deshabilita la exportación y vuelve al paso 5.
- **Cambiar filtros.** El actor modifica los filtros y el sistema vuelve al paso 5.

## Postcondición
El actor obtiene el reporte con sus totales según los filtros elegidos. Si lo exportó, el archivo PDF o Excel se descarga y la exportación queda registrada en la bitácora. Los datos consultados no se modifican.
