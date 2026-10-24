{{-- CU03 Gestionar Roles y Permisos · UI_Roles: mostrar datos del rol (Consultar) --}}
@extends('compartido.app')

@section('titulo', 'Rol ' . $rol->nombre)

@section('contenido')

    <div class="mb-3">
        <a href="{{ route('roles.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a roles
        </a>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
            <h1 class="h3 fw-semibold mb-0">
                {{ $rol->nombre }}
                @if ($rol->activo)
                    <span class="badge text-bg-success align-middle fs-6">Activo</span>
                @else
                    <span class="badge text-bg-secondary align-middle fs-6">Inactivo</span>
                @endif
            </h1>
            <a href="{{ route('roles.editar', $rol) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="max-width: 900px">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9">{{ $rol->descripcion ?? '—' }}</dd>
                <dt class="col-sm-3">Usuarios activos</dt>
                <dd class="col-sm-9">{{ $usuariosActivos }}</dd>
                <dt class="col-sm-3">Tipo</dt>
                <dd class="col-sm-9">{{ $rol->esDelSistema() ? 'Rol del sistema' : 'Rol creado por el administrador' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 900px">
        <div class="card-body">
            <h2 class="h6 text-muted text-uppercase mb-3">Permisos del rol</h2>
            @forelse ($grupos as $grupo => $permisos)
                <div class="mb-3">
                    <div class="fw-semibold mb-1">{{ $grupo }}</div>
                    <ul class="list-unstyled mb-0">
                        @foreach ($permisos as $permiso)
                            <li class="mb-1">
                                <i class="bi bi-check-circle-fill text-success"></i> {{ $permiso->nombre }}
                                <span class="small text-muted">— {{ $permiso->descripcion }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-muted mb-0">Este rol no tiene permisos.</p>
            @endforelse
        </div>
    </div>

@endsection
