{{-- CU21 Pagar Cita · UI_PagarCita: mostrarResumenPago, seleccionarFormaCobro, registrarEfectivo, pagarEnLinea, confirmarCobro --}}
@extends('compartido.app')

@section('titulo', 'Pagar cita ' . $cita->codigo)

@php
    $nombresForma = ['efectivo' => 'Solo efectivo', 'en_linea' => 'Solo pago en línea', 'combinado' => 'Efectivo + pago en línea'];
    $hayPendientes = $detalles->contains(fn ($d) => $d->estado === 'Pendiente');
    $saldoCero = (float) $saldo <= 0;
    // El pago en línea ya verificado con Stripe (tiene referencia) no impide confirmar
    $enLineaSinVerificar = $enLineaPendiente && ! $enLineaPendiente->referencia_externa;
    $coloresEstado = ['Pendiente' => 'warning', 'Aprobado' => 'success', 'Rechazado' => 'danger', 'Anulado' => 'secondary'];
@endphp

@section('contenido')

    <div class="mb-3">
        <a href="{{ route('pagos.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a citas pendientes de pago
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">Pagar cita {{ $cita->codigo }}</h1>
    </div>

    {{-- Misma barra de pasos que "Registrar cita": el pago es el último paso (CU13 <<include>> CU21) --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            @include('citas.registrar_cita._pasos', [
                'pasos' => $esRecepcion
                    ? ['paciente', 'especialidad', 'medico', 'fecha', 'resumen']
                    : ['especialidad', 'medico', 'fecha', 'resumen'],
                'actual' => 'resumen',
            ])
        </div>
    </div>

    <div class="row g-4">

        {{-- Resumen de la cita (paso 1) --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3"><i class="bi bi-calendar-check me-1"></i> Resumen de la cita</h2>
                    <dl class="row mb-0">
                        <dt class="col-5 fw-normal text-muted">Código</dt>
                        <dd class="col-7">{{ $cita->codigo }}</dd>
                        <dt class="col-5 fw-normal text-muted">Paciente</dt>
                        <dd class="col-7">{{ $cita->paciente->nombre_completo }}</dd>
                        <dt class="col-5 fw-normal text-muted">Especialidad</dt>
                        <dd class="col-7">{{ $cita->especialidad->nombre }}</dd>
                        <dt class="col-5 fw-normal text-muted">Médico</dt>
                        <dd class="col-7">{{ $cita->medico->nombre_completo }}</dd>
                        <dt class="col-5 fw-normal text-muted">Fecha</dt>
                        <dd class="col-7">{{ $cita->fecha->format('d/m/Y') }}</dd>
                        <dt class="col-5 fw-normal text-muted">Hora</dt>
                        <dd class="col-7">{{ substr($cita->hora_inicio, 0, 5) }} – {{ substr($cita->hora_fin, 0, 5) }}</dd>
                        <dt class="col-5 border-top pt-2">Monto total</dt>
                        <dd class="col-7 border-top pt-2 fs-5 fw-semibold">Bs {{ number_format($montoTotal, 2) }}</dd>
                        <dt class="col-5">Tiempo restante</dt>
                        <dd class="col-7 mb-0">
                            <span class="fs-5 fw-semibold font-monospace"
                                  data-cuenta-regresiva="{{ $segundosRestantes }}" data-recargar></span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Cobro --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3"><i class="bi bi-cash-coin me-1"></i> Cobro</h2>

                    @if ($esRecepcion && ! $forma)
                        {{-- Paso 2: elegir la forma de cobro --}}
                        <p class="mb-2">Elija la forma de cobro:</p>
                        <div class="d-grid gap-2">
                            @foreach ($nombresForma as $valor => $nombre)
                                @php($habilitada = ($valor === 'en_linea' || $hayEfectivo) && ($valor === 'efectivo' || $hayEnLinea))
                                <form method="POST" action="{{ route('pagos.forma', $cita) }}">
                                    @csrf
                                    <input type="hidden" name="forma" value="{{ $valor }}">
                                    <button type="submit" class="btn btn-outline-primary w-100 text-start" @disabled(! $habilitada)>
                                        <i class="bi {{ $valor === 'efectivo' ? 'bi-cash' : ($valor === 'en_linea' ? 'bi-credit-card' : 'bi-wallet2') }} me-1"></i>
                                        {{ $nombre }}
                                        @unless ($habilitada)
                                            <small class="text-muted">(método deshabilitado)</small>
                                        @endunless
                                    </button>
                                </form>
                            @endforeach
                        </div>
                        <p class="text-muted small mt-2 mb-0">
                            Métodos de pago activos: {{ $metodos->pluck('nombre')->join(', ') }}
                        </p>
                    @else
                        {{-- Forma elegida --}}
                        @if ($esRecepcion)
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <span>Forma de cobro: <strong>{{ $nombresForma[$forma] }}</strong></span>
                                @unless ($hayPendientes)
                                    {{-- Se puede cambiar mientras no haya nada cobrándose --}}
                                    <form method="POST" action="{{ route('pagos.forma', $cita) }}" class="d-flex gap-1">
                                        @csrf
                                        <select name="forma" class="form-select form-select-sm" aria-label="Forma de cobro">
                                            @foreach ($nombresForma as $valor => $nombre)
                                                <option value="{{ $valor }}" @selected($valor === $forma)>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Cambiar</button>
                                    </form>
                                @endunless
                            </div>
                        @endif

                        {{-- Detalles del pago (efectivo / en línea) --}}
                        @if ($detalles->isNotEmpty())
                            <table class="table table-sm align-middle mb-3">
                                <thead class="table-light">
                                    <tr><th>Método</th><th class="text-end">Monto</th><th>Estado</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($detalles as $detalle)
                                        <tr>
                                            <td>{{ $detalle->metodoPago->nombre }}</td>
                                            <td class="text-end">Bs {{ number_format($detalle->monto, 2) }}</td>
                                            <td><span class="badge text-bg-{{ $coloresEstado[$detalle->estado] }}">{{ $detalle->estado }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        <p class="fs-5 mb-3">Saldo pendiente: <strong>Bs {{ number_format($saldo, 2) }}</strong></p>

                        @if ($enLineaSinVerificar && $clientSecret)
                            {{-- Pasos 4 y 5: formulario de Stripe incrustado en esta página --}}
                            <p class="mb-2">
                                Pago en línea: <strong>Bs {{ number_format($enLineaPendiente->monto, 2) }}</strong>.
                                Los datos de la tarjeta los procesa Stripe de forma segura; la clínica no los ve ni los guarda.
                            </p>
                            @if (str_starts_with((string) $clavePublica, 'pk_test_'))
                                <div class="alert alert-info small py-2">
                                    <i class="bi bi-info-circle me-1"></i> Modo prueba: use la tarjeta 4242 4242 4242 4242,
                                    cualquier fecha futura y cualquier CVC.
                                </div>
                            @endif
                            <div id="checkout-stripe" class="cvs-checkout mb-2"
                                 data-clave-publica="{{ $clavePublica }}" data-client-secret="{{ $clientSecret }}">
                                <div class="text-center text-muted py-5">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Cargando el formulario de pago...
                                </div>
                            </div>
                            {{-- 5a: el actor decide no pagar en línea --}}
                            <a href="{{ route('pagos.cancelado', ['cita' => $cita, 'detalle' => $enLineaPendiente->id_detalle_pago]) }}"
                               class="btn btn-link px-0">
                                <i class="bi bi-arrow-left me-1"></i> No pagar en línea{{ $esRecepcion ? ' / usar otra forma de pago' : '' }}
                            </a>
                        @elseif ($enLineaSinVerificar)
                            {{-- Un intento de pago en línea quedó sin terminar y la sesión de Stripe ya no está abierta --}}
                            <div class="alert alert-info">
                                Hay un pago en línea iniciado por Bs {{ number_format($enLineaPendiente->monto, 2) }}.
                                @if ($enLineaPendiente->id_sesion_pasarela)
                                    Si ya pagó, verifíquelo; si no, puede intentarlo de nuevo.
                                    <div class="d-flex gap-2 mt-2">
                                        <a href="{{ route('pagos.retorno', ['cita' => $cita, 'session_id' => $enLineaPendiente->id_sesion_pasarela]) }}"
                                           class="btn btn-sm btn-primary">Verificar pago</a>
                                        <form method="POST" action="{{ route('pagos.en_linea', $cita) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Intentar de nuevo</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @elseif (! $saldoCero)
                            {{-- Paso 3: efectivo (solo recepcionista) --}}
                            @if ($esRecepcion && in_array($forma, ['efectivo', 'combinado'], true))
                                @if (! $cajaAbierta)
                                    {{-- 3a: sin caja abierta --}}
                                    <div class="alert alert-warning">
                                        Abra la caja para registrar pagos en efectivo.
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            @if (auth()->user()->tienePermiso('caja.gestionar'))
                                                <a href="{{ route('caja.index') }}" class="btn btn-sm btn-warning">Ir a la caja</a>
                                            @endif
                                            @if ($hayEnLinea && ! $hayPendientes)
                                                <form method="POST" action="{{ route('pagos.forma', $cita) }}">
                                                    @csrf
                                                    <input type="hidden" name="forma" value="en_linea">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Cobrar solo en línea</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('pagos.efectivo', $cita) }}" class="needs-validation mb-3" novalidate>
                                        @csrf
                                        <label for="monto_efectivo" class="form-label">
                                            Efectivo recibido
                                            @if ($forma === 'efectivo')
                                                <span class="text-muted small">(debe ser igual al total)</span>
                                            @endif
                                        </label>
                                        <div class="input-group has-validation">
                                            <span class="input-group-text">Bs</span>
                                            <input type="number" id="monto_efectivo" name="monto_efectivo" required
                                                   min="0.01" max="{{ $saldo }}" step="0.01"
                                                   value="{{ old('monto_efectivo', $forma === 'efectivo' ? $saldo : '') }}"
                                                   class="form-control @error('monto_efectivo') is-invalid @enderror">
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-cash me-1"></i> Registrar efectivo
                                            </button>
                                            <div class="invalid-feedback">
                                                @error('monto_efectivo') {{ $message }} @else El monto en efectivo debe ser mayor a 0 y no superar Bs {{ $saldo }} @enderror
                                            </div>
                                        </div>
                                    </form>
                                @endif
                            @endif

                            {{-- Paso 4: pago en línea por el saldo --}}
                            @if (in_array($forma, ['en_linea', 'combinado'], true))
                                @if ($hayEnLinea)
                                    <form method="POST" action="{{ route('pagos.en_linea', $cita) }}" class="needs-validation" novalidate>
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-credit-card me-1"></i> Pagar en línea (Bs {{ number_format($saldo, 2) }})
                                        </button>
                                        <div class="form-text">El formulario seguro de Stripe aparecerá en esta misma pantalla.</div>
                                    </form>
                                @else
                                    <div class="alert alert-warning mb-0">El pago en línea está deshabilitado.</div>
                                @endif
                            @endif
                        @endif

                        {{-- 3c / paso 7: el total ya está cubierto → confirmar el cobro --}}
                        @if ($saldoCero && ! $enLineaSinVerificar && $esRecepcion)
                            <form method="POST" action="{{ route('pagos.confirmar', $cita) }}" class="needs-validation" novalidate>
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check2-circle me-1"></i> Confirmar cobro
                                </button>
                            </form>
                        @endif
                    @endif
                </div>

                {{-- Cancelar (antes del paso 7): devuelve el efectivo, anula el pago y cancela la cita --}}
                <div class="card-footer bg-transparent text-end">
                    <form method="POST" action="{{ route('pagos.cancelar', $cita) }}" class="d-inline"
                          data-confirmar="¿Cancelar el pago? La cita {{ $cita->codigo }} se cancelará y el horario quedará libre.">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-x-circle me-1"></i> Cancelar pago
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@if ($clientSecret)
    @push('scripts')
        {{-- Stripe.js siempre se carga desde js.stripe.com (requisito de seguridad de Stripe) --}}
        <script src="https://js.stripe.com/endive/stripe.js"></script>
        <script>
            // Muestra el formulario de Stripe dentro de la página. El client_secret ya viene en la página
            // (lo creó el servidor), así no hace falta pedirlo por una API.
            (async () => {
                const contenedor = document.getElementById('checkout-stripe');
                try {
                    const stripe = Stripe(contenedor.dataset.clavePublica);
                    const checkout = await stripe.createEmbeddedCheckoutPage({
                        fetchClientSecret: async () => contenedor.dataset.clientSecret,
                    });
                    contenedor.innerHTML = '';
                    checkout.mount('#checkout-stripe');
                } catch (error) {
                    contenedor.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el formulario de pago. ' +
                        'Recargue la página o use otra forma de pago.</div>';
                }
            })();
        </script>
    @endpush
@endif
