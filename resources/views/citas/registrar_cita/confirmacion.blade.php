{{-- CU13 Registrar Cita · resumen del registro --}}
@extends('compartido.app')

@section('titulo', 'Cita registrada')

@section('contenido')
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                <h1 class="h3 fw-semibold mb-0">Cita registrada</h1>
            </div>
            <p class="text-muted">La cita quedó programada correctamente.</p>

            <dl class="row mb-4">
                <dt class="col-sm-3">Código</dt>
                <dd class="col-sm-9">{{ $cita->codigo }}</dd>
                <dt class="col-sm-3">Estado</dt>
                <dd class="col-sm-9"><span class="badge text-bg-primary">{{ $cita->estado }}</span></dd>
                <dt class="col-sm-3">Paciente</dt>
                <dd class="col-sm-9">{{ $cita->paciente?->nombre_completo }}</dd>
                <dt class="col-sm-3">Especialidad</dt>
                <dd class="col-sm-9">{{ $cita->especialidad?->nombre }}</dd>
                <dt class="col-sm-3">Médico</dt>
                <dd class="col-sm-9">{{ $cita->medico?->nombre_completo }}</dd>
                <dt class="col-sm-3">Fecha</dt>
                <dd class="col-sm-9">{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</dd>
                <dt class="col-sm-3">Horario</dt>
                <dd class="col-sm-9">{{ substr($cita->hora_inicio, 0, 5) }}–{{ substr($cita->hora_fin, 0, 5) }}</dd>
                <dt class="col-sm-3">Motivo</dt>
                <dd class="col-sm-9">{{ $cita->motivo ?: 'No especificado' }}</dd>
            </dl>

            <a href="{{ route('citas.crear') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Registrar otra cita
            </a>
        </div>
    </div>
@endsection
