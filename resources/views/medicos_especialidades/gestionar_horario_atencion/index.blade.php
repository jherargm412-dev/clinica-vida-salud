{{-- CU11 Gestionar Horario de Atención --}}
@extends('compartido.app')

@section('titulo', 'Horarios de atención')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Horarios de atención</h1>
            <p class="text-muted mb-0">Definición de disponibilidad por médico y día</p>
        </div>
        <a href="{{ route('horarios.crear') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo horario
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('horarios.index') }}" class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Médico</label>
                    <select name="medico" class="form-select">
                        <option value="">Todos los médicos</option>
                        @foreach ($medicos as $medico)
                            <option value="{{ $medico->id_medico }}" @selected((int) ($idMedico ?? 0) === (int) $medico->id_medico)>
                                {{ $medico->nombre_completo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Día</label>
                    <select name="dia" class="form-select">
                        <option value="">Todos</option>
                        @foreach (App\Modulos\medicos_especialidades\models\HorarioAtencion::DIAS as $valor => $nombre)
                            <option value="{{ $valor }}" @selected((int) ($dia ?? 0) === (int) $valor)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="activo" @selected(($estado ?? '') === 'activo')>Activo</option>
                        <option value="inactivo" @selected(($estado ?? '') === 'inactivo')>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100">Buscar</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Médico</th>
                            <th>Día</th>
                            <th>Horario</th>
                            <th>Duración</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($horarios as $horario)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $horario->medico?->nombre_completo }}</div>
                                    <div class="small text-muted">{{ $horario->medico?->codigo }}</div>
                                </td>
                                <td>{{ App\Modulos\medicos_especialidades\models\HorarioAtencion::DIAS[$horario->dia_semana] ?? $horario->dia_semana }}</td>
                                <td class="text-nowrap">{{ $horario->hora_inicio }} - {{ $horario->hora_fin }}</td>
                                <td>{{ $horario->duracion_cita }} min</td>
                                <td>
                                    @if ($horario->activo)
                                        <span class="badge text-bg-success">Activo</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('horarios.editar', $horario) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>

                                    @if ($horario->activo)
                                        <form method="POST" action="{{ route('horarios.desactivar', $horario) }}" class="d-inline"
                                              data-confirmar="¿Desactivar este horario del médico {{ $horario->medico?->nombre_completo }}?">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-slash-circle"></i> Desactivar
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('horarios.activar', $horario) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle"></i> Activar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No se encontraron horarios</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
