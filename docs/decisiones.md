# Decisiones de diseño vigentes

## Actores y roles
- Roles: Administrador, Recepcionista, Médico y Paciente. Todos heredan del actor Usuario (CU02 Iniciar sesión y CU04 Configurar perfil los usa cualquier usuario).
- Los permisos son fijos por rol (tabla `rol_permiso`); hay un permiso por caso de uso, con una clave (`usuarios.gestionar`, `especialidades.gestionar`, `medicos.gestionar`, `citas.registrar`, etc.).
- El rol Administrador no se puede deshabilitar ni perder el permiso de gestionar roles. Un rol con usuarios activos no se puede deshabilitar.

## Datos personales y cuentas
- Nombres, apellidos y correo se guardan **solo en Usuario**. Médico y Paciente tienen una cuenta de usuario obligatoria (Usuario 1 — 0..1 Médico/Paciente) y toman de ella esos datos.
- El correo es obligatorio para el personal (validado en PHP) y opcional para pacientes.
- Contraseñas con hash seguro (`password_hash` / `Hash::make`), mínimo 8 caracteres.
- Tras 3 intentos fallidos consecutivos la cuenta se bloquea; solo el administrador la desbloquea (CU01).
- CU06 crea la cuenta del paciente: usuario = CI, contraseña temporal de 8 caracteres mostrada en pantalla (envío por correo en el Ciclo 2).

## Bajas y estados
- No se borran registros con historial: se desactivan (`activo = false`) usuarios, roles, especialidades, médicos, pacientes y horarios.
- Las tablas puente (`rol_permiso`, `medico_especialidad`) se eliminan o sincronizan al modificar.
- Las citas cambian de estado: Programada, Confirmada, Atendida, Cancelada o Inasistencia.
- La bitácora nunca se modifica ni se elimina.

## Médicos, especialidades y horarios
- Especialidad: nombre único sin distinguir mayúsculas ni espacios (índice `LOWER(TRIM(nombre))`). Una especialidad inactiva no se ofrece para nuevos médicos ni citas.
- Especialidad: el sistema guarda el nombre en formato tipo título ("peDiaTRía" → "Pediatría"; "de", "del", "y"… en minúscula). Se hace en `EspecialidadRequest::prepareForValidation()`. La validación de duplicados ignora mayúsculas, tildes y espacios.
- Médico: código generado (`MED-0001`), registro profesional único, al menos una especialidad, cuenta de usuario con rol Médico. Un médico inactivo no recibe nuevas citas pero conserva su historial.
- Médico ◆— HorarioAtencion es composición (el horario no existe sin su médico).
- Duración de cita por defecto: 30 minutos.

## Citas
- Un médico no puede tener dos citas vigentes a la misma fecha y hora (índice único parcial `WHERE estado <> 'Cancelada'`).
- Las citas atendidas o canceladas no se reprograman; se registra una nueva.
- El paciente es actor de CU12 y CU13 (modelado en el Ciclo 1; su pantalla se implementa en el Ciclo 2).

## Arquitectura
- Laravel monolítico con Blade, sin API (indicado por el auxiliar).
- Código organizado por paquetes (`app/Modulos/<paquete>`), igual que el diagrama de paquetes.
- Base de datos creada con el script SQL; no se usan migraciones de Laravel.

## Recuperar contraseña (CU02, flujo alternativo 3a) — 06/11/2026
- Se documenta como flujo alternativo de CU02 (no es un CU nuevo). Enlace "¿Olvidó su contraseña?" en el login → correo → enlace de un solo uso → nueva contraseña.
- Correo por Brevo (`MAIL_MAILER=brevo`), remitente `clinicavidasalud.oficial@gmail.com`. Solo funciona con cuentas que tienen correo; un paciente sin correo debe acudir a recepción.
- Al restablecer la contraseña **la cuenta se desbloquea** y los intentos vuelven a 0: quien abre el enlace demostró ser el dueño del correo y no tiene que esperar al administrador. Los mensajes de cuenta bloqueada (5c, 5d) ahora mencionan esta opción.
- Seguridad: mismo mensaje exista o no el correo (no revela qué correos están registrados); en la tabla `recuperacion_contrasena` se guarda solo el hash SHA-256 del token; el enlace vence en 30 minutos y sirve una sola vez; al pedir uno nuevo se anulan los anteriores; no se envía otro si se pidió uno hace menos de 1 minuto; la página del enlace no envía la URL a otros sitios (`referrer no-referrer`). Las cuentas desactivadas no reciben enlace.
- Límite de peticiones por IP con limitadores con nombre (`AppServiceProvider`): 5 solicitudes por minuto y 20 aperturas o envíos del enlace por minuto. No se usa `throttle:N,1` sin nombre porque comparte el contador con el del login.
- Misma política de contraseñas de CU01 y CU04 (`Contrasena::reglas`), con confirmación y checklist verde.
- Bitácora: `SOLICITAR_RECUPERACION` (también si el correo no existe, sin usuario) y `RESTABLECER_CONTRASENA` ("y cuenta desbloqueada" si estaba bloqueada).
- Las páginas públicas (login, recuperar y restablecer) comparten la plantilla `resources/views/compartido/publica.blade.php`.
- Base existente: `database/sql/actualizacion_recuperar_contrasena.sql`.

