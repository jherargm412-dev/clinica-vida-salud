{{-- CU13 / CU21 · Barra de pasos del asistente "Registrar cita".
     Recibe: $pasos (claves en orden), $actual (clave del paso actual), $urlsPasos (enlaces a pasos ya hechos; opcional).
     Opcionales para usarla en otros asistentes (p. ej. CU19 Reportes): $nombresPasos (clave => nombre) y $etiquetaPasos. --}}
@php
    $nombresPasos = $nombresPasos ?? [
        'paciente' => 'Paciente',
        'especialidad' => 'Especialidad',
        'medico' => 'Médico',
        'fecha' => 'Fecha y hora',
        'resumen' => 'Resumen y pago',
    ];
    $indiceActual = array_search($actual, $pasos, true);
    $urlsPasos = $urlsPasos ?? [];
@endphp

<ol class="cvs-pasos" aria-label="{{ $etiquetaPasos ?? 'Pasos para registrar la cita' }}">
    @foreach ($pasos as $i => $clave)
        @php($estado = $i < $indiceActual ? 'hecho' : ($i === $indiceActual ? 'actual' : 'pendiente'))
        <li class="cvs-paso {{ $estado }}" @if ($estado === 'actual') aria-current="step" @endif>
            @if ($estado === 'hecho' && ! empty($urlsPasos[$clave]))
                {{-- Un paso ya hecho se puede volver a abrir para cambiar la elección --}}
                <a href="{{ $urlsPasos[$clave] }}" class="cvs-paso-contenido text-decoration-none">
            @else
                <span class="cvs-paso-contenido">
            @endif
                <span class="cvs-paso-circulo">
                    @if ($estado === 'hecho') <i class="bi bi-check-lg"></i> @else {{ $i + 1 }} @endif
                </span>
                <span class="cvs-paso-nombre">{{ $nombresPasos[$clave] }}</span>
            @if ($estado === 'hecho' && ! empty($urlsPasos[$clave]))
                </a>
            @else
                </span>
            @endif
        </li>
    @endforeach
</ol>
