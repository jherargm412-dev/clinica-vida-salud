{{-- CU22 · Fragmento compartido: lista de ingresos de la caja (fecha, comprobante y monto) y totales.
     Recibe: $sesion, $ingresos, $totalIngresos, $montoEsperado --}}
<div class="table-responsive mb-3">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Fecha y hora</th>
                <th>Comprobante</th>
                <th>Cita</th>
                <th class="text-end">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ingresos as $ingreso)
                <tr>
                    <td>{{ $ingreso->fecha->format('d/m/Y H:i') }}</td>
                    <td class="fw-semibold">{{ $ingreso->detallePago->pago->numero_comprobante }}</td>
                    <td>{{ $ingreso->detallePago->pago->cita->codigo }}</td>
                    <td class="text-end text-nowrap">Bs {{ number_format($ingreso->monto, 2) }}</td>
                </tr>
            @empty
                {{-- 5a: la caja no tiene ingresos --}}
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">Aún no hay ingresos en esta caja</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<dl class="row mb-0" style="max-width: 420px; margin-left: auto">
    <dt class="col-7 fw-normal text-muted">Monto inicial</dt>
    <dd class="col-5 text-end mb-1">Bs {{ number_format($sesion->monto_inicial, 2) }}</dd>
    <dt class="col-7 fw-normal text-muted">Total de ingresos</dt>
    <dd class="col-5 text-end mb-1">Bs {{ number_format($totalIngresos, 2) }}</dd>
    <dt class="col-7 border-top pt-1">Monto esperado</dt>
    <dd class="col-5 text-end border-top pt-1 fw-semibold mb-0">Bs {{ number_format($montoEsperado, 2) }}</dd>
</dl>
