<?php

namespace App\Modulos\pagos\controller\pagar_cita;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\pagos\models\DetallePago;
use App\Modulos\pagos\models\MetodoPago;
use App\Modulos\pagos\models\MovimientoCaja;
use App\Modulos\pagos\models\Notificacion;
use App\Modulos\pagos\models\Pago;
use App\Modulos\pagos\models\SesionCaja;
use App\Modulos\pagos\models\Tarifa;
use App\Modulos\pagos\service\PasarelaPagoEnLinea;
use App\Modulos\pagos\service\ReservaPago;
use App\Modulos\usuarios_acceso\models\Bitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Exception\InvalidRequestException;
use Throwable;

/**
 * CU21 Pagar Cita (Ctrl_Pagos en los diagramas).
 *
 * Dos actores:
 *  - Recepcionista (permiso pagos.cobrar): efectivo, pago en línea o combinado.
 *  - Paciente (permiso pagos.pagar_propia): solo pago en línea y solo sus propias citas (2a).
 *
 * El estado del cobro se guarda en la BD (pago + detalles); la "forma de cobro" elegida
 * por el recepcionista se recuerda en la sesión de Laravel.
 */
class PagoController extends Controller
{
    private const MSJ_VENCIDO = 'El tiempo para pagar venció; el horario fue liberado';
    private const MSJ_NO_APROBADO = 'El pago en línea no fue aprobado';
    private const MSJ_SIN_CAJA = 'Abra la caja para registrar pagos en efectivo';
    private const MSJ_PASARELA = 'No se pudo conectar con la pasarela de pago; intente nuevamente';
    private const MSJ_COBRO_RECEPCION = 'Recepción está registrando el pago de esta cita; comuníquese con recepción';
    private const FORMAS = ['efectivo', 'en_linea', 'combinado'];

    public function __construct(private PasarelaPagoEnLinea $pasarela)
    {
    }

    // ---------------------------------------------------------------
    // Paso 1: lista de citas pendientes de pago y pantalla de pago
    // ---------------------------------------------------------------

    /**
     * Pestaña "Pendientes": citas "Pendiente de pago", todas (recepcionista) o solo las propias (paciente).
     * Pestaña "Pagadas" (?vista=pagadas): pagos completados, para consultar y reimprimir el comprobante (RF25).
     */
    public function listarPendientes(Request $request): View
    {
        ReservaPago::liberarVencidas();

        $esRecepcion = $this->esRecepcion($request);
        $idPaciente = $esRecepcion ? null : $this->idPacienteSesion($request);
        $vista = $request->query('vista') === 'pagadas' ? 'pagadas' : 'pendientes';
        $texto = $esRecepcion ? trim((string) $request->query('buscar', '')) : '';

        return view('pagos.pagar_cita.index', [
            'vista' => $vista,
            'texto' => $texto,
            'pagados' => $vista === 'pagadas'
                ? Pago::listarPagados($idPaciente, $texto ?: null)->paginate(15)->withQueryString()
                : null,
            'citas' => Cita::listarPendientesDePago($idPaciente),
            'esRecepcion' => $esRecepcion,
        ]);
    }

