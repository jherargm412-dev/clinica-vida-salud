{{-- CU17 Consultar Agenda Médica · UI_agendaMedica --}}
@extends('compartido.app')

@section('titulo', 'Agenda médica')

@section('contenido')
    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-1">Agenda médica</h1>
        <p class="text-muted mb-0">Seleccione un médico para consultar sus citas.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @forelse ($medicos as $medico)
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 py-3 border-bottom">
                    <div>
                        <div class="fw-semibold">{{ $medico->nombre_completo }}</div>
                        <div class="small text-muted">{{ $medico->codigo }} · Registro profesional: {{ $medico->registro_profesional }}</div>
                    </div>
                    <a href="{{ route('agenda.consultar', ['idMedico' => $medico->id_medico]) }}" class="btn btn-outline-primary">
                        <i class="bi bi-calendar3 me-1"></i> Consultar agenda
                    </a>
                </div>
            @empty
                <p class="text-center text-muted mb-0 py-4">No hay médicos activos disponibles para consultar.</p>
            @endforelse
        </div>
    </div>
@endsection
