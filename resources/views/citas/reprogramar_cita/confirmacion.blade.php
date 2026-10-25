{{-- CU14 Reprogramar Cita · resumen de la reprogramación --}}
@extends('compartido.app')

@section('titulo', 'Cita reprogramada')

@section('contenido')
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                <h1 class="h3 fw-semibold mb-0">Cita reprogramada</h1>
            </div>
            <dl class="row mb-4">
                <dt class="col-sm-3">Código</dt>
                <dd class="col-sm-9">{{ $cita->codigo }}</dd>
                <dt class="col-sm-3">Paciente</dt>
                <dd class="col-sm-9">{{ $cita->paciente?->nombre_completo }}</dd>
                <dt class="col-sm-3">Especialidad</dt>
                <dd class="col-sm-9">{{ $cita->especialidad?->nombre }}</dd>
                <dt class="col-sm-3">Médico</dt>
                <dd class="col-sm-9">{{ $cita->medico?->nombre_completo }}</dd>
                <dt class="col-sm-3">Nueva fecha</dt>
                <dd class="col-sm-9">{{ $cita->fecha->format('d/m/Y') }}</dd>
                <dt class="col-sm-3">Nuevo horario</dt>
                <dd class="col-sm-9">{{ substr($cita->hora_inicio, 0, 5) }}–{{ substr($cita->hora_fin, 0, 5) }}</dd>
                <dt class="col-sm-3">Estado conservado</dt>
                <dd class="col-sm-9">{{ $cita->estado }}</dd>
            </dl>
            <a href="{{ route('citas.reprogramar.index') }}" class="btn btn-primary">
                Volver a citas
            </a>
        </div>
    </div>
@endsection
