{{-- CU13 · Paso "Fecha y hora" (CU12): 7 días con cupos y los bloques libres del día elegido --}}
@php
    $diasSemana = ['LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB', 'DOM'];
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $anterior = $desde->copy()->subDays(7);
    $hayAnterior = $desde->gt(today());
@endphp

<h2 class="h5 fw-semibold mb-1">Elija la fecha y la hora</h2>
<p class="text-muted mb-3">{{ $especialidad->nombre }} con {{ $medico->nombre_completo }}</p>

{{-- Selector de días: flechas para cambiar de semana --}}
<div class="d-flex align-items-stretch gap-2 mb-4">
    <a href="{{ $hayAnterior ? route('citas.crear', $hastaFecha + ['desde' => ($anterior->lt(today()) ? today() : $anterior)->toDateString()]) : '#' }}"
       class="btn btn-outline-secondary d-flex align-items-center {{ $hayAnterior ? '' : 'disabled' }}" aria-label="Semana anterior">
        <i class="bi bi-chevron-left"></i>
    </a>

    <div class="cvs-dias flex-grow-1">
        @foreach ($dias as $dia)
            @php
                $elegido = $fecha && $dia['fecha']->isSameDay($fecha);
                $estado = ! $dia['tieneHorario'] ? 'Sin horario' : ($dia['libres'] === 0 ? 'Lleno' : $dia['libres'] . ' libres');
            @endphp
            <a href="{{ $dia['libres'] > 0 ? route('citas.crear', $hastaFecha + ['desde' => $desde->toDateString(), 'fecha' => $dia['fecha']->toDateString()]) : '#' }}"
               class="cvs-dia {{ $elegido ? 'elegido' : '' }} {{ $dia['libres'] > 0 ? '' : 'sin-cupos' }}"
               @if ($dia['libres'] === 0) aria-disabled="true" tabindex="-1" @endif>
                <span class="small fw-semibold">{{ $dia['fecha']->isToday() ? 'HOY' : $diasSemana[$dia['fecha']->dayOfWeekIso - 1] }}</span>
                <span class="fs-4 fw-semibold lh-1 my-1">{{ $dia['fecha']->day }}</span>
                <span class="small">{{ $meses[$dia['fecha']->month - 1] }} · {{ $estado }}</span>
            </a>
        @endforeach
    </div>

    <a href="{{ route('citas.crear', $hastaFecha + ['desde' => $desde->copy()->addDays(7)->toDateString()]) }}"
       class="btn btn-outline-secondary d-flex align-items-center" aria-label="Semana siguiente">
        <i class="bi bi-chevron-right"></i>
    </a>
</div>

{{-- Horas del día elegido: libres (se eligen) y ocupadas (en rojo, no se pueden elegir) --}}
@if (! $fecha)
    <div class="alert alert-info mb-0">No hay horarios libres en estos días. Pruebe con la semana siguiente.</div>
@elseif (empty($bloques))
    <div class="alert alert-info mb-0">No hay horarios libres el {{ $fecha->format('d/m/Y') }}.</div>
@else
    <p class="fw-semibold mb-2">Horarios del {{ $fecha->format('d/m/Y') }}</p>
    @foreach ($turnos as $turno => $horas)
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="text-muted small fw-semibold" style="width: 4.5rem">{{ $turno }}</span>
            @foreach ($horas as $opcion)
                @if ($opcion['ocupado'])
                    <span class="btn cvs-hora ocupada" title="Ocupado" aria-disabled="true">
                        {{ $opcion['hora_inicio'] }}<span class="visually-hidden"> (ocupado)</span>
                    </span>
                @else
                    <a href="{{ route('citas.crear', $hastaFecha + ['fecha' => $fecha->toDateString(), 'hora_inicio' => $opcion['hora_inicio'], 'hora_fin' => $opcion['hora_fin']]) }}"
                       class="btn btn-outline-primary cvs-hora">
                        {{ $opcion['hora_inicio'] }}
                    </a>
                @endif
            @endforeach
        </div>
    @endforeach
    <p class="small text-muted mb-0"><span class="cvs-muestra-ocupada"></span> Ocupado: ya tiene una cita registrada.</p>
@endif
