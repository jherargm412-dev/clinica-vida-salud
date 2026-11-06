# Notas de implementación – Reportes (CU19 Generar Reporte)

Reglas técnicas y decisiones del paquete `reportes`. El `detallar.md` del CU19 manda sobre estas notas; si algo choca, sigue el detallar y avisa.

## 1. Alcance
- Solo **consultas**: ningún reporte inserta, modifica ni borra datos. La única escritura es el registro en `bitacora` al **exportar** (acción `"Exportar reporte"`). Generar o ver un reporte en pantalla **no** se registra.
- **No hay tablas nuevas.** Todo sale de las tablas del Ciclo 2: `cita`, `paciente`, `medico`, `especialidad`, `usuario`, `pago`, `detalle_pago`, `metodo_pago`, `sesion_caja` (y `movimiento_caja` si hace falta, ver §5) y `bitacora`.

## 2. Reportes y permisos

| Grupo en pantalla | Reporte | Filtros | Exportar | Administrador | Recepcionista |
|---|---|---|---|---|---|
| Citas | Reporte de citas | período, médico, especialidad, estado | PDF y Excel | ✔ | ✔ (solo fecha y estado) |
| Citas | Asistencia | período | PDF y Excel | ✔ | ✘ |
| Citas | Agenda diaria | fecha (del día o de la semana), médico | **Solo PDF** (para imprimir) | ✔ | ✔ |
| Pacientes | Pacientes registrados | estado (activo/inactivo), rango de fechas de registro | PDF y Excel | ✔ | ✘ |
| Pagos y caja | Ingresos | período, médico, especialidad, método de pago | PDF y Excel | ✔ | ✘ |
| Pagos y caja | Cierres de caja | período, recepcionista | PDF y Excel | ✔ | ✘ |

- El permiso sugerido en el detallar es `reportes.generar`. Adáptalo a la convención de permisos que ya usa el sistema y avisa cuál quedó.
- Para el recepcionista, la pantalla **solo muestra** Reporte de citas (con filtros de fecha y estado) y Agenda diaria (alternativo 2a). Valida también en el servidor (no solo ocultar botones).
- Período: Diario, Semanal, Mensual o Rango de fechas (fecha inicio / fecha fin).

## 3. Validaciones y mensajes (exactos del detallar)
- Fecha de inicio mayor que la de fin → `La fecha de inicio no puede ser mayor que la fecha de fin` (vuelve a los filtros).
- Sin datos → `No se encontraron resultados para los filtros seleccionados` y **se deshabilitan** los botones de exportar.

## 4. Archivo exportado
- Encabezado de todos los archivos: **nombre de la clínica**, **tipo de reporte**, **filtros aplicados**, **fecha y hora de generación** y **usuario que lo generó**.
- **PDF:** Dompdf (o TCPDF si el proyecto ya usa otra). La agenda diaria en formato listo para imprimir (A4, ordenada por hora).
- **Excel:** PhpSpreadsheet, formato **.xlsx** (no CSV, para no romper tildes y ñ).
- Instalar con Composer: `composer require dompdf/dompdf phpoffice/phpspreadsheet`.
- El archivo se descarga directamente (no se guarda en el servidor).

## 5. Notas de consultas
- **Nombres de pacientes y médicos** están en `usuario` (`nombres`, `apellidos`): unir `paciente.id_usuario` / `medico.id_usuario` con `usuario`.
- **Reporte de citas:** total de citas, total por estado y detalle (fecha, hora, paciente, médico, especialidad, estado). Estados: Pendiente de pago, Programada, Confirmada, Atendida, Cancelada, Inasistencia.
- **Asistencia:** por día, cantidad de citas **Atendida**, **Cancelada** e **Inasistencia**, con totales.
- **Agenda diaria:** citas del día (o de la semana, lunes a domingo) de un médico ordenadas por fecha y `hora_inicio` (día, hora, paciente, CI, especialidad, estado). Sin las canceladas.
- **Pacientes registrados:** total, activos e inactivos, y listado (nombre, CI, teléfono, fecha de registro, estado). La fecha de registro sale de `usuario.fecha_registro`.
- **Ingresos:** total cobrado, total por método de pago, número de pagos y detalle (fecha, paciente, especialidad, método, monto y quién cobró). Incluye pagos de ventanilla y **en línea (Stripe)**; si el pago no tiene cajero (`pago.id_usuario` nulo o método `en_linea = true`) mostrar **"En línea"**. Contar solo pagos en estado pagado/confirmado (usa el estado que ya definió el CU21).
  - En el modelo de análisis la relación es Usuario **0..1** – Pago: si hoy `pago.id_usuario` es NOT NULL, propón el cambio para permitir pagos en línea sin cajero.
- **Cierres de caja:** solo sesiones con estado cerrada; por cada una fecha, recepcionista, monto de apertura, monto esperado, monto contado y diferencia, con totales. Si `sesion_caja` no guarda el monto esperado, calcularlo como `monto_inicial + suma de movimiento_caja` de esa sesión (igual que en CU22).

## 6. Pendientes del documento (no bloquean la implementación)
- Numeración de RF de Reportes: Ingresos y Cierres de caja como RF30/RF31 o RF24/RF25 (pendiente de confirmar en el Google Doc).
- Sugerencias al detallar aún no aplicadas: en 3b "Continúa en el paso 8" → paso 7; agregar "y presiona 'Generar'" en 3a, 3c, 3d y 3e.
