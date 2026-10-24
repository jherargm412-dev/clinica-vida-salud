{{-- CU09 Gestionar Especialidad · UI_Especialidades: mostrar lista / mostrar resultado --}}
@extends('compartido.app')

@section('titulo', 'Especialidades')

@section('contenido')

    {{-- 1. Encabezado: título + botón "Nueva especialidad" (detallar, paso 3) --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Especialidades</h1>
            <p class="text-muted mb-0">Especialidades que ofrece la clínica</p>
        </div>
        <a href="{{ route('especialidades.crear') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva especialidad
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            {{-- 2. Buscador (Consultar): formulario GET; el campo se llama "buscar" --}}
            <form method="GET" action="{{ route('especialidades.index') }}" class="row g-2 mb-3">
                <div class="col-sm-8 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="buscar" value="{{ $texto }}" class="form-control"
                               placeholder="Buscar por nombre">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    @if ($texto)
                        <a href="{{ route('especialidades.index') }}" class="btn btn-link">Limpiar</a>
                    @endif
                </div>
            </form>

            {{-- 3. Tabla (Listar) --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($especialidades as $especialidad)
                            <tr>
                                <td class="fw-semibold">{{ $especialidad->nombre }}</td>
                                <td class="text-muted">{{ $especialidad->descripcion ?? '—' }}</td>
                                <td>
                                    @if ($especialidad->activo)
                                        <span class="badge text-bg-success">Activo</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    {{-- Editar: enlace GET al formulario --}}
                                    <a href="{{ route('especialidades.editar', $especialidad) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>

                                    {{-- Desactivar / Activar: formulario POST según el estado --}}
                                    @if ($especialidad->activo)
                                        <form method="POST" action="{{ route('especialidades.desactivar', $especialidad) }}"
                                              class="d-inline" data-confirmar="¿Desactivar la especialidad {{ $especialidad->nombre }}?">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-slash-circle"></i> Desactivar
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('especialidades.activar', $especialidad) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle"></i> Activar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            {{-- Detallar, Consultar: lista vacía --}}
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No se encontraron especialidades</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
