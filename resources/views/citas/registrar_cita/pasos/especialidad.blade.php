{{-- CU13 · Paso "Especialidad": tarjetas con el precio de la consulta (tarifa vigente, CU20) --}}
<h2 class="h5 fw-semibold mb-1">Elija la especialidad</h2>
@unless ($esPaciente)
    <p class="text-muted mb-3">Paciente: <strong>{{ $paciente->nombre_completo }}</strong> (CI {{ $paciente->ci }})</p>
@endunless

<div class="row g-3">
    @forelse ($especialidades as $opcion)
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('citas.crear', $hastaMedico + ['id_especialidad' => $opcion->id_especialidad]) }}"
               class="cvs-opcion h-100">
                <span class="fw-semibold fs-6">{{ $opcion->nombre }}</span>
                <span class="text-muted small flex-grow-1">{{ $opcion->descripcion ?: 'Consulta médica' }}</span>
                <span class="fw-semibold mt-2">Bs {{ number_format($opcion->monto_tarifa, 2) }}</span>
            </a>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">
                No hay especialidades disponibles para registrar citas. Una especialidad necesita una tarifa vigente
                y al menos un médico activo.
            </div>
        </div>
    @endforelse
</div>
