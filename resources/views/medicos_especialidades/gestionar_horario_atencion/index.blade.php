{{-- CU11 Gestionar Horario de Atención · lista de médicos con el resumen de su semana --}}
@extends('compartido.app')

@php($dias = App\Modulos\medicos_especialidades\models\HorarioAtencion::DIAS)

@section('titulo', 'Horarios de atención')

@section('contenido')
    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-0">Horarios de atención</h1>
        <p class="text-muted mb-0">Días y horas en que atiende cada médico</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            {{-- Buscador y filtro --}}
            <form method="GET" action="{{ route('horarios.index') }}" class="row g-2 mb-3">
                <div class="col-sm-7 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="buscar" value="{{ $texto }}" class="form-control" placeholder="Buscar médico por nombre o código">
                    </div>
                </div>
                <div class="col-sm-5 col-md-3">
                    <select name="horario" class="form-select" aria-label="Horario">
                        <option value="">Horario: Todos</option>
                        <option value="con" @selected($filtro === 'con')>Con horario</option>
                        <option value="sin" @selected($filtro === 'sin')>Sin horario</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    @if ($texto || $filtro)
                        <a href="{{ route('horarios.index') }}" class="btn btn-link">Limpiar</a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Médico</th>
                            <th>Especialidades</th>
                            <th>Semana</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($medicos as $medico)
                            @php($diasConHorario = $medico->horarios->pluck('dia_semana')->unique()->all())
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $medico->nombre_completo }}</div>
                                    <div class="small text-muted">{{ $medico->codigo }}</div>
                                </td>
                                <td class="small">{{ $medico->especialidades->pluck('nombre')->join(', ') ?: '—' }}</td>
                                <td>
                                    {{-- Días de la semana: resaltados los que atiende --}}
                                    <div class="d-flex gap-1 mb-1">
                                        @foreach ($dias as $numero => $nombre)
                                            <span class="cvs-dia-chip {{ in_array($numero, $diasConHorario, true) ? 'activo' : '' }}" title="{{ $nombre }}">
                                                {{ mb_substr($nombre, 0, 1) }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @foreach (App\Modulos\medicos_especialidades\models\HorarioAtencion::resumenSemanal($medico->horarios) as $grupo)
                                        <div class="small text-muted">
                                            <span class="fw-semibold">{{ $grupo['dias'] }}:</span> {{ $grupo['tramos'] }} · {{ $grupo['duracion'] }}
                                        </div>
                                    @endforeach
                                </td>
                                <td>
                                    @if ($medico->horarios->isNotEmpty())
                                        <span class="badge text-bg-success">Con horario</span>
                                    @else
                                        <span class="badge text-bg-warning">Sin horario</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('horarios.semana', $medico) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-calendar-week"></i> Asignar horario
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No se encontraron médicos activos</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