    /** Paso 1 (obtenerDatosPago): resumen de la cita, monto total, tiempo restante y métodos activos. */
    public function mostrarPago(Request $request, Cita $cita): View|RedirectResponse
    {
        ReservaPago::liberarVencidas();
        $this->autorizarCita($request, $cita);

        $cita = Cita::obtenerDetallePorId($cita->id_cita);
        $pago = Pago::obtenerPorCita($cita->id_cita);

        if ($pago?->estado === 'Pagado') {
            return redirect()->route('pagos.comprobante', $cita);
        }
        if ($cita->estado !== 'Pendiente de pago') {
            return redirect()->route('pagos.index')->with('error', $this->mensajeNoPendiente($request, $cita));
        }

        $esRecepcion = $this->esRecepcion($request);
        $tarifa = Tarifa::obtenerActivaPorEspecialidad($cita->id_especialidad);
        $montoTotal = $pago?->monto_total ?? $tarifa?->monto;   // si ya hay pago, su monto no cambia

        if ($montoTotal === null) {
            return redirect()->route('pagos.index')->with('error', 'La especialidad no tiene una tarifa vigente');
        }

        $metodos = $esRecepcion ? MetodoPago::listarActivos() : MetodoPago::listarActivosEnLinea();
        $enLineaPendiente = $pago ? DetallePago::enLineaPendiente($pago->id_pago) : null;

        return view('pagos.pagar_cita.pagar', [
            'cita' => $cita,
            'pago' => $pago,
            'montoTotal' => $montoTotal,
            'saldo' => $pago ? Pago::deCentavos($pago->saldoCentavos()) : $montoTotal,
            'segundosRestantes' => $cita->segundosRestantesPago(),
            'metodos' => $metodos,
            'hayEfectivo' => $metodos->contains(fn ($m) => ! $m->en_linea),
            'hayEnLinea' => $metodos->contains(fn ($m) => $m->en_linea),
            'esRecepcion' => $esRecepcion,
            // 2a: el paciente siempre paga solo en línea
            'forma' => $esRecepcion ? session($this->claveForma($cita)) : 'en_linea',
            'detalles' => $pago ? $pago->detalles()->with('metodoPago')->orderBy('id_detalle_pago')->get() : collect(),
            'enLineaPendiente' => $enLineaPendiente,
            // Paso 5: si hay un pago en línea abierto, se muestra el formulario de Stripe dentro de esta página
            'clientSecret' => $this->clientSecretAbierto($enLineaPendiente),
            'clavePublica' => config('services.stripe.public'),
            'cajaAbierta' => $esRecepcion ? SesionCaja::obtenerAbiertaPorUsuario($request->user()->id_usuario) : null,
        ]);
    }

    // ---------------------------------------------------------------
    // Paso 2: forma de cobro (solo recepcionista)
    // ---------------------------------------------------------------

    /** Paso 2 (iniciarCobro): registra el pago "Pendiente" por el monto total y recuerda la forma elegida. */
    public function iniciarCobro(Request $request, Cita $cita): RedirectResponse
    {
        $this->autorizarRecepcion($request);
        $forma = $request->validate(['forma' => ['required', 'in:' . implode(',', self::FORMAS)]])['forma'];
        ReservaPago::liberarVencidas();
        $idUsuario = (int) $request->user()->id_usuario;

        // ¿Los métodos que necesita esta forma están habilitados? (CU20)
        $efectivoActivo = MetodoPago::listarActivos()->contains(fn ($m) => ! $m->en_linea);
        $enLineaActivo = MetodoPago::listarActivosEnLinea()->isNotEmpty();
        if (($forma !== 'en_linea' && ! $efectivoActivo) || ($forma !== 'efectivo' && ! $enLineaActivo)) {
            return redirect()->route('pagos.pagar', $cita)->with('error', 'El método de pago seleccionado no está habilitado');
        }

        $resultado = DB::transaction(function () use ($request, $cita, $idUsuario): array {
            [$citaBloqueada, $error] = $this->bloquearCitaVigente($request, $cita->id_cita);
            if ($error) {
                return $error;
            }

            $pago = Pago::obtenerPorCita($cita->id_cita, bloquear: true);

            if ($pago) {
                // Solo se cambia la forma si no hay nada cobrándose todavía
                if ($pago->detalles()->where('estado', 'Pendiente')->exists()) {
                    return ['error' => 'Complete o cancele el cobro en curso antes de cambiar la forma de cobro'];
                }
                $pago->update(['id_usuario' => $idUsuario]);   // este recepcionista toma el cobro
                return ['ok' => true];
            }

            // 2: el pago se registra una sola vez, por el monto de la tarifa vigente
            $tarifa = Tarifa::obtenerActivaPorEspecialidad($citaBloqueada->id_especialidad, bloquear: true);
            if (! $tarifa) {
                return ['error' => 'La especialidad no tiene una tarifa vigente'];
            }
            Pago::insertarPago($cita->id_cita, $idUsuario, $tarifa->monto);

            return ['ok' => true];
        });

        if (isset($resultado['error'])) {
            return $this->redirigirError($cita, $resultado);
        }

        session([$this->claveForma($cita) => $forma]);

        return redirect()->route('pagos.pagar', $cita);
    }

