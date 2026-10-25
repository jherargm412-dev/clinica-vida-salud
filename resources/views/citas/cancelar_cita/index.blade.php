{{-- CU15 Cancelar Cita · UI_cancelarCita --}}
@extends('compartido.app')

@section('titulo', 'Cancelar citas')

@section('contenido')
    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-1">Cancelar citas</h1>
        <p class="text-muted mb-0">Citas programadas o confirmadas que pueden cancelarse.</p>
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
            <form method="GET" action="{{ route('citas.cancelar.buscar') }}" class="row g-3 align-items-end mb-3">
                <div class="col-md-6">
                    <label for="texto" class="form-label">Código, CI, nombre o apellido del paciente</label>
                    <input type="search" id="texto" name="texto" value="{{ $texto }}" maxlength="100" class="form-control">
                </div>
                <div class="col-md-4">
                    <label for="fecha" class="form-label">Fecha de la cita</label>
                    <input type="date" id="fecha" name="fecha" value="{{ $fecha }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Paciente</th>
                            <th>Médico</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($citas as $cita)
                            <tr>
                                <td class="text-nowrap">{{ $cita->codigo }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $cita->paciente?->nombre_completo }}</div>
                                    <div class="small text-muted">CI: {{ $cita->paciente?->ci }}</div>
                                </td>
                                <td>{{ $cita->medico?->nombre_completo }}</td>
                                <td class="text-nowrap">{{ $cita->fecha->format('d/m/Y') }}</td>
                                <td class="text-nowrap">{{ substr($cita->hora_inicio, 0, 5) }}–{{ substr($cita->hora_fin, 0, 5) }}</td>
                                <td>
                                    <span class="badge {{ $cita->estado === 'Confirmada' ? 'text-bg-success' : 'text-bg-primary' }}">
                                        {{ $cita->estado }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('citas.cancelar.confirmar', $cita) }}" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-calendar-x me-1"></i> Cancelar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No se encontraron citas para cancelar.
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
