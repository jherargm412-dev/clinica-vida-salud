{{-- CU21 Pagar Cita · dos pestañas:
     "Pendientes de pago" (paso 1: el actor selecciona "Pagar") y
     "Pagadas" (RF25: consultar y reimprimir el comprobante). --}}
@extends('compartido.app')

@section('titulo', 'Pagar cita')

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Pagos de citas</h1>
            <p class="text-muted mb-0">
                @if ($vista === 'pendientes')
                    Cada cita reserva su horario durante 10 minutos; si no se paga a tiempo, se cancela y el horario queda libre.
                @else
                    Citas ya pagadas: abra el comprobante para consultarlo o imprimirlo nuevamente.
                @endif
            </p>
        </div>
        @if (auth()->user()->tienePermiso('citas.registrar'))
            <a href="{{ route('citas.crear') }}" class="btn btn-outline-primary">
                <i class="bi bi-calendar-plus me-1"></i> Registrar cita
            </a>
        @endif
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $vista === 'pendientes' ? 'active' : '' }}" href="{{ route('pagos.index') }}"
               @if ($vista === 'pendientes') aria-current="page" @endif>
                <i class="bi bi-hourglass-split me-1"></i> Pendientes de pago
                @if ($citas->isNotEmpty())
                    <span class="badge rounded-pill text-bg-warning ms-1">{{ $citas->count() }}</span>
                @endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $vista === 'pagadas' ? 'active' : '' }}" href="{{ route('pagos.index', ['vista' => 'pagadas']) }}"
               @if ($vista === 'pagadas') aria-current="page" @endif>
                <i class="bi bi-receipt me-1"></i> Pagadas
            </a>
        </li>
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($vista === 'pendientes')
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                @if ($esRecepcion)
                                    <th>Paciente</th>
                                @endif
                                <th>Especialidad</th>
                                <th>Médico</th>
                                <th>Fecha y hora</th>
                                <th>Tiempo restante</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($citas as $cita)
                                <tr>
                                    <td class="fw-semibold">{{ $cita->codigo }}</td>
                                    @if ($esRecepcion)
                                        <td>{{ $cita->paciente->nombre_completo }}</td>
                                    @endif
                                    <td>{{ $cita->especialidad->nombre }}</td>
                                    <td>{{ $cita->medico->nombre_completo }}</td>
                                    <td class="text-nowrap">
                                        {{ $cita->fecha->format('d/m/Y') }} · {{ substr($cita->hora_inicio, 0, 5) }}
                                    </td>
                                    <td>
                                        <span class="fw-semibold font-monospace" data-cuenta-regresiva="{{ $cita->segundosRestantesPago() }}"></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('pagos.pagar', $cita) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-credit-card"></i> Pagar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $esRecepcion ? 7 : 6 }}" class="text-center text-muted py-4">
                                        No hay citas pendientes de pago
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                {{-- Buscador (solo recepción: el paciente ve únicamente sus pagos) --}}
                @if ($esRecepcion)
                    <form method="GET" action="{{ route('pagos.index') }}" class="row g-2 mb-3">
                        <input type="hidden" name="vista" value="pagadas">
                        <div class="col-sm-8 col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="buscar" value="{{ $texto }}" class="form-control"
                                       placeholder="N° de comprobante, código de cita, CI o nombre del paciente">
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-outline-primary">Buscar</button>
                            @if ($texto)
                                <a href="{{ route('pagos.index', ['vista' => 'pagadas']) }}" class="btn btn-link">Limpiar</a>
                            @endif
                        </div>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Comprobante</th>
                                <th>Cita</th>
                                @if ($esRecepcion)
                                    <th>Paciente</th>
                                @endif
                                <th>Especialidad · Médico</th>
                                <th>Fecha de la cita</th>
                                <th>Pagado el</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pagados as $pago)
                                <tr>
                                    <td class="fw-semibold">{{ $pago->numero_comprobante }}</td>
                                    <td>{{ $pago->cita->codigo }}</td>
                                    @if ($esRecepcion)
                                        <td>{{ $pago->cita->paciente->nombre_completo }}</td>
                                    @endif
                                    <td>
                                        {{ $pago->cita->especialidad->nombre }}
                                        <div class="small text-muted">{{ $pago->cita->medico->nombre_completo }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        {{ $pago->cita->fecha->format('d/m/Y') }} · {{ substr($pago->cita->hora_inicio, 0, 5) }}
                                    </td>
                                    <td class="text-nowrap">{{ $pago->fecha?->format('d/m/Y H:i') }}</td>
                                    <td class="text-end text-nowrap">Bs {{ number_format($pago->monto_total, 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('pagos.comprobante', $pago->cita) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                            <i class="bi bi-receipt"></i> Ver comprobante
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $esRecepcion ? 8 : 7 }}" class="text-center text-muted py-4">
                                        {{ $texto ? 'No se encontraron pagos con ese dato' : 'Todavía no hay citas pagadas' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $pagados->links() }}</div>
            @endif
        </div>
    </div>

@endsection
