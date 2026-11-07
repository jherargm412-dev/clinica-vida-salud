{{-- CU22 Gestionar Caja · UI_Caja: mostrarFormularioApertura (pasos 2 y 3) --}}
@extends('compartido.app')

@section('titulo', 'Caja')

@section('contenido')

    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-0">Caja</h1>
        <p class="text-muted mb-0">No tiene una caja abierta. Ábrala para poder recibir pagos en efectivo.</p>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 520px">
        <div class="card-body">
            <h2 class="h5 fw-semibold mb-3"><i class="bi bi-safe me-1"></i> Abrir caja</h2>

            <form method="POST" action="{{ route('caja.abrir') }}" class="needs-validation" novalidate>
                @csrf

                <div class="mb-4">
                    <label for="monto_inicial" class="form-label">Efectivo inicial <span class="text-danger">*</span></label>
                    <div class="input-group has-validation">
                        <span class="input-group-text">Bs</span>
                        <input type="number" id="monto_inicial" name="monto_inicial" required min="0" step="0.01" max="99999999.99"
                               value="{{ old('monto_inicial') }}"
                               class="form-control @error('monto_inicial') is-invalid @enderror">
                        <div class="invalid-feedback">
                            @error('monto_inicial') {{ $message }} @else Ingrese el efectivo con el que abre la caja @enderror
                        </div>
                    </div>
                    <div class="form-text">Efectivo con el que empieza (cambio). Puede ser 0.</div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-unlock me-1"></i> Abrir caja
                </button>
            </form>
        </div>
    </div>

@endsection
