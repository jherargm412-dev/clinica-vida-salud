{{-- CU13 · Paso "Resumen y pago": revisa los datos y confirma. Al confirmar se crea la cita
     "Pendiente de pago" y se pasa a pagarla (CU21) en este mismo paso. --}}
<h2 class="h5 fw-semibold mb-3">Resumen de la cita</h2>

<div class="row g-4">
    <div class="col-lg-7">
        <table class="table mb-0">
            <tbody>
                <tr><th class="fw-normal text-muted w-50">Paciente</th><td>{{ $paciente->nombre_completo }} (CI {{ $paciente->ci }})</td></tr>
                <tr><th class="fw-normal text-muted">Especialidad</th><td>{{ $especialidad->nombre }}</td></tr>
                <tr><th class="fw-normal text-muted">Médico</th><td>{{ $medico->nombre_completo }}</td></tr>
                <tr><th class="fw-normal text-muted">Fecha</th><td>{{ $fecha->format('d/m/Y') }}</td></tr>
                <tr><th class="fw-normal text-muted">Hora</th><td>{{ $bloque['hora_inicio'] }} – {{ $bloque['hora_fin'] }}</td></tr>
                <tr class="fw-semibold"><th>Total a pagar</th><td>Bs {{ number_format($especialidad->monto_tarifa, 2) }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="col-lg-5">
        <form method="POST" action="{{ route('citas.guardar') }}" class="needs-validation" novalidate>
            @csrf
            @unless ($esPaciente)
                <input type="hidden" name="id_paciente" value="{{ $paciente->id_paciente }}">
            @endunless
            <input type="hidden" name="id_especialidad" value="{{ $especialidad->id_especialidad }}">
            <input type="hidden" name="id_medico" value="{{ $medico->id_medico }}">
            <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">
            <input type="hidden" name="hora_inicio" value="{{ $bloque['hora_inicio'] }}">
            <input type="hidden" name="hora_fin" value="{{ $bloque['hora_fin'] }}">

            <div class="mb-3">
                <label for="motivo" class="form-label">Motivo de consulta <span class="text-muted">(opcional)</span></label>
                <textarea id="motivo" name="motivo" rows="3" maxlength="200"
                          class="form-control @error('motivo') is-invalid @enderror">{{ old('motivo') }}</textarea>
                @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            @if ($puedePagar)
                {{-- Reserva de 10 minutos (CU21) --}}
                <div class="alert alert-warning small">
                    <i class="bi bi-hourglass-split me-1"></i>
                    Al confirmar, la cita queda <strong>Pendiente de pago</strong> y el horario se reserva
                    <strong>10 minutos</strong>. Si no se paga a tiempo, se cancela y el horario queda libre.
                </div>
            @endif

            <button type="submit" class="btn btn-primary btn-lg w-100">
                @if ($puedePagar)
                    <i class="bi bi-credit-card me-1"></i> Confirmar y pagar
                @else
                    <i class="bi bi-check-lg me-1"></i> Guardar cita
                @endif
            </button>
        </form>
    </div>
</div>
