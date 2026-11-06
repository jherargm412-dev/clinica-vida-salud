{{-- CU12 Consultar Disponibilidad: especialidad → semana de cada médico → horas libres del día elegido.
     También la usa CU14 Reprogramar cita (con $cita): se ve solo el médico de la cita. --}}
@extends('compartido.app')

@php
    $diasCortos = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $hasta = $desde->copy()->addDays(6);
    $anterior = $desde->copy()->subDays(7);
    $hayAnterior = $desde->gt(today());
@endphp

@section('titulo', $cita ? 'Seleccionar nuevo horario' : 'Consultar disponibilidad')

@section('contenido')
    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-1">{{ $cita ? 'Seleccionar nuevo horario' : 'Consultar disponibilidad' }}</h1>
        <p class="text-muted mb-0">
            @if ($cita)
                Cita {{ $cita->codigo }} · actual: {{ $cita->fecha->format('d/m/Y') }} {{ substr($cita->hora_inicio, 0, 5) }}.
                Elija un día con horarios libres y luego la hora.
            @else
                Elija una especialidad para ver qué días tiene horarios libres cada médico.
            @endif
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- 1. Especialidades --}}
    @unless ($cita)
        <div class="d-flex flex-wrap gap-2 mb-4" role="group" aria-label="Especialidades">
            @forelse ($especialidades as $especialidad)
                @php
                    $elegida = $especialidadSeleccionada?->id_especialidad === $especialidad->id_especialidad;
                @endphp
                <a href="{{ route('disponibilidad.medicos', $conservar + ['id_especialidad' => $especialidad->id_especialidad]) }}"
                   class="btn rounded-pill {{ $elegida ? 'btn-primary' : 'btn-outline-primary' }}"
                   @if ($elegida) aria-current="true" @endif>
                    {{ $especialidad->nombre }}
                </a>
            @empty
                <div class="alert alert-info w-100 mb-0">No hay especialidades activas.</div>
            @endforelse
        </div>
    @endunless

    {{-- 2. Médicos de la especialidad con sus próximos 7 días --}}
    @if ($especialidadSeleccionada)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h5 fw-semibold mb-0">{{ $especialidadSeleccionada->nombre }}</h2>

                    {{-- Cambiar de semana --}}
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ $hayAnterior ? route('disponibilidad.medicos', $conservar + [
                                'id_especialidad' => $especialidadSeleccionada->id_especialidad,
                                'desde' => ($anterior->lt(today()) ? today() : $anterior)->toDateString(),
                            ]) : '#' }}"
                           class="btn btn-sm btn-outline-secondary {{ $hayAnterior ? '' : 'disabled' }}" aria-label="Semana anterior">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <span class="small fw-semibold text-nowrap">
                            {{ $desde->day }} {{ $meses[$desde->month - 1] }} – {{ $hasta->day }} {{ $meses[$hasta->month - 1] }}
                        </span>
                        <a href="{{ route('disponibilidad.medicos', $conservar + [
                                'id_especialidad' => $especialidadSeleccionada->id_especialidad,
                                'desde' => $desde->copy()->addDays(7)->toDateString(),
                            ]) }}"
                           class="btn btn-sm btn-outline-secondary" aria-label="Semana siguiente">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>

                @if ($medicos->isEmpty())
                    <div class="alert alert-info mb-0">No hay médicos activos para esta especialidad.</div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 cvs-semana">
                            <thead>
                                <tr>
                                    <th class="text-start">Médico</th>
                                    @for ($i = 0; $i < 7; $i++)
                                        @php
                                            $dia = $desde->copy()->addDays($i);
                                        @endphp
                                        <th class="text-center fw-normal">
                                            <span class="d-block small text-muted">{{ $dia->isToday() ? 'Hoy' : $diasCortos[$dia->dayOfWeekIso - 1] }}</span>
                                            <span class="fw-semibold">{{ $dia->day }}</span>
                                        </th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($medicos as $opcion)
                                    @php
                                        $iniciales = mb_strtoupper(mb_substr($opcion->usuario->nombres, 0, 1) . mb_substr($opcion->usuario->apellidos, 0, 1));
                                    @endphp
                                    <tr>
                                        <td class="text-start">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="cvs-iniciales">{{ $iniciales }}</span>
                                                <div>
                                                    <div class="fw-semibold text-nowrap">{{ $opcion->nombre_completo }}</div>
                                                    <div class="small text-muted">{{ $opcion->codigo }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        @foreach ($semanas[$opcion->id_medico] as $dia)
                                            @php
                                                $elegida = $medicoSeleccionado?->id_medico === $opcion->id_medico
                                                    && $fecha && $dia['fecha']->isSameDay($fecha);
                                            @endphp
                                            <td class="text-center">
                                                @if ($dia['libres'] > 0)
                                                    <a href="{{ route('disponibilidad.horarios', $conservar + [
                                                            'id_especialidad' => $especialidadSeleccionada->id_especialidad,
                                                            'id_medico' => $opcion->id_medico,
                                                            'fecha' => $dia['fecha']->toDateString(),
                                                            'desde' => $desde->toDateString(),
                                                        ]) }}#horas"
                                                       class="cvs-celda libre {{ $elegida ? 'elegida' : '' }}"
                                                       title="Ver las horas libres">
                                                        {{ $dia['libres'] }} {{ $dia['libres'] === 1 ? 'libre' : 'libres' }}
                                                    </a>
                                                @elseif ($dia['tieneHorario'])
                                                    <span class="cvs-celda" title="Sin horarios libres">Lleno</span>
                                                @else
                                                    <span class="cvs-celda vacia" title="No atiende este día">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        Toque un día con horarios libres para ver las horas. «—» = no atiende ese día.
                    </p>
                @endif
            </div>
        </div>
    @endif

    {{-- 3. Horas libres del médico y día elegidos --}}
    @if ($turnos !== null)
        <div class="card border-0 shadow-sm" id="horas">
            <div class="card-body">
                <h2 class="h5 fw-semibold mb-1">{{ $medicoSeleccionado->nombre_completo }}</h2>
                <p class="text-muted mb-3">
                    {{ ucfirst($fecha->locale('es')->isoFormat('dddd D [de] MMMM')) }}
                    @if ($cita)
                        · elija la nueva hora
                    @elseif ($puedeRegistrar)
                        · elija una hora para registrar la cita
                    @endif
                </p>

                @php
                    $hayLibres = collect($turnos)->flatten(1)->contains(fn ($bloque) => ! $bloque['ocupado']);
                @endphp
                @unless ($hayLibres)
                    <div class="alert alert-info">No hay horarios disponibles para esta fecha.</div>
                @endunless

                @foreach ($turnos as $turno => $bloques)
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="text-muted small fw-semibold" style="width: 4.5rem">{{ $turno }}</span>
                        @foreach ($bloques as $bloque)
                            @if ($bloque['ocupado'])
                                {{-- Ocupada por otra cita: en rojo y sin enlace (no se muestra de quién es) --}}
                                <span class="btn cvs-hora ocupada" title="Ocupado" aria-disabled="true">
                                    {{ $bloque['hora_inicio'] }}<span class="visually-hidden"> (ocupado)</span>
                                </span>
                            @elseif ($cita)
                                <a href="{{ route('citas.reprogramar.editar', [
                                        'cita' => $cita->id_cita,
                                        'nueva_fecha' => $fecha->toDateString(),
                                        'nueva_hora_inicio' => $bloque['hora_inicio'],
                                        'nueva_hora_fin' => $bloque['hora_fin'],
                                    ]) }}" class="btn btn-outline-primary cvs-hora">{{ $bloque['hora_inicio'] }}</a>
                            @elseif ($puedeRegistrar)
                                <a href="{{ route('citas.crear', array_filter([
                                        'id_paciente' => $idPaciente,
                                        'id_especialidad' => $especialidadSeleccionada->id_especialidad,
                                        'id_medico' => $medicoSeleccionado->id_medico,
                                        'fecha' => $fecha->toDateString(),
                                        'hora_inicio' => $bloque['hora_inicio'],
                                        'hora_fin' => $bloque['hora_fin'],
                                    ], fn ($valor) => $valor !== null)) }}" class="btn btn-outline-primary cvs-hora">{{ $bloque['hora_inicio'] }}</a>
                            @else
                                <span class="btn btn-outline-secondary cvs-hora disabled">{{ $bloque['hora_inicio'] }}</span>
                            @endif
                        @endforeach
                    </div>
                @endforeach
                @if (collect($turnos)->flatten(1)->contains(fn ($bloque) => $bloque['ocupado']))
                    <p class="small text-muted mb-0"><span class="cvs-muestra-ocupada"></span> Ocupado: ya tiene una cita registrada.</p>
                @endif
            </div>
        </div>
    @endif
@endsection
