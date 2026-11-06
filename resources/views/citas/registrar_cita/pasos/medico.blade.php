{{-- CU13 · Paso "Médico": médicos activos de la especialidad elegida --}}
<h2 class="h5 fw-semibold mb-1">Elija el médico</h2>
<p class="text-muted mb-3">{{ $especialidad->nombre }} · Bs {{ number_format($especialidad->monto_tarifa, 2) }}</p>

<div class="row g-3">
    @forelse ($medicos as $opcion)
        @php($iniciales = mb_strtoupper(mb_substr($opcion->usuario->nombres, 0, 1) . mb_substr($opcion->usuario->apellidos, 0, 1)))
        <div class="col-sm-6 col-lg-4">
            <a href="{{ route('citas.crear', $hastaMedico + ['id_especialidad' => $especialidad->id_especialidad, 'id_medico' => $opcion->id_medico]) }}"
               class="cvs-opcion flex-row align-items-center gap-3">
                <span class="cvs-iniciales">{{ $iniciales }}</span>
                <span>
                    <span class="d-block fw-semibold">{{ $opcion->nombre_completo }}</span>
                    <span class="d-block text-muted small">{{ $opcion->codigo }}</span>
                </span>
            </a>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">No hay médicos activos para esta especialidad.</div>
        </div>
    @endforelse
</div>