    // ---------------------------------------------------------------
    // Paso 3: efectivo (solo recepcionista)
    // ---------------------------------------------------------------

    /** Paso 3 (registrarPagoEfectivo): detalle de efectivo "Pendiente"; muestra el saldo. */
    public function registrarPagoEfectivo(Request $request, Cita $cita): RedirectResponse
    {
        $this->autorizarRecepcion($request);
        ReservaPago::liberarVencidas();
        $idUsuario = (int) $request->user()->id_usuario;
        $forma = session($this->claveForma($cita));
        $textoMonto = str_replace(',', '.', trim((string) $request->input('monto_efectivo')));

        $resultado = DB::transaction(function () use ($request, $cita, $idUsuario, $forma, $textoMonto): array {
            [, $error] = $this->bloquearCitaVigente($request, $cita->id_cita);
            if ($error) {
                return $error;
            }

            $pago = Pago::obtenerPorCita($cita->id_cita, bloquear: true);
            if (! $pago || $pago->estado !== 'Pendiente' || ! in_array($forma, ['efectivo', 'combinado'], true)) {
                return ['error' => 'Seleccione la forma de cobro'];
            }
            if ((int) $pago->id_usuario !== $idUsuario) {
                return ['error' => 'Otro usuario está cobrando esta cita'];
            }
            if (DetallePago::enLineaPendiente($pago->id_pago)) {
                return ['error' => 'Hay un pago en línea en curso; espere su resultado'];
            }

            // 3a: sin caja abierta no se recibe efectivo.
            // Se bloquea la caja: así no se cruza con un cierre de caja (CU22, 9a) que ocurra al mismo tiempo.
            if (! SesionCaja::obtenerAbiertaPorUsuario($idUsuario, bloquear: true)) {
                return ['error' => self::MSJ_SIN_CAJA];
            }

            $efectivo = MetodoPago::listarActivos()->first(fn ($m) => ! $m->en_linea);
            if (! $efectivo) {
                return ['error' => 'El método de pago seleccionado no está habilitado'];
            }

            // validarMontoEfectivo (3b y 3c)
            $saldo = $pago->saldoCentavos();
            $total = Pago::aCentavos($pago->monto_total);
            $esNumero = preg_match('/^\d+(\.\d{1,2})?$/', $textoMonto) === 1;
            $monto = $esNumero ? Pago::aCentavos($textoMonto) : 0;

            if (! $esNumero || $monto <= 0 || $monto > $saldo) {
                $tope = $saldo === $total ? 'el total' : 'el saldo';
                return ['campo' => "El monto en efectivo debe ser mayor a 0 y no superar {$tope} (Bs " . Pago::deCentavos($saldo) . ')'];
            }
            if ($forma === 'efectivo' && $monto !== $saldo) {
                return ['campo' => 'El monto debe ser igual al total (Bs ' . Pago::deCentavos($saldo) . ')'];
            }

            DetallePago::insertarDetalle($pago->id_pago, $efectivo->id_metodo_pago, Pago::deCentavos($monto));

            return ['saldo' => Pago::deCentavos($saldo - $monto)];
        });

        if (isset($resultado['campo'])) {
            return back()->withErrors(['monto_efectivo' => $resultado['campo']])->withInput();
        }
        if (isset($resultado['error'])) {
            return $this->redirigirError($cita, $resultado);
        }

        return redirect()->route('pagos.pagar', $cita)
            ->with('ok', 'Efectivo registrado. Saldo: Bs ' . $resultado['saldo']);
    }

    // ---------------------------------------------------------------
    // Pasos 4 a 6: pago en línea con Stripe
    // ---------------------------------------------------------------

