{{-- CU22 Gestionar Caja · UI_Caja: mostrarConfirmacionCierre (pasos 7 y 8) --}}
@extends('compartido.app')

@section('titulo', 'Confirmar cierre de caja')

@php($dif = (float) $diferencia)

@section('contenido')

    <div class="mb-3">
        <a href="{{ route('caja.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a la caja
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">Confirmar cierre de caja</h1>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 520px">
        <div class="card-body">
            <dl class="row mb-3">
                <dt class="col-7 fw-normal text-muted">Monto esperado</dt>
                <dd class="col-5 text-end">Bs {{ number_format($montoEsperado, 2) }}</dd>
                <dt class="col-7 fw-normal text-muted">Monto contado</dt>
                <dd class="col-5 text-end">Bs {{ number_format($montoContado, 2) }}</dd>
                <dt class="col-7 border-top pt-2">Diferencia</dt>
                <dd class="col-5 text-end border-top pt-2 fw-semibold {{ $dif > 0 ? 'text-primary' : ($dif < 0 ? 'text-danger' : 'text-success') }}">
                    Bs {{ number_format($dif, 2) }}
                </dd>
            </dl>

            <div class="alert {{ $dif == 0 ? 'alert-success' : 'alert-warning' }} py-2">
                @if ($dif > 0)
                    Hay un <strong>sobrante</strong> de Bs {{ number_format($dif, 2) }}.
                @elseif ($dif < 0)
                    Hay un <strong>faltante</strong> de Bs {{ number_format(abs($dif), 2) }}.
                @else
                    La caja <strong>cuadra</strong>: no hay diferencia.
                @endif
                Al confirmar, la caja se cierra y ya no recibe más ingresos.
            </div>

            <div class="d-flex gap-2">
                {{-- Paso 8: confirmar --}}
                <form method="POST" action="{{ route('caja.cerrar') }}">
                    @csrf
                    <input type="hidden" name="monto_contado" value="{{ $montoContado }}">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-lock me-1"></i> Confirmar cierre
                    </button>
                </form>
                {{-- 8a: no confirma → vuelve al paso 5 sin cambios --}}
                <a href="{{ route('caja.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </div>
    </div>

@endsection