## Notificaciones push (RF30, RF31) — 06/11/2026
- Web Push estándar con la librería `minishlink/web-push` y claves VAPID propias (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` en `.env`). No hay cuenta en ningún servicio externo; las claves deben ser las mismas en todos los `.env` y en Railway (si cambian, las suscripciones dejan de servir).
- RF30 en CU04 "Mi perfil" (solo pacientes): tarjeta "Notificaciones" con el estado de este dispositivo, "Activar/Desactivar en este dispositivo" y la lista de dispositivos activos (cada uno se puede desactivar). La suscripción se envía con un formulario normal que llena `public/js/notificaciones.js` (sin API ni JSON). Service worker: `public/sw.js`; al pulsar el aviso abre "Mis citas".
- RF31: `App\Modulos\pagos\service\Notificador` intenta push en todos los dispositivos activos; si no hay o fallan todos, correo (Brevo); si tampoco, "Fallida". Las suscripciones que el navegador borró (404/410) se desactivan solas. La tabla `notificacion` guarda por dónde salió (`canal`).
- Avisos: "Cita programada" (CU21, tras el pago), "Cita reprogramada" (CU14), "Cita cancelada" (CU15, solo citas Programadas o Confirmadas; la cancelación automática por falta de pago NO se avisa) y "Recordatorio" 24 h antes. Se envían después de guardar (`DB::afterCommit`): un fallo nunca deshace la operación.
- Recordatorio: se crea "Pendiente" al programar la cita con `programada_para` = inicio − 24 h; al reprogramar se anula y se crea otro; al cancelar queda "Anulada". Si faltan menos de 24 h no se crea (el paciente acaba de recibir el aviso de programada o reprogramada). El comando `notificaciones:enviar-recordatorios` (cada 5 minutos) envía los que tocan, anula los de citas que ya no están vigentes y crea los que falten para citas antiguas.
- Railway: `docker/entrypoint.sh` arranca `php artisan schedule:work` en segundo plano como www-data (también hace correr `pagos:liberar-reservas`). El Dockerfile agrega la extensión `bcmath`.
- iPhone/iPad: push solo si el paciente agrega la clínica a la pantalla de inicio (iOS 16.4+); para eso están `public/manifest.json` y el ícono `public/img/icono-192.png`.
- Bitácora: ACTIVAR_NOTIFICACIONES y DESACTIVAR_NOTIFICACIONES (tabla `suscripcion_push`). Los envíos no van a la bitácora (quedan en `notificacion`).
- Base existente: `database/sql/actualizacion_notificaciones_push.sql` (tabla `suscripcion_push` y estado "Anulada").

## Usuarios (CU01)
- En CU01 solo se asignan roles activos **excepto Paciente** (las cuentas de pacientes se crean en el CU06 junto con su ficha). Al editar una cuenta se muestra también su rol actual.
- Correo opcional; si se ingresa, debe ser único. Contraseña obligatoria al registrar; al modificar, si se deja vacía se mantiene.
- Política de contraseñas (CU01, CU04, CU06): mínimo 8 caracteres con al menos 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial (ej.: `Clave123!`). Se pide confirmar la contraseña (campo `contrasena_confirmation`). Centralizada en `app/Modulos/compartido/Contrasena.php`.
- Nombre de usuario y correo se guardan en minúsculas; el login no distingue mayúsculas. Nombres y apellidos en formato tipo título (`app/Modulos/compartido/Texto.php`, compartido con especialidades y pacientes).
- El administrador no puede desactivar su propia cuenta.

## Médicos (CU10)
- La cuenta del médico se elige entre cuentas **activas** con rol Medico sin vincular (al editar se incluye la actual). Nombre y correo se muestran de la cuenta, sin editarse.
- Al editar se muestran las especialidades activas y además las que el médico ya tiene aunque estén inactivas ("(inactiva)"), para no quitárselas sin querer. Se guardan con `sync()` en `medico_especialidad`.
- Código `MED-0001` generado con el mayor número existente + 1. Registro profesional en mayúsculas y único (sin distinguir mayúsculas). Teléfono: números, espacios, + o -; 7 a 20 caracteres.
- Desactivar médico (CU10) no desactiva su cuenta: puede seguir entrando a ver su agenda e historial, pero no recibe nuevas citas.
- Desactivar la cuenta (CU01) de un médico también lo desactiva como médico. Reactivar la cuenta NO reactiva al médico. No se puede activar un médico cuya cuenta está desactivada ("Active primero la cuenta de usuario del médico").

## Filtros en las listas
- Especialidades: Estado. Usuarios: Rol (incluye Paciente) y Estado (Activo/Inactivo/Bloqueado). Médicos: Especialidad (incluye inactivas) y Estado. Se combinan con el buscador, se mantienen al paginar y "Limpiar" los quita.

## Roles y permisos (CU03)
- Los 4 roles del sistema (Administrador, Recepcionista, Medico, Paciente) no se pueden renombrar (el código los busca por nombre); sí su descripción y permisos. Los roles nuevos sí se renombran.
- El rol Administrador no se puede deshabilitar ni perder el permiso `roles.gestionar` (casilla bloqueada + validación en el servidor).
- No se deshabilita un rol con usuarios activos ("No se puede deshabilitar un rol con usuarios activos"). Habilitar muestra "Rol habilitado".
- Los cambios de permisos se aplican de inmediato (se leen en cada petición).
- Permisos agrupados por paquete en el formulario y en "Ver" (`Permiso::GRUPOS`). Nombre del rol en formato tipo título, único sin distinguir mayúsculas ni tildes.

## Bitácora (CU05)
- Solo lectura: una sola ruta GET; el modelo `Bitacora` cancela cualquier update/delete.
- Filtros: Desde, Hasta (4a: "La fecha final no puede ser anterior a la fecha inicial"), Usuario (incluye "Desconocido" = intentos con usuario inexistente), Acción y Tabla afectada. 20 registros por página, del más reciente al más antiguo.
- Al abrir la bitácora, Desde y Hasta vienen con la fecha de hoy; "Limpiar filtros" muestra todos los registros. La IP se ve en la lista.
- Zona horaria America/La_Paz en Laravel (`config/app.php`) y en la sesión de PostgreSQL (`config/database.php`), para que "hoy" y `fecha_hora` coincidan también en Railway.
- Ver detalle en un modal (fecha y hora, usuario y correo, acción, detalle, tabla afectada, IP).
- IP en Railway: primero salía la IP interna (100.64.x.x) y se agregó ese rango a `trustProxies`. Luego salía para todos la IP de la entrada (edge) de Railway en São Paulo (46.151.194.x, encabezado `x-railway-edge: gru1`). 07/11/2026: el middleware `IpRealRailway` (usuarios_acceso, global con `prepend`) usa el encabezado `X-Real-IP` que Railway pone con la IP real del cliente, solo si la petición viene de 100.64.0.0/10. En local se sigue viendo 127.0.0.1 (es normal). Los registros anteriores conservan la IP de São Paulo.
- Ciclo 2: "Datos anteriores / Datos nuevos" (ver ideas del Ciclo 2).

## Configurar perfil de usuario (CU04)
- Renombrado de "Gestionar Perfil de Usuario" a "Configurar Perfil de Usuario" (24/10). Acceso desde el panel izquierdo ("Mi perfil", para todos) y desde el menú del nombre de usuario.
- Una sola pantalla: encabezado (iniciales, nombre, correo, rol), Datos personales y Cambiar contraseña lado a lado ("Mostrar contraseñas"), Información de la cuenta (usuario, rol, estado, registro; si es médico: código, registro profesional, teléfono y especialidades) e Inicios de sesión recientes (últimos 5 de la bitácora). Teléfono y fecha de nacimiento NO se agregan a Usuario (no están en la BD).
- Sin permiso especial: cualquier usuario con sesión; siempre trabaja con su propia cuenta. Usuario y rol solo lectura.
- Correo opcional y único (4b "El correo ya está registrado"). Nombres/apellidos tipo título.
- Nueva contraseña con la misma política del CU01 (Contrasena::reglas, con confirmación y checklist verde) y distinta de la actual ("La nueva contraseña debe ser diferente de la actual").
- Bitácora: ACTUALIZAR_PERFIL y CAMBIAR_CONTRASENA.


## Pagos (CU20, CU21, CU22)
- Tablas nuevas: `tarifa`, `metodo_pago`, `sesion_caja`, `pago`, `detalle_pago`, `movimiento_caja`, `notificacion`. Para una base existente se aplica `database/sql/actualizacion_pagos.sql` (no borra datos).
- La cita tiene el estado nuevo "Pendiente de pago" (`estado` pasa a VARCHAR(20)) y la columna `cancelada_por` ('Sistema' cuando la cancela CU21).
- Agregados que no estaban en los diagramas: `pago.id_usuario` (quién registró el cobro; lo usan `existePagoEfectivoEnCurso` y la caja del paso 8) y `detalle_pago.id_sesion_pasarela` (sesión de Stripe que se verifica al volver).
- Permisos: `pagos.configurar` (CU20, Administrador), `pagos.cobrar` (CU21, Recepcionista), `pagos.pagar_propia` (CU21, Paciente), `caja.gestionar` (CU22, Recepcionista). El Administrador los tiene todos.
- CU20: una sola tarifa activa por especialidad (índice único parcial). Las tarifas inactivas quedan como historial y no se reactivan: se registra una nueva. El monto acepta coma o punto decimal y hasta 2 decimales. Siempre queda al menos un método de pago activo.
- Bitácora: REGISTRAR_TARIFA, MODIFICAR_TARIFA, DESACTIVAR_TARIFA, CAMBIAR_METODO_PAGO.
- CU22: cada usuario trabaja solo con su propia caja (una sola "Abierta" por usuario, índice único parcial). El monto inicial puede ser 0. Las cuentas de dinero se hacen en centavos para evitar errores de redondeo.
- CU22: el paso 7 (diferencia y confirmación) no guarda nada; al confirmar, el monto esperado se recalcula y la caja se bloquea (`lockForUpdate`) para que un cobro en efectivo de CU21 no se cruce con el cierre. "Pago en efectivo en curso" (9a) = pago "Pendiente" del usuario con un detalle de efectivo "Pendiente".
- Bitácora: ABRIR_CAJA, CERRAR_CAJA.
- CU21: la cita se crea en "Pendiente de pago" (CU13) y reserva el horario 10 minutos desde `fecha_creacion`. CU13 exige una tarifa activa ("La especialidad no tiene una tarifa vigente; no se pueden registrar citas") y un método activo (en línea, si registra el paciente), y luego redirige a la pantalla de pago.
- CU21: la reserva vencida se libera sin cron: `ReservaPago::liberarVencidas()` se llama al consultar horarios (CU12), registrar cita (CU13), en cada acción de CU21, al desactivar tarifa (CU20) y al cerrar caja (CU22). Respaldo opcional: `php artisan pagos:liberar-reservas` (programado cada minuto si corre el scheduler).
- CU21: al vencer o cancelar, la cita queda "Cancelada" con `cancelada_por = 'Sistema'` y motivo "Pago no realizado a tiempo" o "Pago cancelado"; el pago y sus detalles pendientes quedan "Anulado"; se avisa el efectivo a devolver. La sesión de Stripe abierta se expira; si alcanzó a pagarse, se reembolsa automáticamente. Las cancelaciones automáticas no van a la bitácora; "Cancelar" registra ANULAR_PAGO.
- CU21: Stripe Checkout (modo prueba). Se aprueba solo si al verificar la sesión en el servidor `payment_status = 'paid'` y coinciden el detalle (metadata), el monto y la moneda. `referencia_externa` = payment_intent. Moneda configurable (`STRIPE_CURRENCY`, por defecto bob).
- CU21: la forma de cobro (efectivo / en línea / combinado) se recuerda en la sesión de Laravel; solo se cambia si no hay detalles "Pendiente". El recepcionista que elige la forma "toma" el cobro (`pago.id_usuario`) y el efectivo entra a SU caja. Comprobante `CP-0001` con bloqueo `pg_advisory_xact_lock` para no repetir números.
- CU21 (RF29): aviso "Cita programada" por push y, si no hay o falla, por correo (ver "Notificaciones push"). Se envía después de guardar; si falla, la notificación queda "Fallida" sin revertir el pago.
- Bitácora: PAGAR_CITA, ANULAR_PAGO.
- CU13 + CU21: "Registrar cita" es un asistente por pasos: Paciente (solo recepcionista) → Especialidad (con el precio de la tarifa) → Médico → Fecha y hora (7 días con cupos y bloques libres) → Resumen y pago. Cada paso es una página (lo elegido viaja en la URL; sin API). Solo se ofrecen especialidades activas con tarifa y médicos activos. La pantalla de pago (CU21) muestra la misma barra de pasos.
- CU12: el cálculo de horarios libres está en `app/Modulos/citas/service/Disponibilidad.php` (lo usan "Disponibilidad" y el asistente). La pantalla "Disponibilidad" sigue existiendo (consulta y CU14).
- CU12 (vista): especialidades como botones → tabla con cada médico de la especialidad y sus próximos 7 días ("N libres", "Lleno" o "—" si no atiende) → horas del día elegido separadas en Mañana y Tarde. Al elegir una hora se va a Registrar cita (o a Reprogramar, en CU14) con todo ya elegido. Se mantienen las 3 operaciones del diagrama de secuencia.
- CU12 y CU13: las horas ocupadas por otra cita se muestran en rojo, tachadas y sin poder elegirse ("Ocupado", sin decir de quién es). Las horas ya pasadas de hoy no se muestran. Una cita "Cancelada" libera su hora al instante; una "Pendiente de pago" la ocupa mientras dura su reserva.
- CU21 (RF25 consultar y reimprimir): la pantalla "Pagar cita" tiene dos pestañas: "Pendientes de pago" y "Pagadas" (comprobante, cita, fecha de pago, total y "Ver comprobante"). El paciente ve solo sus pagos; recepción busca por N° de comprobante, código de cita, CI o nombre.
- CU21: si recepción empezó el cobro de una cita y tiene montos registrados sin terminar, el paciente no puede pagarla en línea ni cancelarla ("Recepción está registrando el pago de esta cita; comuníquese con recepción"). Si recepción solo eligió la forma de cobro, el paciente toma el cobro y paga el total en línea.
- CU21: "Cancelar" revisa primero si la reserva ya venció (en ese caso queda como "Pago no realizado a tiempo"). Si Stripe cobró pero el detalle, el monto o la moneda no coinciden (6a), el detalle queda "Rechazado" y el cobro se reembolsa.
- CU22: al registrar efectivo (CU21, paso 3) también se bloquea la caja, para que no se cruce con un cierre de caja al mismo tiempo.
- CU21: Stripe Checkout **incrustado** (`ui_mode: embedded_page`): el formulario de Stripe aparece dentro de la pantalla de pago (usa `STRIPE_PUBLISHABLE_KEY`). Una tarjeta rechazada se informa en el mismo formulario; "No pagar en línea" deja el detalle "Rechazado" (5a). Se actualizaron el detallar de CU21 (pasos 4 y 5a) y NOTAS_IMPLEMENTACION.

## Horarios de atención (CU11) — vista semanal
- La lista muestra una fila por médico activo (días que atiende y resumen de tramos), con buscador y filtro "Con horario / Sin horario".
- "Asignar horario" edita la semana completa del médico: por día, "Atiende" y uno o varios tramos (entrada, salida, duración de cada cita). Botón "Copiar el lunes a los días marcados" (solo JavaScript).
- Al guardar (`guardarSemana`) se comparan los tramos guardados con los del formulario y se aplican las mismas operaciones y reglas de CU11: tramo igual → sin cambios (o modificar si cambió la duración); tramo cambiado → modificar; tramo quitado o día desmarcado → desactivar (baja lógica); tramo nuevo → activar uno inactivo con las mismas horas o registrar. No se modifica ni desactiva un horario con citas pendientes; los tramos de un día no pueden superponerse. Todo o nada: si un día tiene error no se guarda ningún cambio. Bitácora: REGISTRAR/MODIFICAR/ACTIVAR/DESACTIVAR_HORARIO.
- Las rutas y métodos anteriores de un solo horario (crear, editar, activar, desactivar) se mantienen, pero ya no se enlazan desde la lista.

## Menú lateral
- Las opciones se agrupan por paquete en grupos desplegables (`config/modulos.php`: `grupos` y `opciones`), en el orden del diagrama de paquetes, salvo Agenda médica y Reportes, que van al final. Un grupo con una sola opción para el rol se muestra como enlace directo. La página de Inicio muestra las mismas tarjetas agrupadas.

## Reportes (CU19)
- Reportes como asistente de dos pasos, con la misma barra de pasos de "Registrar cita" (`citas/registrar_cita/_pasos.blade.php`, ahora acepta `$nombresPasos`): Paso 1 "Filtros" (`index.blade.php`) y Paso 2 "Resultado" (`resultado.blade.php`: resumen de filtros, exportar, totales, gráfico y tabla; "Atrás" vuelve a los filtros con los mismos valores). Si hay error (6a) o no hay datos (6b) se queda en el Paso 1 con el mensaje. La pestaña activa usa el color del sistema (`public/css/app.css`).
- Una sola pantalla "Reportes" (`/reportes`): arriba los reportes como pestañas agrupadas (Citas: Reporte de citas, Asistencia, Agenda diaria · Pacientes: Pacientes registrados · Pagos y caja: Ingresos, Cierres de caja); debajo filtros → "Generar" → tarjetas de totales → gráfico → tabla. "Exportar PDF" / "Exportar Excel" arriba a la derecha (la Agenda diaria solo PDF).
- Diseño (tomado de los reportes de la barbería): pocas tarjetas grandes con las cifras clave; los totales por estado como etiquetas de color ("Por estado"); atajos "Rápido" (mismo filtro que Período, con un clic): Hoy / Esta semana / Este mes; en la agenda Hoy / Mañana / Esta semana; en pacientes Esta semana / Este mes / Este año / Todos (por fecha de registro). El atajo deja de resaltarse si se cambian las fechas a mano, y el formulario lleva autocomplete="off" para que el navegador no restaure valores viejos; fechas cortas ("Lun 2 nov") en las tablas y gráficos por día; estados con badge de color en las tablas y en el PDF (`Cita::COLOR_ESTADO`).
- Gráficos con Chart.js por CDN (solo en la pantalla de reportes; no van al PDF): Reporte de citas = barras por día (o por estado si es un solo día), Asistencia = barras apiladas por día, Ingresos = área por día (o barras por método si es un solo día). Un punto por día, o por mes si el período pasa de 62 días. Colores validados para daltonismo en modo claro y oscuro; se redibujan al cambiar el tema.
- Excel: los totales y montos se guardan como números con formato "Bs 0.00" (se pueden sumar y Excel no los marca como texto).
- Permisos: `reportes.generar` (Administrador y Recepcionista) = Reporte de citas solo con período y estado + Agenda diaria (alternativo 2a). Nuevo `reportes.generar_todos` (Administrador) = los 6 reportes con todos sus filtros. Se valida en el servidor: los otros reportes dan 403 y los filtros de médico/especialidad del recepcionista se ignoran. Base existente: `database/sql/actualizacion_reportes.sql`.
- No hay tablas nuevas; solo consultas. La única escritura es la bitácora al exportar (`EXPORTAR_REPORTE`, "Exportar reporte"), con el tipo, el formato y los filtros en el detalle. Ver un reporte en pantalla no se registra.
- Período: Diario, Semanal (lunes a domingo de la fecha elegida), Mensual o Rango de fechas. 6a "La fecha de inicio no puede ser mayor que la fecha de fin"; 6b "No se encontraron resultados para los filtros seleccionados" y la exportación queda deshabilitada (también en el servidor).
- Exportar reenvía los filtros (POST) y el servidor vuelve a generar el reporte: no se guarda nada en la sesión ni en el servidor. PDF con Dompdf (A4, fuente DejaVu Sans, caché de fuentes en `storage/framework/cache/dompdf`); Excel .xlsx con PhpSpreadsheet. Encabezado: clínica, tipo de reporte, filtros, fecha y hora, y usuario.
- Agenda diaria: no muestra las citas canceladas (su hora quedó libre). Incluye "Pendiente de pago". Se puede pedir "Del día" o "De la semana" (lunes a domingo de la fecha elegida, con la columna Día; el archivo se titula "Agenda semanal"). Cambio del alternativo 3b acordado el 06/11/2026 (detallar actualizado).
- Ingresos: solo pagos "Pagado" con detalles "Aprobado", filtrados por `pago.fecha` (la fecha en que quedó pagado). Una fila por detalle (un pago combinado sale en dos filas). "Cobró" = usuario del pago, o "En línea" si el método es en línea. `pago.id_usuario` sigue NOT NULL (CU21 lo usa para saber quién tomó el cobro).
- Cierres de caja: solo sesiones "Cerrada", filtradas por la fecha de apertura. Monto esperado = monto inicial + ingresos en efectivo (`movimiento_caja`), igual que CU22. El filtro de recepcionista lista a los de rol Recepcionista y a cualquiera que haya abierto una caja.
- Los filtros de médico y especialidad incluyen los inactivos (los reportes son historial).
- Railway: el Dockerfile instala las extensiones `gd` y `zip` (las exige PhpSpreadsheet).

## Ciclo 2: CU07, CU08, CU16, CU17 y CU18
- Las opciones de CU07 y CU18 requieren tanto su permiso como una ficha vinculada del tipo de actor correspondiente; el menú no se filtra por el nombre del rol. Los controladores autorizan la operación por permiso y asociación de usuario-paciente/médico, de modo que un rol personalizado con el permiso y ficha apropiados pueda usarla.
- CU08 determina el historial propio mediante la ficha de paciente vinculada. Si la cuenta tiene esa ficha, no puede consultar otros historiales; el resto de usuarios con permiso puede buscar pacientes.
- CU17 determina la agenda propia mediante la ficha de médico vinculada. Los demás usuarios con `agenda.consultar` pueden seleccionar un médico.
- CU16 usa `CAMBIAR_ESTADO_CITA` para cualquier transición; CU18 usa `REGISTRAR_ATENCION` y CU07 `ACTUALIZAR_PERFIL_MEDICO`. Los nombres de tabla en bitácora usan las tablas reales (`cita`, `atencion_medica`, `perfil_medico`) y ningún registro de auditoría incluye diagnóstico ni notas clínicas.
- Los badges de todos los estados de cita se obtienen de `Cita::COLOR_ESTADO` para conservar una sola asignación de colores.
- CU16 no permite registrar Atención/Inasistencia hasta el inicio de la cita. CU18 tampoco lista ni guarda atención antes del inicio, y no admite más de una atención clínica por cita.
- CU08 ejecuta `ReservaPago::liberarVencidas()` antes de consultar el historial para que las reservas vencidas aparezcan como canceladas.

## Asistencia al usuario (CU23, CU24, CU25) — 07/11/2026
- Paquete nuevo `asistencia_usuario` (menú "Asistencia al usuario", al final). Tabla nueva `pregunta_frecuente`; base existente: `database/sql/actualizacion_asistencia_usuario.sql` (empieza con `SET client_encoding = 'UTF8'` para que las tildes no se dañen en Railway). Se agregó lo mismo a `clinica_vida_salud.sql`.
- Permisos: `faq.gestionar` (CU23, Administrador), `asistente.consultar` (CU24, Paciente) y `voz.usar` (CU25, los 4 roles). El Administrador los tiene todos.
- CU23: 5 categorías fijas (`PreguntaFrecuente::CATEGORIAS`, también como CHECK en la BD). Pregunta única sin distinguir mayúsculas, tildes ni signos ¿?¡!. Las palabras clave se guardan en minúsculas, separadas por comas y sin repetidas. Bitácora: REGISTRAR_, MODIFICAR_, DESACTIVAR_ y ACTIVAR_PREGUNTA_FRECUENTE. Se cargan 24 preguntas iniciales; las de "La clínica" (dirección, horario, teléfono) son DATOS DE EJEMPLO que el administrador debe cambiar.
- CU24 es un chatbot basado en reglas, SIN inteligencia artificial (`service/InterpreteAsistente.php`). Texto normalizado (minúsculas, sin tildes, ñ = n, sin signos) e ignora palabras comunes. Orden: saludo/agradecimiento → especialidades → precio de una especialidad → pregunta frecuente con más puntos. Puntos: palabra clave de varias palabras encontrada completa = 4, palabra clave = 3, palabra del texto de la pregunta = 1; se responde con 3 o más. Dos palabras "coinciden" si son iguales o comparten al menos 4 letras iniciales y a cada una le sobran 3 letras como máximo ("cancelo" = "cancelar", "estado" ≠ "estacionamiento").
- CU24: las especialidades y precios salen de `Especialidad::listarParaRegistrarCita()` (activas, con tarifa vigente y con médicos activos: las que se pueden reservar). Una pregunta de precio que coincide con otra pregunta frecuente ("¿reprogramar tiene costo?") responde esa pregunta, no la lista de precios.
- CU24: la conversación vive solo en la sesión (`asistente_virtual.conversacion`, máximo 20 mensajes); no se guarda en la BD ni en la bitácora. Solo con sesión iniciada: `/asistente-virtual`, permiso `asistente.consultar` (actor Paciente). Límite `throttle:asistente-virtual` (30 por minuto por usuario); al superarlo se muestra la página 429.
- 07/11/2026: se quitó el acceso del Visitante (página pública `/ayuda/asistente-virtual` y su enlace en el login) porque el CU24 solo tiene como actor al Paciente; también se quitó del login el texto "Ingrese con su nombre de usuario o correo electrónico" (se mantiene el aviso de bloqueo tras 3 intentos).
- CU25 (`service/InterpreteComandoVoz.php` + `public/js/comando_voz.js`): Web Speech API del navegador (idioma es-BO, y es-ES si no lo tiene). El texto se envía con un formulario normal a `POST /comando-voz` y el servidor redirige. Los comandos solo buscan y navegan. "abrir [opción]" usa las opciones del menú de `config/modulos.php` y sus permisos; el nombre de un grupo abre su primera opción permitida. "buscar médico" requiere `medicos.gestionar` (solo Administrador). "mi agenda" requiere ficha de médico. Errores como alerta en la misma pantalla (`session('error')`); el comando reconocido se muestra con `session('comando')`. Límite `throttle:comando-voz` (30 por minuto por usuario). La opción "Comandos de voz" del menú muestra la ayuda.
