{{-- CU12 Consultar Disponibilidad --}}
@extends('compartido.app')

@section('titulo', 'Consultar disponibilidad')

@section('contenido')
    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-1">Consultar disponibilidad</h1>
        <p class="text-muted mb-0">Consulta los horarios libres por especialidad, médico y fecha.</p>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 fw-semibold">1. Seleccionar especialidad</h2>
            <form method="GET" action="{{ route('disponibilidad.medicos') }}" class="row g-3 align-items-end">
                @if ($idPaciente)
                    <input type="hidden" name="id_paciente" value="{{ $idPaciente }}">
                @endif
                <div class="col-md-8">
                    <label for="id_especialidad" class="form-label">Especialidad</label>
                    <select id="id_especialidad" name="id_especialidad" class="form-select" required>
                        <option value="">Seleccione una especialidad</option>
                        @foreach ($especialidades as $especialidad)
                            <option value="{{ $especialidad->id_especialidad }}"
                                @selected((int) ($especialidadSeleccionada?->id_especialidad ?? 0) === (int) $especialidad->id_especialidad)>
                                {{ $especialidad->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_especialidad') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Buscar médicos
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($especialidadSeleccionada)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 fw-semibold">2. Seleccionar médico y fecha</h2>
                <p class="text-muted">Especialidad: {{ $especialidadSeleccionada->nombre }}</p>
                <form method="GET" action="{{ route('disponibilidad.horarios') }}" class="row g-3 align-items-end">
                    <input type="hidden" name="id_especialidad" value="{{ $especialidadSeleccionada->id_especialidad }}">
                    @if ($idPaciente)
                        <input type="hidden" name="id_paciente" value="{{ $idPaciente }}">
                    @endif
                    <div class="col-md-5">
                        <label for="id_medico" class="form-label">Médico</label>
                        <select id="id_medico" name="id_medico" class="form-select" required>
                            <option value="">Seleccione un médico</option>
                            @foreach ($medicos ?? [] as $medico)
                                <option value="{{ $medico->id_medico }}"
                                    @selected((int) ($medicoSeleccionado?->id_medico ?? 0) === (int) $medico->id_medico)>
                                    {{ $medico->nombre_completo }} ({{ $medico->codigo }})
                                </option>
                            @endforeach
                        </select>
                        @error('id_medico') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="fecha" class="form-label">Fecha</label>
                        <input type="date" id="fecha" name="fecha" value="{{ $fecha }}"
                               min="{{ now()->toDateString() }}" class="form-control" required>
                        @error('fecha') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-calendar-check me-1"></i> Consultar horarios
                        </button>
                    </div>
                </form>
                @if (($medicos ?? collect())->isEmpty())
                    <div class="alert alert-info mt-3 mb-0">No hay médicos activos para esta especialidad.</div>
                @endif
            </div>
        </div>
    @endif

    @if ($horariosDisponibles !== null)
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 fw-semibold mb-1">Horarios disponibles</h2>
                <p class="text-muted">
                    {{ $medicoSeleccionado->nombre_completo }} · {{ $especialidadSeleccionada->nombre }} ·
                    {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                </p>

                @forelse ($horariosDisponibles as $disponibilidad)
                    <section class="border rounded p-3 mb-3">
                        <h3 class="h6 fw-semibold">
                            {{ \App\Modulos\medicos_especialidades\models\HorarioAtencion::DIAS[$diaConsultado] }}:
                            {{ substr($disponibilidad['horario']->hora_inicio, 0, 5) }}–{{ substr($disponibilidad['horario']->hora_fin, 0, 5) }}
                        </h3>
                        @if ($disponibilidad['bloques'])
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($disponibilidad['bloques'] as $bloque)
                                    <a href="{{ route('citas.crear', array_filter([
                                        'id_paciente' => $idPaciente,
                                        'id_especialidad' => $especialidadSeleccionada->id_especialidad,
                                        'id_medico' => $medicoSeleccionado->id_medico,
                                        'fecha' => $fecha,
                                        'hora_inicio' => $bloque['hora_inicio'],
                                        'hora_fin' => $bloque['hora_fin'],
                                    ], fn ($valor) => $valor !== null)) }}"
                                       class="badge text-bg-success fs-6 fw-normal text-decoration-none">
                                        {{ $bloque['hora_inicio'] }}–{{ $bloque['hora_fin'] }}
                                        <span class="visually-hidden">Elegir este horario</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted mb-0">No hay bloques disponibles en este horario.</p>
                        @endif
                    </section>
                @empty
                    <div class="alert alert-info mb-0">
                        El médico no tiene horarios de atención activos para ese día.
                    </div>
                @endforelse
            </div>
        </div>
    @endif
@endsection
