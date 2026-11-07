{{-- CU21 Pagar Cita · UI_PagarCita: mostrarComprobante (paso 9), con opción de imprimir --}}
@extends('compartido.app')

@section('titulo', 'Comprobante ' . $pago->numero_comprobante)

{{-- Al imprimir solo sale el comprobante (sin barra superior, menú ni botones) --}}
@push('estilos')
    <style>
        @media print {
            .cvs-navbar, .cvs-sidebar, .alert, .d-print-none { display: none !important; }
            main { padding: 0 !important; }
            .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
        }
    </style>
@endpush

@section('contenido')

    <div class="d-flex flex-wrap gap-2 mb-3 d-print-none">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Imprimir
        </button>
        <a href="{{ route('pagos.index', ['vista' => 'pagadas']) }}" class="btn btn-outline-secondary">Ver citas pagadas</a>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 640px">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="fw-semibold"><i class="bi bi-heart-pulse-fill text-primary"></i> Clínica Vida Salud</div>
                    <h1 class="h4 fw-semibold mt-2 mb-0">Comprobante de pago</h1>
                </div>
                <div class="text-end">
                    <div class="fs-5 fw-semibold">{{ $pago->numero_comprobante }}</div>
                    <div class="text-muted small">{{ $pago->fecha->format('d/m/Y H:i') }}</div>
                </div>
            </div>

            <dl class="row mb-3">
                <dt class="col-4 fw-normal text-muted">Cita</dt>
                <dd class="col-8">{{ $cita->codigo }} <span class="badge text-bg-primary">{{ $cita->estado }}</span></dd>
                <dt class="col-4 fw-normal text-muted">Paciente</dt>
                <dd class="col-8">{{ $cita->paciente->nombre_completo }} (CI {{ $cita->paciente->ci }})</dd>
                <dt class="col-4 fw-normal text-muted">Especialidad</dt>
                <dd class="col-8">{{ $cita->especialidad->nombre }}</dd>
                <dt class="col-4 fw-normal text-muted">Médico</dt>
                <dd class="col-8">{{ $cita->medico->nombre_completo }}</dd>
                <dt class="col-4 fw-normal text-muted">Fecha y hora</dt>
                <dd class="col-8">{{ $cita->fecha->format('d/m/Y') }} · {{ substr($cita->hora_inicio, 0, 5) }} – {{ substr($cita->hora_fin, 0, 5) }}</dd>
            </dl>

            <table class="table mb-0">
                <thead class="table-light">
                    <tr><th>Método de pago</th><th class="text-end">Monto</th></tr>
                </thead>
                <tbody>
                    @foreach ($porMetodo as $metodo => $monto)
                        <tr><td>{{ $metodo }}</td><td class="text-end">Bs {{ number_format($monto, 2) }}</td></tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-semibold"><td>Total</td><td class="text-end">Bs {{ number_format($pago->monto_total, 2) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>

@endsection
