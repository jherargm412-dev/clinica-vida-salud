{{-- CU22 Gestionar Caja · UI_Caja: mostrarResumenCierre (paso 9) --}}
@extends('compartido.app')

@section('titulo', 'Resumen del cierre de caja')

@php($dif = (float) $sesion->diferencia)

@section('contenido')

    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-0">
            Caja N° {{ $sesion->id_sesion_caja }} <span class="badge text-bg-secondary align-middle fs-6">Cerrada</span>
        </h1>
        <p class="text-muted mb-0">
            Abierta el {{ $sesion->apertura->format('d/m/Y H:i') }} · cerrada el {{ $sesion->cierre->format('d/m/Y H:i') }}
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3"><i class="bi bi-cash-stack me-1"></i> Ingresos en efectivo</h2>
                    @include('pagos.gestionar_caja._ingresos')
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3"><i class="bi bi-clipboard-check me-1"></i> Cierre</h2>
                    <dl class="row mb-3">
                        <dt class="col-7 fw-normal text-muted">Monto esperado</dt>
                        <dd class="col-5 text-end">Bs {{ number_format($montoEsperado, 2) }}</dd>
                        <dt class="col-7 fw-normal text-muted">Monto contado</dt>
                        <dd class="col-5 text-end">Bs {{ number_format($sesion->monto_contado, 2) }}</dd>
                        <dt class="col-7 border-top pt-2">Diferencia</dt>
                        <dd class="col-5 text-end border-top pt-2 fw-semibold">Bs {{ number_format($dif, 2) }}</dd>
                    </dl>

                    {{-- Sobrante, faltante o sin diferencia --}}
                    @if ($dif > 0)
                        <div class="alert alert-warning py-2 mb-3">Sobrante de Bs {{ number_format($dif, 2) }}</div>
                    @elseif ($dif < 0)
                        <div class="alert alert-danger py-2 mb-3">Faltante de Bs {{ number_format(abs($dif), 2) }}</div>
                    @else
                        <div class="alert alert-success py-2 mb-3">Sin diferencia: la caja cuadra</div>
                    @endif

                    <a href="{{ route('caja.index') }}" class="btn btn-primary w-100">
                        <i class="bi bi-safe me-1"></i> Ir a la caja
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection
