{{-- CU13 · Paso "Paciente" (solo recepcionista): buscar y elegir un paciente activo --}}
<h2 class="h5 fw-semibold mb-3">¿Para qué paciente es la cita?</h2>

<form method="GET" action="{{ route('citas.crear') }}" class="row g-2 mb-3">
    {{-- Si ya venía un horario elegido desde "Disponibilidad", se conserva --}}
    @foreach ($conservar as $nombre => $valor)
        <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
    @endforeach
    <div class="col-md-8">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="buscar" value="{{ $buscar }}" class="form-control" maxlength="100" required
                   placeholder="CI, nombre o apellido" aria-label="Buscar paciente" autofocus>
        </div>
    </div>
    <div class="col-md-4">
        <button type="submit" class="btn btn-outline-primary w-100">Buscar</button>
    </div>
</form>

@if ($pacientes)
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Código</th><th>CI</th><th>Paciente</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($pacientes as $resultado)
                    <tr>
                        <td>{{ $resultado->codigo }}</td>
                        <td>{{ $resultado->ci }}</td>
                        <td>{{ $resultado->nombre_completo }}</td>
                        <td class="text-end">
                            <a href="{{ route('citas.crear', ['id_paciente' => $resultado->id_paciente] + $conservar) }}"
                               class="btn btn-sm btn-primary">
                                Elegir <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No se encontraron pacientes activos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $pacientes->links() }}</div>
@else
    <p class="text-muted mb-0">Busque al paciente por su CI, nombre o apellido.</p>
@endif