    /** Paso 4 (iniciarPagoEnLinea): detalle en línea por el saldo y sesión de Stripe; el formulario se muestra en la pantalla de pago. */
    public function iniciarPagoEnLinea(Request $request, Cita $cita): RedirectResponse
    {
        $this->autorizarCita($request, $cita);
        ReservaPago::liberarVencidas();
        $esRecepcion = $this->esRecepcion($request);
        $idUsuario = (int) $request->user()->id_usuario;

        $enLinea = MetodoPago::listarActivosEnLinea()->first();
        if (! $enLinea) {
            return redirect()->route('pagos.pagar', $cita)->with('error', 'El método de pago seleccionado no está habilitado');
        }

        // Si quedó un intento anterior sin terminar (p. ej. volvió con el botón "atrás"), se revisa primero
        $redireccion = $this->resolverIntentoAnterior($cita);
        if ($redireccion) {
            return $redireccion;
        }

        $resultado = DB::transaction(function () use ($request, $cita, $esRecepcion, $idUsuario, $enLinea): array {
            [$citaBloqueada, $error] = $this->bloquearCitaVigente($request, $cita->id_cita);
            if ($error) {
                return $error;
            }

            $pago = Pago::obtenerPorCita($cita->id_cita, bloquear: true);

            if (! $esRecepcion && ! $pago) {
                // 2a: el paciente paga solo en línea por el total; el pago se registra ahora
                $tarifa = Tarifa::obtenerActivaPorEspecialidad($citaBloqueada->id_especialidad, bloquear: true);
                if (! $tarifa) {
                    return ['error' => 'La especialidad no tiene una tarifa vigente'];
                }
                $pago = Pago::insertarPago($cita->id_cita, $idUsuario, $tarifa->monto);
            }

            // 2a: el paciente paga por el total; si recepción ya está cobrando (con efectivo registrado), no interviene
            if (! $esRecepcion && $pago && $pago->estado === 'Pendiente' && (int) $pago->id_usuario !== $idUsuario) {
                if ($this->recepcionEstaCobrando($pago, $idUsuario)) {
                    return ['error' => self::MSJ_COBRO_RECEPCION];
                }
                $pago->update(['id_usuario' => $idUsuario]);   // recepción no registró nada: el paciente toma el cobro
            }

            if (! $pago || $pago->estado !== 'Pendiente'
                || ($esRecepcion && ! in_array(session($this->claveForma($cita)), ['en_linea', 'combinado'], true))) {
                return ['error' => 'Seleccione la forma de cobro'];
            }
            if (DetallePago::enLineaPendiente($pago->id_pago)) {
                return ['error' => 'Hay un pago en línea en curso; espere su resultado'];
            }

            // calcularSaldo: el pago en línea cubre lo que falta
            $saldo = $pago->saldoCentavos();
            if ($saldo <= 0) {
                return ['error' => 'No hay saldo pendiente; confirme el cobro'];
            }

            $detalle = DetallePago::insertarDetalle($pago->id_pago, $enLinea->id_metodo_pago, Pago::deCentavos($saldo));

            return ['detalle' => $detalle];
        });

        if (isset($resultado['error'])) {
            return $this->redirigirError($cita, $resultado);
        }

        /** @var DetallePago $detalle */
        $detalle = $resultado['detalle'];

        // crearSesionPago (fuera de la transacción: es una llamada por internet a Stripe)
        try {
            $sesion = $this->pasarela->crearSesionPago(
                $detalle,
                Cita::obtenerDetallePorId($cita->id_cita),
                route('pagos.retorno', $cita)
            );
        } catch (InvalidRequestException $e) {
            $detalle->marcarRechazado();
            Log::warning('Stripe rechazó la creación de la sesión: ' . $e->getMessage());
            $mensaje = $e->getStripeCode() === 'amount_too_small'
                ? 'El monto es menor al mínimo que acepta el pago en línea; cóbrelo en efectivo'
                : self::MSJ_PASARELA;
            return redirect()->route('pagos.pagar', $cita)->with('error', $mensaje);
        } catch (Throwable $e) {
            $detalle->marcarRechazado();
            Log::error('Error al crear la sesión de Stripe: ' . $e->getMessage());
            return redirect()->route('pagos.pagar', $cita)->with('error', self::MSJ_PASARELA);
        }

        $detalle->update(['id_sesion_pasarela' => $sesion['idSesion']]);

        // abrirPaginaPago: se guarda el client_secret para que la vista muestre el formulario de Stripe
        session([$this->claveClientSecret($detalle) => $sesion['clientSecret']]);

        return redirect()->route('pagos.pagar', $cita);
    }

