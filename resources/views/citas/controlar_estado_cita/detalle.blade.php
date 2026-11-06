{{-- CU16 Controlar Estado y Asistencia de Cita · UI_estadoCita --}}
@extends('compartido.app')

@section('titulo', 'Gestionar cita ' . $cita->codigo)

@section('contenido')
    <div class="mb-3">
        <a href="{{ route('citas.estado.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a citas
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">Gestionar cita <span class="badge text-bg-light border">{{ $cita->codigo }}</span></h1>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 760px">
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-sm-6">
                    <div class="small text-muted">Paciente</div>
                    <div class="fw-semibold">{{ $cita->paciente?->nombre_completo ?? '—' }}</div>
                    <div class="small text-muted">CI: {{ $cita->paciente?->ci ?? '—' }}</div>
                </div>
                <div class="col-sm-6">
                    <div class="small text-muted">Médico</div>
                    <div class="fw-semibold">{{ $cita->medico?->nombre_completo ?? '—' }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="small text-muted">Fecha</div>
                    <div>{{ $cita->fecha->format('d/m/Y') }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="small text-muted">Horario</div>
                    <div>{{ substr($cita->hora_inicio, 0, 5) }}–{{ substr($cita->hora_fin, 0, 5) }}</div>
                </div>
                <div class="col-sm-4">
                    <div class="small text-muted">Estado actual</div>
                    <span class="badge {{ $cita->estado === 'Confirmada' ? 'text-bg-success' : 'text-bg-primary' }}">
                        {{ $cita->estado }}
                    </span>
                </div>
            </div>

            <h2 class="h6 text-muted text-uppercase mb-3">Acciones disponibles</h2>
            <div class="d-flex flex-wrap gap-2">
                @if ($cita->estado === 'Programada')
                    <form method="POST" action="{{ route('citas.estado.cambiar', $cita->id_cita) }}">
                        @csrf
                        <input type="hidden" name="nuevo_estado" value="Confirmada">
                        <button type="submit" class="btn btn-outline-success">
                            <i class="bi bi-check-circle me-1"></i> Confirmar cita
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('citas.estado.cambiar', $cita->id_cita) }}">
                    @csrf
                    <input type="hidden" name="nuevo_estado" value="Atendida">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-clipboard-check me-1"></i> Registrar como atendida
                    </button>
                </form>

                <form method="POST" action="{{ route('citas.estado.cambiar', $cita->id_cita) }}">
                    @csrf
                    <input type="hidden" name="nuevo_estado" value="Inasistencia">
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-person-x me-1"></i> Registrar inasistencia
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
