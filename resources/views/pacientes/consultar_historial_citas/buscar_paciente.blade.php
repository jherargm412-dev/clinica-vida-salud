{{-- CU08 Consultar Historial de Citas · UI_historialCitas --}}
@extends('compartido.app')

@section('titulo', 'Historial de citas')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Historial de citas</h1>
            <p class="text-muted mb-0">Busque un paciente para consultar todas sus citas.</p>
        </div>
        <a href="{{ route('inicio') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('historial.buscar') }}" class="row g-2 align-items-end mb-3">
                <div class="col-12 col-lg-7">
                    <label for="texto" class="form-label">CI, nombre o apellido del paciente</label>
                    <input type="search" id="texto" name="texto" value="{{ old('texto', $texto) }}" maxlength="100"
                           class="form-control @error('texto') is-invalid @enderror" required>
                    @error('texto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>
                @if ($busquedaRealizada)
                    <div class="col-auto">
                        <a href="{{ route('historial.index') }}" class="btn btn-link">Limpiar</a>
                    </div>
                @endif
            </form>

            @if ($busquedaRealizada)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>CI</th>
                                <th>Paciente</th>
                                <th>Estado</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pacientes as $paciente)
                                <tr>
                                    <td class="text-nowrap">
                                        <span class="badge text-bg-light border">{{ $paciente->codigo }}</span>
                                    </td>
                                    <td class="text-nowrap">{{ $paciente->ci }}</td>
                                    <td>{{ $paciente->nombre_completo }}</td>
                                    <td>
                                        @if ($paciente->activo)
                                            <span class="badge text-bg-success">Activo</span>
                                        @else
                                            <span class="badge text-bg-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('historial.paciente', $paciente->id_paciente) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-clock-history me-1"></i> Ver historial
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No se encontraron pacientes</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