    /** Pasos 5 y 6 (retornarPago → confirmarPagoEnLinea): Stripe devuelve al usuario con ?session_id=... */
    public function confirmarPagoEnLinea(Request $request, Cita $cita): RedirectResponse
    {
        $this->autorizarCita($request, $cita);
        $idSesion = (string) $request->query('session_id', '');

        $detalle = DetallePago::query()
            ->where('id_sesion_pasarela', $idSesion)
            ->whereHas('pago', fn ($p) => $p->where('id_cita', $cita->id_cita))
            ->first();
        abort_unless($idSesion !== '' && $detalle, 404);

        $pago = $detalle->pago;
        if ($pago->estado === 'Pagado') {
            return redirect()->route('pagos.comprobante', $cita);   // recargó la página después de pagar
        }
        if ($detalle->estado === 'Rechazado') {
            return redirect()->route('pagos.pagar', $cita)->with('error', self::MSJ_NO_APROBADO);
        }
        if ($detalle->estado !== 'Pendiente') {
            // Anulado: la reserva venció o se canceló mientras estaba en Stripe (si pagó, ya se reembolsó)
            return redirect()->route('pagos.index')->with('error', $this->mensajeNoPendiente($request, $cita->refresh()));
        }

        // 6 (verificarPago): se pregunta a Stripe; no se confía en que el usuario llegó a esta URL
        try {
            $verificacion = $this->pasarela->verificarPago($idSesion);
        } catch (Throwable $e) {
            Log::error('Error al verificar el pago en Stripe: ' . $e->getMessage());
            return redirect()->route('pagos.pagar', $cita)->with('error', self::MSJ_PASARELA);
        }

        $coincide = ($verificacion['metadata']['idDetallePago'] ?? null) === (string) $detalle->id_detalle_pago
            && $verificacion['centavos'] === Pago::aCentavos($detalle->monto)
            && $verificacion['moneda'] === config('services.stripe.moneda');

        // 6a: Stripe no confirma el pago → igual que 5a
        if (! $verificacion['pagado'] || ! $coincide) {
            $detalle->marcarRechazado();
            if ($verificacion['abierta'] || $verificacion['pagado']) {
                // Abierta: se expira. Cobrada pero con datos que no coinciden: se reembolsa (no se aprueba ese dinero).
                $this->pasarela->cerrarSesion($idSesion);
            }
            return redirect()->route('pagos.pagar', $cita)->with('error', self::MSJ_NO_APROBADO);
        }

        // 6: se guarda la referencia externa (payment_intent de Stripe)
        $detalle->update(['referencia_externa' => $verificacion['referenciaExterna']]);

        return $this->completarPago($request, $cita);
    }

    /** 5a: el actor decide no pagar en línea (botón "Usar otra forma de pago" junto al formulario de Stripe). */
    public function pagoEnLineaCancelado(Request $request, Cita $cita): RedirectResponse
    {
        $this->autorizarCita($request, $cita);

        $detalle = DetallePago::query()
            ->whereKey((int) $request->query('detalle'))
            ->where('estado', 'Pendiente')
            ->whereHas('pago', fn ($p) => $p->where('id_cita', $cita->id_cita))
            ->first();

        if ($detalle?->id_sesion_pasarela) {
            $redireccion = $this->resolverIntentoAnterior($cita);
            if ($redireccion) {
                return $redireccion;   // en realidad sí pagó: se confirma
            }
        }

        // El efectivo registrado se conserva; puede reintentar o completar el saldo en efectivo
        return redirect()->route('pagos.pagar', $cita)->with('error', self::MSJ_NO_APROBADO);
    }

    // ---------------------------------------------------------------
    // Paso 3c / 7: confirmar el cobro
    // ---------------------------------------------------------------

    /** 3c (confirmarPagoEfectivo): la suma ya cubre el total con efectivo → paso 7. */
    public function confirmarPagoEfectivo(Request $request, Cita $cita): RedirectResponse
    {
        $this->autorizarRecepcion($request);
        ReservaPago::liberarVencidas();

        return $this->completarPago($request, $cita);
    }

