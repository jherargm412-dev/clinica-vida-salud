{{-- CU03 Gestionar Roles y Permisos · UI_Roles: mostrar lista de roles --}}
@extends('compartido.app')

@section('titulo', 'Roles y permisos')

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Roles y permisos</h1>
            <p class="text-muted mb-0">Cada rol define qué opciones del menú ven sus usuarios</p>
        </div>
        <a href="{{ route('roles.crear') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo rol
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            {{-- Buscador + filtro Estado --}}
            <form method="GET" action="{{ route('roles.index') }}" class="row g-2 mb-3">
                <div class="col-sm-8 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="buscar" value="{{ $texto }}" class="form-control" placeholder="Buscar por nombre">
                    </div>
                </div>
                <div class="col-sm-4 col-md-3">
                    <select name="estado" class="form-select" aria-label="Estado">
                        <option value="">Estado: Todos</option>
                        <option value="activo" @selected($estado === 'activo')>Activo</option>
                        <option value="inactivo" @selected($estado === 'inactivo')>Inactivo</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    @if ($texto || $estado)
                        <a href="{{ route('roles.index') }}" class="btn btn-link">Limpiar</a>
                    @endif
                </div>
            </form>

            {{-- Tabla: nombre, descripción y estado (detallar paso 2) + cantidad de permisos y usuarios activos --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th class="text-center">Permisos</th>
                            <th class="text-center">Usuarios activos</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $rol)
                            <tr>
                                <td class="fw-semibold text-nowrap">
                                    {{ $rol->nombre }}
                                    @if ($rol->esDelSistema())
                                        <span class="badge text-bg-light border fw-normal ms-1" title="Rol del sistema: el nombre no se puede cambiar">
                                            <i class="bi bi-lock"></i> Sistema
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $rol->descripcion ?? '—' }}</td>
                                <td class="text-center">{{ $rol->permisos_count }}</td>
                                <td class="text-center">{{ $rol->usuarios_activos_count }}</td>
                                <td>
                                    @if ($rol->activo)
                                        <span class="badge text-bg-success">Activo</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('roles.ver', $rol) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                    <a href="{{ route('roles.editar', $rol) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>

                                    @if ($rol->activo)
                                        @unless ($rol->esAdministrador())
                                            <form method="POST" action="{{ route('roles.deshabilitar', $rol) }}" class="d-inline"
                                                  data-confirmar="¿Deshabilitar el rol {{ $rol->nombre }}?">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-slash-circle"></i> Deshabilitar
                                                </button>
                                            </form>
                                        @endunless
                                    @else
                                        <form method="POST" action="{{ route('roles.habilitar', $rol) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle"></i> Habilitar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No se encontraron roles</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
