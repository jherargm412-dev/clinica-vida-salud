{{-- CU20 Gestionar Tarifas y Métodos de Pago · UI_TarifasMetodosPago: nuevaTarifa / modificarTarifa --}}
@extends('compartido.app')

{{-- $tarifa->exists: false = Nueva tarifa (Registrar) · true = Modificar --}}
@php($esNueva = ! $tarifa->exists)

@section('titulo', $esNueva ? 'Nueva tarifa' : 'Modificar tarifa')

@section('contenido')

    <div class="mb-3">
        <a href="{{ route('tarifas.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a tarifas y métodos de pago
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">{{ $esNueva ? 'Nueva tarifa' : 'Modificar tarifa' }}</h1>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 640px">
        <div class="card-body">

            <form method="POST" class="needs-validation" novalidate
                  action="{{ $esNueva ? route('tarifas.guardar') : route('tarifas.actualizar', $tarifa) }}">
                @csrf

                {{-- Especialidad: en Registrar se elige entre las activas sin tarifa (paso 4); en Modificar no cambia --}}
                <div class="mb-3">
                    <label for="id_especialidad" class="form-label">Especialidad <span class="text-danger">*</span></label>
                    @if ($esNueva)
                        <select id="id_especialidad" name="id_especialidad" required
                                class="form-select @error('id_especialidad') is-invalid @enderror">
                            <option value="">Seleccione una especialidad</option>
                            @foreach ($especialidades as $especialidad)
                                <option value="{{ $especialidad->id_especialidad }}"
                                        @selected(old('id_especialidad') == $especialidad->id_especialidad)>
                                    {{ $especialidad->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">
                            @error('id_especialidad') {{ $message }} @else Seleccione una especialidad @enderror
                        </div>
                    @else
                        <input type="text" id="id_especialidad" class="form-control" readonly
                               value="{{ $tarifa->especialidad->nombre }}">
                        <div class="form-text">
                            Vigente desde el {{ $tarifa->vigente_desde->format('d/m/Y') }}.
                            Al guardar, la vigencia pasa a ser desde hoy. Los pagos ya realizados conservan su monto.
                        </div>
                    @endif
                </div>

                {{-- Monto (obligatorio, mayor a 0, hasta 2 decimales) --}}
                <div class="mb-4">
                    <label for="monto" class="form-label">Monto de la consulta <span class="text-danger">*</span></label>
                    <div class="input-group has-validation">
                        <span class="input-group-text">Bs</span>
                        <input type="number" id="monto" name="monto" required min="0.01" step="0.01" max="99999999.99"
                               value="{{ old('monto', $tarifa->monto) }}"
                               class="form-control @error('monto') is-invalid @enderror">
                        <div class="invalid-feedback">
                            @error('monto') {{ $message }} @else Ingrese un monto mayor a 0 @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar
                    </button>
                    {{-- Cancelar: vuelve a la lista sin guardar cambios --}}
                    <a href="{{ route('tarifas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

@endsection