    /** "Cancelar" antes del paso 7: se devuelve el efectivo, se anula el pago y se libera el horario. */
    public function cancelarPago(Request $request, Cita $cita): RedirectResponse
    {
        $this->autorizarCita($request, $cita);
        // 7a: si la reserva ya venció, se libera como "Pago no realizado a tiempo" (no como cancelación del actor)
        ReservaPago::liberarVencidas();

        // El paciente no anula un cobro que recepción está registrando (el efectivo lo tiene recepción)
        $pago = Pago::obtenerPorCita($cita->id_cita);
        if (! $this->esRecepcion($request) && $pago && $pago->estado === 'Pendiente'
            && $this->recepcionEstaCobrando($pago, (int) $request->user()->id_usuario)) {
            return redirect()->route('pagos.pagar', $cita)->with('error', self::MSJ_COBRO_RECEPCION);
        }

        $resultado = ReservaPago::anularReserva($cita->id_cita, ReservaPago::MOTIVO_CANCELADA);
        if ($resultado === null) {
            return redirect()->route('pagos.index')->with('error', $this->mensajeNoPendiente($request, $cita->refresh()));
        }

        session()->forget($this->claveForma($cita));
        Bitacora::registrar('ANULAR_PAGO', 'pago', 'Cita: ' . $resultado['codigo'] . ' - Motivo: ' . ReservaPago::MOTIVO_CANCELADA);

        return redirect()->route('pagos.index')
            ->with('ok', 'Pago cancelado. La cita fue cancelada y el horario quedó libre.' . $this->textoDevolucion($resultado['efectivoDevolver']));
    }

    /** Paso 9 (mostrarComprobante): número, fecha, datos de la cita, monto por método y total. */
    public function mostrarComprobante(Request $request, Cita $cita): View
    {
        $this->autorizarCita($request, $cita);

        $cita = Cita::obtenerDetallePorId($cita->id_cita);
        $pago = Pago::obtenerPorCita($cita->id_cita);
        abort_unless($pago && $pago->estado === 'Pagado', 404);

        // Monto por método (si hubo dos partes en efectivo, se suman)
        $porMetodo = $pago->detalles()->with('metodoPago')->where('estado', 'Aprobado')->get()
            ->groupBy(fn ($d) => $d->metodoPago->nombre)
            ->map(fn ($grupo) => Pago::deCentavos($grupo->sum(fn ($d) => Pago::aCentavos($d->monto))));

        return view('pagos.pagar_cita.comprobante', [
            'cita' => $cita,
            'pago' => $pago,
            'porMetodo' => $porMetodo,
        ]);
    }

    // ---------------------------------------------------------------
    // Paso 7 a 9: completar el pago (común a efectivo y en línea)
    // ---------------------------------------------------------------

