{{-- CU13 Registrar Cita · UI_cita --}}
@extends('compartido.app')

@section('titulo', 'Registrar cita')

@section('contenido')
    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-1">Registrar cita</h1>
        <p class="text-muted mb-0">Seleccione un paciente y un horario disponible.</p>
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

    @if ($mensajePaciente)
        <div class="alert alert-warning">{{ $mensajePaciente }}</div>
    @endif

    @if (! $esPaciente && ! $paciente)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 fw-semibold">1. Buscar paciente</h2>
                <form method="GET" action="{{ route('citas.crear') }}" class="row g-2 mb-3">
                    @if ($horarioSeleccionado)
                        <input type="hidden" name="id_especialidad" value="{{ $horarioSeleccionado['id_especialidad'] }}">
                        <input type="hidden" name="id_medico" value="{{ $horarioSeleccionado['id_medico'] }}">
                        <input type="hidden" name="fecha" value="{{ $horarioSeleccionado['fecha'] }}">
                        <input type="hidden" name="hora_inicio" value="{{ $horarioSeleccionado['hora_inicio'] }}">
                        <input type="hidden" name="hora_fin" value="{{ $horarioSeleccionado['hora_fin'] }}">
                    @endif
                    <div class="col-md-8">
                        <label for="buscar" class="form-label">CI, nombre o apellido</label>
                        <input type="search" id="buscar" name="buscar" value="{{ $buscar }}"
                               class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search me-1"></i> Buscar
                        </button>
                    </div>
                </form>

                @if ($pacientes)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>CI</th>
                                    <th>Paciente</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pacientes as $resultado)
                                    <tr>
                                        <td>{{ $resultado->codigo }}</td>
                                        <td>{{ $resultado->ci }}</td>
                                        <td>{{ $resultado->nombre_completo }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('citas.crear', array_merge(
                                                ['id_paciente' => $resultado->id_paciente, 'buscar' => $buscar],
                                                $horarioSeleccionado ? [
                                                    'id_especialidad' => $horarioSeleccionado['id_especialidad'],
                                                    'id_medico' => $horarioSeleccionado['id_medico'],
                                                    'fecha' => $horarioSeleccionado['fecha'],
                                                    'hora_inicio' => $horarioSeleccionado['hora_inicio'],
                                                    'hora_fin' => $horarioSeleccionado['hora_fin'],
                                                ] : []
                                            )) }}"
                                               class="btn btn-sm btn-primary">
                                                Seleccionar
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">
                                            No se encontraron pacientes activos.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $pacientes->links() }}</div>
                @endif
            </div>
        </div>
    @endif

    @if (! $paciente && $horarioSeleccionado)
        <div class="alert alert-info">
            Horario seleccionado:
            {{ $especialidad?->nombre ?? 'Especialidad' }} ·
            {{ $medico?->nombre_completo ?? 'Médico' }} ·
            {{ \Carbon\Carbon::parse($horarioSeleccionado['fecha'])->format('d/m/Y') }} ·
            {{ $horarioSeleccionado['hora_inicio'] }}–{{ $horarioSeleccionado['hora_fin'] }}.
            Seleccione un paciente para confirmar.
        </div>
    @endif

    @if ($paciente)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 fw-semibold">{{ $esPaciente ? 'Sus datos' : '1. Paciente seleccionado' }}</h2>
                <div class="row">
                    <div class="col-md-4"><span class="text-muted">Código:</span> {{ $paciente->codigo }}</div>
                    <div class="col-md-4"><span class="text-muted">CI:</span> {{ $paciente->ci }}</div>
                    <div class="col-md-4"><span class="text-muted">Nombre:</span> {{ $paciente->nombre_completo }}</div>
                </div>
            </div>
        </div>

        @if ($horarioSeleccionado)
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 fw-semibold">2. Confirmar horario</h2>
                    <div class="alert alert-info">
                        <div><strong>Especialidad:</strong> {{ $especialidad?->nombre ?? 'No disponible' }}</div>
                        <div><strong>Médico:</strong> {{ $medico?->nombre_completo ?? 'No disponible' }}</div>
                        <div><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($horarioSeleccionado['fecha'])->format('d/m/Y') }}</div>
                        <div><strong>Hora:</strong> {{ $horarioSeleccionado['hora_inicio'] }}–{{ $horarioSeleccionado['hora_fin'] }}</div>
                    </div>

                    <form method="POST" action="{{ route('citas.guardar') }}">
                        @csrf
                        @unless ($esPaciente)
                            <input type="hidden" name="id_paciente" value="{{ $paciente->id_paciente }}">
                        @endunless
                        <input type="hidden" name="id_especialidad" value="{{ $horarioSeleccionado['id_especialidad'] }}">
                        <input type="hidden" name="id_medico" value="{{ $horarioSeleccionado['id_medico'] }}">
                        <input type="hidden" name="fecha" value="{{ $horarioSeleccionado['fecha'] }}">
                        <input type="hidden" name="hora_inicio" value="{{ $horarioSeleccionado['hora_inicio'] }}">
                        <input type="hidden" name="hora_fin" value="{{ $horarioSeleccionado['hora_fin'] }}">

                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo de consulta <span class="text-muted">(opcional)</span></label>
                            <textarea id="motivo" name="motivo" rows="3" maxlength="200"
                                      class="form-control @error('motivo') is-invalid @enderror">{{ old('motivo') }}</textarea>
                            <div class="form-text">Máximo 200 caracteres.</div>
                            @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Guardar cita
                            </button>
                            <a href="{{ $urlDisponibilidad }}" class="btn btn-outline-secondary">Cambiar horario</a>
                            <a href="{{ route('citas.crear') }}" class="btn btn-link">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-info">
                <div class="fw-semibold">2. Consultar disponibilidad</div>
                <div>Elija una especialidad, médico, fecha y bloque libre para continuar con el registro.</div>
                <a href="{{ $urlDisponibilidad }}" class="btn btn-primary mt-3">
                    <i class="bi bi-calendar-check me-1"></i> Consultar disponibilidad
                </a>
            </div>
        @endif
    @endif
@endsection
