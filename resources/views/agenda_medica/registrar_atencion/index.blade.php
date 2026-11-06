{{-- CU18 Registrar Atención de Cita · UI_atencionCita --}}
@extends('compartido.app')

@section('titulo', 'Registrar atención')

@section('contenido')
    <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-2 mb-3">
        <div>
            <a href="{{ route('agenda.mi') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left"></i> Volver a mi agenda
            </a>
            <h1 class="h3 fw-semibold mt-2 mb-1">Registrar atención de cita</h1>
            <p class="text-muted mb-0">Seleccione una cita para revisar los datos del paciente e ingresar el registro clínico.</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Fecha y horario</th>
                            <th>Paciente</th>
                            <th>Especialidad</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($citas as $cita)
                            <tr>
                                <td class="text-nowrap">{{ $cita->codigo }}</td>
                                <td class="text-nowrap">
                                    <div>{{ $cita->fecha->format('d/m/Y') }}</div>
                                    <div class="small text-muted">{{ substr($cita->hora_inicio, 0, 5) }}–{{ substr($cita->hora_fin, 0, 5) }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $cita->paciente?->nombre_completo ?? '—' }}</div>
                                    <div class="small text-muted">CI: {{ $cita->paciente?->ci ?? '—' }}</div>
                                </td>
                                <td>{{ $cita->especialidad?->nombre ?? '—' }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $cita->estado === 'Atendida' ? 'success' : 'primary' }}">
                                        {{ $cita->estado }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('atencion.datos', $cita->id_cita) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-clipboard2-pulse me-1"></i> Registrar atención
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No hay citas pendientes de atención.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $citas->links() }}</div>
        </div>
    </div>
@endsection
