{{-- CU08 Consultar Historial de Citas · UI_historialCitas --}}
@extends('compartido.app')

@section('titulo', $esPaciente ? 'Mis citas' : 'Historial de citas')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">{{ $esPaciente ? 'Mis citas' : 'Historial de citas' }}</h1>
            <p class="text-muted mb-0">Citas pasadas y actuales, incluidas las canceladas.</p>
        </div>
        <div class="d-flex gap-2">
            @unless ($esPaciente)
                <a href="{{ route('historial.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-search me-1"></i> Cambiar paciente
                </a>
            @endunless
            <a href="{{ route('inicio') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver al menú
            </a>
        </div>
    </div>

    @if ($error)
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ $error }}
        </div>
    @else
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <div class="small text-muted">Código</div>
                        <div class="fw-semibold">{{ $paciente->codigo }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="small text-muted">CI</div>
                        <div class="fw-semibold">{{ $paciente->ci }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="small text-muted">Paciente</div>
                        <div class="fw-semibold">{{ $paciente->nombre_completo }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="GET"
                      action="{{ $esPaciente ? route('historial.mis_citas') : route('historial.paciente', $paciente->id_paciente) }}"
                      class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="estado" class="form-label">Filtrar por estado</label>
                        <select id="estado" name="estado" class="form-select">
                            <option value="">Todos los estados</option>
                            @foreach (\App\Modulos\citas\models\Cita::ESTADOS as $opcionEstado)
                                <option value="{{ $opcionEstado }}" @selected($estado === $opcionEstado)>
                                    {{ $opcionEstado }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-funnel me-1"></i> Filtrar
                        </button>
                    </div>
                    @if ($estado)
                        <div class="col-auto">
                            <a href="{{ $esPaciente ? route('historial.mis_citas') : route('historial.paciente', $paciente->id_paciente) }}"
                               class="btn btn-link">Limpiar</a>
                        </div>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Médico</th>
                                <th>Especialidad</th>
                                <th>Estado</th>
                                <th>Registrada</th>
                                <th>Motivo de cancelación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($citas as $cita)
                                <tr>
                                    <td class="text-nowrap">{{ $cita->codigo }}</td>
                                    <td class="text-nowrap">{{ $cita->fecha->format('d/m/Y') }}</td>
                                    <td class="text-nowrap">{{ substr($cita->hora_inicio, 0, 5) }}–{{ substr($cita->hora_fin, 0, 5) }}</td>
                                    <td>{{ $cita->medico?->nombre_completo ?? '—' }}</td>
                                    <td>{{ $cita->especialidad?->nombre ?? '—' }}</td>
                                    <td>
                                        @php
                                            $colorEstado = match ($cita->estado) {
                                                'Atendida' => 'success',
                                                'Cancelada', 'Inasistencia' => 'secondary',
                                                'Pendiente de pago' => 'warning',
                                                default => 'primary',
                                            };
                                        @endphp
                                        <span class="badge text-bg-{{ $colorEstado }}">{{ $cita->estado }}</span>
                                    </td>
                                    <td class="text-nowrap">{{ $cita->fecha_creacion?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $cita->estado === 'Cancelada' ? ($cita->motivo_cancelacion ?: '—') : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        @if ($estado)
                                            No hay citas con ese estado
                                        @else
                                            El paciente aún no tiene citas registradas
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $citas->links() }}</div>
            </div>
        </div>
    @endif
@endsection
