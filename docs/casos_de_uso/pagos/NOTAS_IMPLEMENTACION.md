# Notas de implementación — paquete `pagos` (CU20, CU21, CU22)

## Pasarela de pago en línea: Stripe (modo de prueba)
- Usar **solo claves de prueba** (`sk_test_...`, `pk_test_...`) en variables de entorno (`STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`). Nunca subir claves al repositorio.
- `PasarelaPagoEnLinea.crearSesionPago(monto, idCita)` → crear una **Stripe Checkout Session incrustada** (`ui_mode: embedded_page`, modo `payment`, moneda BOB o la configurada, `return_url` del sistema, `metadata.idCita`, `metadata.idPago` y `metadata.idDetallePago`). Devuelve `idSesion` y `client_secret`.
- `mostrarFormularioPago(clientSecret)` → la pantalla de pago carga Stripe.js (`https://js.stripe.com/endive/stripe.js`) con la clave publicable y monta el formulario con `stripe.createEmbeddedCheckoutPage({ fetchClientSecret })`. El `client_secret` ya viene escrito en la vista (sin API ni JSON).
- `retornarPago(idSesion)` → la `return_url` recibe `session_id={CHECKOUT_SESSION_ID}`.
- `verificarPago(idSesion)` → recuperar la sesión en el servidor y aprobar solo si `payment_status = 'paid'`. La `referenciaExterna` del detalle en línea es el `payment_intent` de la sesión.
- Tarjeta de prueba aprobada: `4242 4242 4242 4242`, cualquier fecha futura y cualquier CVC. Tarjeta rechazada: `4000 0000 0000 0002` (flujo alternativo 5a).

## Reglas clave
- El pago se crea una sola vez al elegir la forma de cobro (CU21, paso 2); los detalles (efectivo / en línea) se agregan después.
- La cita queda reservada 10 minutos desde que pasa a "Pendiente de pago"; si vence, se cancela con canceladaPor "Sistema" (CU21, 7a).
- La suma de los detalles debe ser igual al total antes de marcar el pago como "Pagado".
- Solo el efectivo genera `MovimientoCaja` tipo "Ingreso" en la caja abierta del recepcionista.
- Una sola tarifa activa por especialidad: índice único parcial `UNIQUE (id_especialidad) WHERE activo = true`.
- Siempre debe quedar al menos un método de pago activo.
- Los pagos conservan `montoTotal` aunque la tarifa cambie después.

## Pendiente
- CU20: el diagrama de secuencia todavía no está hecho.
