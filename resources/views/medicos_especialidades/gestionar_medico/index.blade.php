{{-- CU10 Gestionar Médico · UI_Medicos: mostrar lista / mostrar resultado --}}
@extends('compartido.app')

@section('titulo', 'Médicos')

@section('contenido')

    {{-- 1. Encabezado: título + "Nuevo médico" (detallar, paso 3) --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Médicos</h1>
            <p class="text-muted mb-0">Médicos de la clínica, sus especialidades y su cuenta de acceso</p>
        </div>
        <a href="{{ route('medicos.crear') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo médico
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            {{-- 2. Buscador (Consultar): nombre o especialidad --}}
            <form method="GET" action="{{ route('medicos.index') }}" class="row g-2 mb-3">
                <div class="col-12 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="buscar" value="{{ $texto }}" class="form-control"
                               placeholder="Buscar por nombre o especialidad">
                    </div>
                </div>
                {{-- Filtro: Especialidad --}}
                <div class="col-sm-6 col-lg-3">
                    <select name="especialidad" class="form-select" aria-label="Especialidad">
                        <option value="">Especialidad: Todas</option>
                        @foreach ($especialidades as $especialidad)
                            <option value="{{ $especialidad->id_especialidad }}" @selected($idEspecialidad === $especialidad->id_especialidad)>
                                {{ $especialidad->nombre }}{{ $especialidad->activo ? '' : ' (inactiva)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- Filtro: Estado --}}
                <div class="col-sm-6 col-lg-2">
                    <select name="estado" class="form-select" aria-label="Estado">
                        <option value="">Estado: Todos</option>
                        <option value="activo" @selected($estado === 'activo')>Activo</option>
                        <option value="inactivo" @selected($estado === 'inactivo')>Inactivo</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    @if ($texto || $idEspecialidad || $estado)
                        <a href="{{ route('medicos.index') }}" class="btn btn-link">Limpiar</a>
                    @endif
                </div>
            </form>

            {{-- 3. Tabla (Listar): código, nombre completo, especialidades, teléfono y estado (detallar, paso 2) --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Especialidades</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($medicos as $medico)
                            <tr>
                                <td class="text-nowrap"><span class="badge text-bg-light border">{{ $medico->codigo }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $medico->nombre_completo }}</div>
                                    <div class="small text-muted">Reg. {{ $medico->registro_profesional }}</div>
                                </td>
                                <td>
                                    @foreach ($medico->especialidades->sortBy('nombre') as $especialidad)
                                        <span class="badge rounded-pill {{ $especialidad->activo ? 'text-bg-info' : 'text-bg-secondary' }} fw-normal">
                                            {{ $especialidad->nombre }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="text-nowrap">{{ $medico->telefono }}</td>
                                <td>
                                    @if ($medico->activo)
                                        <span class="badge text-bg-success">Activo</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    @endif
                                    @unless ($medico->usuario?->activo)
                                        <div class="small text-danger mt-1"><i class="bi bi-person-x"></i> Cuenta desactivada</div>
                                    @endunless
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('medicos.editar', $medico) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>

                                    @if ($medico->activo)
                                        <form method="POST" action="{{ route('medicos.desactivar', $medico) }}" class="d-inline"
                                              data-confirmar="¿Desactivar al médico {{ $medico->nombre_completo }}? No podrá recibir nuevas citas.">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-slash-circle"></i> Desactivar
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('medicos.activar', $medico) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle"></i> Activar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No se encontraron médicos</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $medicos->links() }}
            </div>

        </div>
    </div>

@endsection
