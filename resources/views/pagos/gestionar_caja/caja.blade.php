{{-- CU22 Gestionar Caja · UI_Caja: mostrarResumenCaja (paso 5) y cerrarCaja (paso 6) --}}
@extends('compartido.app')

@section('titulo', 'Caja')

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Caja <span class="badge text-bg-success align-middle fs-6">Abierta</span></h1>
            <p class="text-muted mb-0">
                Caja N° {{ $sesion->id_sesion_caja }} · abierta el {{ $sesion->apertura->format('d/m/Y') }}
                a las {{ $sesion->apertura->format('H:i') }}
            </p>
        </div>
    </div>

    <div class="row g-4">
        {{-- Ingresos en efectivo y monto esperado --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3"><i class="bi bi-cash-stack me-1"></i> Ingresos en efectivo</h2>
                    @include('pagos.gestionar_caja._ingresos')
                </div>
            </div>
        </div>

        {{-- Paso 6: cerrar caja con el efectivo contado --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3"><i class="bi bi-lock me-1"></i> Cerrar caja</h2>
                    <p class="text-muted small">Cuente el efectivo que tiene en la caja e ingrese el total.</p>

                    {{-- GET: solo calcula la diferencia y pide confirmación; todavía no cierra (paso 7) --}}
                    <form method="GET" action="{{ route('caja.cerrar.confirmar') }}" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="monto_contado" class="form-label">Monto contado <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">Bs</span>
                                <input type="number" id="monto_contado" name="monto_contado" required min="0" step="0.01" max="99999999.99"
                                       value="{{ old('monto_contado') }}"
                                       class="form-control @error('monto_contado') is-invalid @enderror">
                                <div class="invalid-feedback">
                                    @error('monto_contado') {{ $message }} @else Ingrese el total de efectivo que contó en la caja @enderror
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-lock me-1"></i> Cerrar caja
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
