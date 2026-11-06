{{-- CU17 Consultar Agenda Médica · UI_agendaMedica --}}
@extends('compartido.app')

@section('titulo', 'Agenda de ' . $medico->nombre_completo)

@section('contenido')
    <div class="mb-3">
        @unless ($esMiAgenda)
            <a href="{{ route('agenda.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left"></i> Volver a médicos
            </a>
        @endunless
        <h1 class="h3 fw-semibold mt-2 mb-1">{{ $esMiAgenda ? 'Mi agenda' : 'Agenda médica' }}</h1>
        <p class="text-muted mb-0">{{ $medico->nombre_completo }} · {{ $medico->codigo }}</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $mensaje)
                            <li>{{ $mensaje }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="GET"
                  action="{{ $esMiAgenda ? route('agenda.mi') : route('agenda.consultar', $medico->id_medico) }}"
                  class="row g-3 align-items-end mb-4">
                <div class="col-sm-5 col-lg-4">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" id="fecha" name="fecha" value="{{ $fecha }}" class="form-control">
                </div>
                <div class="col-sm-5 col-lg-4">
                    <label for="estado" class="form-label">Estado</label>
                    <select id="estado" name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $opcionEstado)
                            <option value="{{ $opcionEstado }}" @selected($estado === $opcionEstado)>
                                {{ $opcionEstado }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                </div>
                @if ($fecha || $estado)
                    <div class="col-12">
                        <a href="{{ $esMiAgenda ? route('agenda.mi', ['fecha' => '', 'estado' => '']) : route('agenda.consultar', ['idMedico' => $medico->id_medico, 'fecha' => '', 'estado' => '']) }}"
                           class="btn btn-link px-0">Quitar filtros</a>
                    </div>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Horario</th>
                            <th>Paciente</th>
                            <th>Especialidad</th>
                            <th>Estado</th>
                            @if ($permisoVerMotivo)
                                <th>Motivo</th>
                            @endif
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
                                    <span class="badge text-bg-{{ $cita->estado === 'Cancelada' ? 'secondary' : ($cita->estado === 'Atendida' ? 'success' : 'primary') }}">
                                        {{ $cita->estado }}
                                    </span>
                                </td>
                                @if ($permisoVerMotivo)
                                    <td>{{ $cita->motivo ?: '—' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $permisoVerMotivo ? 6 : 5 }}" class="text-center text-muted py-4">
                                    No hay citas para los criterios seleccionados.
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
