{{-- CU11 · Un tramo de horario (entrada, salida y duración). Recibe: $dia, $n (número del tramo), $fila --}}
<div class="d-flex flex-wrap align-items-center gap-2 mb-2" data-tramo>
    <input type="time" name="tramos[{{ $dia }}][{{ $n }}][hora_inicio]" value="{{ $fila['hora_inicio'] ?? '' }}"
           class="form-control" style="width: 8.5rem" required aria-label="Entrada" data-campo="hora_inicio">
    <span class="text-muted">a</span>
    <input type="time" name="tramos[{{ $dia }}][{{ $n }}][hora_fin]" value="{{ $fila['hora_fin'] ?? '' }}"
           class="form-control" style="width: 8.5rem" required aria-label="Salida" data-campo="hora_fin">
    <div class="input-group" style="width: 9.5rem">
        <input type="number" name="tramos[{{ $dia }}][{{ $n }}][duracion_cita]" value="{{ $fila['duracion_cita'] ?? 30 }}"
               class="form-control" min="15" max="180" step="5" required aria-label="Duración de cada cita" data-campo="duracion_cita">
        <span class="input-group-text">min</span>
    </div>
    <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none" data-quitar-tramo>
        <i class="bi bi-x-lg"></i> Quitar
    </button>
</div>