    private function completarPago(Request $request, Cita $cita): RedirectResponse
    {
        $resultado = DB::transaction(function () use ($request, $cita): array {
            [$citaBloqueada, $error] = $this->bloquearCitaVigente($request, $cita->id_cita, permitirPagada: true);
            if ($error) {
                return $error;   // 7a: si venció, la cita se canceló (y un pago en línea se reembolsa)
            }

            $pago = Pago::obtenerPorCita($cita->id_cita, bloquear: true);
            if ($pago?->estado === 'Pagado') {
                return ['ok' => true];   // ya estaba completo (doble clic o recarga)
            }
            if (! $pago || $pago->estado !== 'Pendiente') {
                return ['error' => 'Seleccione la forma de cobro'];
            }

            $pendientes = $pago->detalles()->with('metodoPago')->where('estado', 'Pendiente')->get();

            // El pago en línea debe estar verificado con Stripe (tiene referencia externa)
            if ($pendientes->contains(fn ($d) => ! $d->esEfectivo() && ! $d->referencia_externa)) {
                return ['error' => 'Hay un pago en línea en curso; espere su resultado'];
            }

            // validarSumaDetalles: la suma debe ser igual al total
            if ($pendientes->isEmpty() || $pago->saldoCentavos() !== 0) {
                return ['error' => 'La suma de los pagos no coincide con el total (Bs ' . $pago->monto_total . ')'];
            }

            // 8: el efectivo entra a la caja abierta de quien lo cobró (bloqueada: no se puede cerrar mientras tanto)
            $efectivos = $pendientes->filter(fn ($d) => $d->esEfectivo());
            $caja = null;
            if ($efectivos->isNotEmpty()) {
                $caja = SesionCaja::obtenerAbiertaPorUsuario($pago->id_usuario, bloquear: true);
                if (! $caja) {
                    return ['error' => self::MSJ_SIN_CAJA];
                }
            }

            // 7: aprobar detalles, comprobante, pago "Pagado" y cita "Programada"
            DetallePago::aprobarDetalles($pago->id_pago);
            $pago->marcarPagado(Pago::generarNumeroComprobante());
            $citaBloqueada->marcarProgramada();

            foreach ($efectivos as $detalle) {
                MovimientoCaja::insertarIngreso($caja->id_sesion_caja, $detalle->id_detalle_pago, $detalle->monto);
            }

            $montoEfectivo = Pago::deCentavos($efectivos->sum(fn ($d) => Pago::aCentavos($d->monto)));
            $montoEnLinea = Pago::deCentavos(Pago::aCentavos($pago->monto_total) - Pago::aCentavos($montoEfectivo));
            Bitacora::registrar('PAGAR_CITA', 'pago',
                'Cita: ' . $citaBloqueada->codigo . ' - Comprobante: ' . $pago->numero_comprobante
                . ' - Total: Bs ' . $pago->monto_total . ' (Efectivo: Bs ' . $montoEfectivo . ', En línea: Bs ' . $montoEnLinea . ')');

            // 8 / 8a: aviso al paciente; el correo se envía DESPUÉS de guardar y, si falla, no revierte el pago
            $aviso = Notificacion::registrarAviso($citaBloqueada->id_cita, 'Cita programada');
            DB::afterCommit(fn () => $aviso->enviarCorreo());

            return ['ok' => true];
        });

        if (isset($resultado['error'])) {
            return $this->redirigirError($cita, $resultado);
        }

        session()->forget($this->claveForma($cita));

        return redirect()->route('pagos.comprobante', $cita)->with('ok', 'Pago registrado');
    }

    // ---------------------------------------------------------------
    // Ayudas internas
    // ---------------------------------------------------------------

    /**
     * Bloquea la cita y verifica que siga pendiente y dentro de los 10 minutos.
     * Si venció en este momento, la cancela (7a). Devuelve [cita, null] o [null, ['error' => ..., 'aLista' => true]].
     */
    private function bloquearCitaVigente(Request $request, int $idCita, bool $permitirPagada = false): array
    {
        $cita = Cita::query()->whereKey($idCita)->lockForUpdate()->first();

        if ($permitirPagada && $cita->estado === 'Programada' && Pago::obtenerPorCita($idCita)?->estado === 'Pagado') {
            return [$cita, null];
        }
        if ($cita->estado !== 'Pendiente de pago') {
            return [null, ['error' => $this->mensajeNoPendiente($request, $cita), 'aLista' => true]];
        }
        if (! $cita->retencionVigente()) {
            $anulada = ReservaPago::anularReserva($idCita, ReservaPago::MOTIVO_VENCIDA);
            return [null, ['error' => self::MSJ_VENCIDO . '.' . $this->textoDevolucion($anulada['efectivoDevolver'] ?? '0.00'), 'aLista' => true]];
        }

        return [$cita, null];
    }

    /**
     * Intento de pago en línea anterior que quedó "Pendiente" (el usuario salió de Stripe sin volver por la URL de retorno).
     * Si en Stripe sí se pagó, se continúa con la confirmación; si no, se cierra la sesión y queda "Rechazado".
     */
    private function resolverIntentoAnterior(Cita $cita): ?RedirectResponse
    {
        $pago = Pago::obtenerPorCita($cita->id_cita);
        $anterior = $pago ? DetallePago::enLineaPendiente($pago->id_pago) : null;
        if (! $anterior) {
            return null;
        }

        if ($anterior->id_sesion_pasarela) {
            try {
                if ($this->pasarela->verificarPago($anterior->id_sesion_pasarela)['pagado']) {
                    return redirect()->route('pagos.retorno', ['cita' => $cita, 'session_id' => $anterior->id_sesion_pasarela]);
                }
            } catch (Throwable $e) {
                Log::error('Error al revisar el intento anterior en Stripe: ' . $e->getMessage());
                return redirect()->route('pagos.pagar', $cita)->with('error', self::MSJ_PASARELA);
            }
            $this->pasarela->cerrarSesion($anterior->id_sesion_pasarela);
        }

        $anterior->marcarRechazado();

        return null;
    }

