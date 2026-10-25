{{-- CU15 Cancelar Cita · confirmación de motivo --}}
@extends('compartido.app')

@section('titulo', 'Confirmar cancelación')

@section('contenido')
    <div class="mb-3">
        <a href="{{ route('citas.cancelar.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a citas
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">Confirmar cancelación</h1>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 fw-semibold">Cita seleccionada</h2>
            <dl class="row mb-0">
                <dt class="col-sm-3">Código</dt>
                <dd class="col-sm-9">{{ $cita->codigo }}</dd>
                <dt class="col-sm-3">Paciente</dt>
                <dd class="col-sm-9">{{ $cita->paciente?->nombre_completo }} (CI: {{ $cita->paciente?->ci }})</dd>
                <dt class="col-sm-3">Especialidad</dt>
                <dd class="col-sm-9">{{ $cita->especialidad?->nombre }}</dd>
                <dt class="col-sm-3">Médico</dt>
                <dd class="col-sm-9">{{ $cita->medico?->nombre_completo }}</dd>
                <dt class="col-sm-3">Fecha y horario</dt>
                <dd class="col-sm-9">{{ $cita->fecha->format('d/m/Y') }} · {{ substr($cita->hora_inicio, 0, 5) }}–{{ substr($cita->hora_fin, 0, 5) }}</dd>
                <dt class="col-sm-3">Estado</dt>
                <dd class="col-sm-9">{{ $cita->estado }}</dd>
            </dl>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('citas.cancelar.guardar', $cita) }}">
                @csrf
                <div class="mb-3">
                    <label for="motivo_cancelacion" class="form-label">Motivo de cancelación</label>
                    <textarea id="motivo_cancelacion" name="motivo_cancelacion" rows="3" maxlength="200"
                              class="form-control" required>{{ old('motivo_cancelacion') }}</textarea>
                    <div class="form-text">Máximo 200 caracteres.</div>
                    @error('motivo_cancelacion') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-calendar-x me-1"></i> Confirmar cancelación
                    </button>
                    <a href="{{ route('citas.cancelar.index') }}" class="btn btn-outline-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>
@endsection
