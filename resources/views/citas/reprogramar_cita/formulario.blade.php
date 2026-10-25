{{-- CU14 Reprogramar Cita · UI_reprogramarCita --}}
@extends('compartido.app')

@section('titulo', 'Reprogramar cita')

@section('contenido')
    <div class="mb-3">
        <a href="{{ route('citas.reprogramar.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a citas
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">Reprogramar cita</h1>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 fw-semibold">Cita actual</h2>
            <dl class="row mb-0">
                <dt class="col-sm-3">Código</dt>
                <dd class="col-sm-9">{{ $cita->codigo }}</dd>
                <dt class="col-sm-3">Paciente</dt>
                <dd class="col-sm-9">{{ $cita->paciente?->nombre_completo }} (CI: {{ $cita->paciente?->ci }})</dd>
                <dt class="col-sm-3">Especialidad</dt>
                <dd class="col-sm-9">{{ $cita->especialidad?->nombre }}</dd>
                <dt class="col-sm-3">Médico</dt>
                <dd class="col-sm-9">{{ $cita->medico?->nombre_completo }}</dd>
                <dt class="col-sm-3">Fecha y horario actual</dt>
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

    @if ($horarioSeleccionado)
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 fw-semibold">Nuevo horario seleccionado</h2>
                <p class="alert alert-info">
                    {{ \Carbon\Carbon::parse($horarioSeleccionado['nueva_fecha'])->format('d/m/Y') }} ·
                    {{ $horarioSeleccionado['nueva_hora_inicio'] }}–{{ $horarioSeleccionado['nueva_hora_fin'] }}
                </p>
                <form method="POST" action="{{ route('citas.reprogramar.guardar', $cita) }}">
                    @csrf
                    <input type="hidden" name="nueva_fecha" value="{{ $horarioSeleccionado['nueva_fecha'] }}">
                    <input type="hidden" name="nueva_hora_inicio" value="{{ $horarioSeleccionado['nueva_hora_inicio'] }}">
                    <input type="hidden" name="nueva_hora_fin" value="{{ $horarioSeleccionado['nueva_hora_fin'] }}">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Guardar reprogramación
                        </button>
                        <a href="{{ $urlDisponibilidad }}" class="btn btn-outline-secondary">Elegir otro horario</a>
                        <a href="{{ route('citas.reprogramar.index') }}" class="btn btn-link">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            Seleccione una nueva fecha para consultar los bloques libres del mismo médico y especialidad.
            <a href="{{ $urlDisponibilidad }}" class="btn btn-primary d-block d-sm-inline-block mt-3 mt-sm-0 ms-sm-2">
                <i class="bi bi-calendar-check me-1"></i> Consultar disponibilidad
            </a>
        </div>
    @endif
@endsection