    /**
     * client_secret del pago en línea que sigue abierto en Stripe (para volver a mostrar el formulario),
     * o null si no hay ninguno. Primero se busca en la sesión de Laravel; si no está (otro navegador), se pide a Stripe.
     */
    private function clientSecretAbierto(?DetallePago $detalle): ?string
    {
        if (! $detalle || ! $detalle->id_sesion_pasarela || $detalle->referencia_externa) {
            return null;
        }
        if ($guardado = session($this->claveClientSecret($detalle))) {
            return $guardado;
        }
        try {
            return $this->pasarela->verificarPago($detalle->id_sesion_pasarela)['clientSecret'];
        } catch (Throwable $e) {
            Log::error('Error al consultar la sesión de Stripe: ' . $e->getMessage());
            return null;
        }
    }

    private function claveClientSecret(DetallePago $detalle): string
    {
        return 'pagos.stripe.' . $detalle->id_detalle_pago;
    }

    /** Mensaje cuando la cita ya no está "Pendiente de pago". */
    private function mensajeNoPendiente(Request $request, Cita $cita): string
    {
        if ($cita->estado === 'Cancelada' && $cita->motivo_cancelacion === ReservaPago::MOTIVO_VENCIDA) {
            // Si había efectivo, se recuerda devolverlo (detalle "Anulado")
            $efectivo = 0;
            if ($this->esRecepcion($request) && ($pago = Pago::obtenerPorCita($cita->id_cita))) {
                $efectivo = $pago->detalles()->with('metodoPago')->where('estado', 'Anulado')->get()
                    ->filter(fn ($d) => $d->esEfectivo())->sum(fn ($d) => Pago::aCentavos($d->monto));
            }
            return self::MSJ_VENCIDO . '.' . $this->textoDevolucion(Pago::deCentavos($efectivo));
        }
        if ($cita->estado === 'Programada') {
            return 'La cita ya fue pagada';
        }

        return 'Esta cita no está pendiente de pago';
    }

    private function textoDevolucion(string $efectivo): string
    {
        return (float) $efectivo > 0 ? ' Devuelva el efectivo recibido (Bs ' . $efectivo . ').' : '';
    }

    private function redirigirError(Cita $cita, array $resultado): RedirectResponse
    {
        return ! empty($resultado['aLista'])
            ? redirect()->route('pagos.index')->with('error', $resultado['error'])
            : redirect()->route('pagos.pagar', $cita)->with('error', $resultado['error']);
    }

    /** ¿Otro usuario (recepción) empezó este cobro y tiene montos registrados sin terminar? */
    private function recepcionEstaCobrando(Pago $pago, int $idUsuario): bool
    {
        return (int) $pago->id_usuario !== $idUsuario
            && $pago->detalles()->where('estado', 'Pendiente')->exists();
    }

    /** Recepcionista (o administrador): tiene el permiso de cobrar. */
    private function esRecepcion(Request $request): bool
    {
        return $request->user()->tienePermiso('pagos.cobrar');
    }

    private function autorizarRecepcion(Request $request): void
    {
        abort_unless($this->esRecepcion($request), 403, 'No tiene permiso para acceder a esta opción.');
    }

    /** El recepcionista puede cobrar cualquier cita; el paciente solo las suyas. */
    private function autorizarCita(Request $request, Cita $cita): void
    {
        if ($this->esRecepcion($request)) {
            return;
        }
        abort_unless((int) $cita->id_paciente === $this->idPacienteSesion($request), 403, 'No puede pagar la cita de otro paciente.');
    }

    private function idPacienteSesion(Request $request): int
    {
        $paciente = Paciente::obtenerPorUsuario($request->user()->id_usuario);
        abort_unless($paciente, 403, 'No tiene permiso para acceder a esta opción.');

        return (int) $paciente->id_paciente;
    }

    /** Clave en la sesión de Laravel donde se recuerda la forma de cobro elegida para la cita. */
    private function claveForma(Cita $cita): string
    {
        return 'pagos.forma.' . $cita->id_cita;
    }
}
