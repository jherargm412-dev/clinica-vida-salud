{{-- CU01 Gestionar Usuarios · UI_Usuarios: mostrar lista / mostrar resultado --}}
@extends('compartido.app')

@section('titulo', 'Usuarios')

@section('contenido')

    {{-- 1. Encabezado: título + "Nuevo usuario" (detallar, paso 3) --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Usuarios</h1>
            <p class="text-muted mb-0">Cuentas de acceso al sistema y su rol</p>
        </div>
        <a href="{{ route('usuarios.crear') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Nuevo usuario
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            {{-- 2. Buscador (Consultar) --}}
            <form method="GET" action="{{ route('usuarios.index') }}" class="row g-2 mb-3">
                <div class="col-sm-8 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="buscar" value="{{ $texto }}" class="form-control"
                               placeholder="Buscar por nombre, usuario o correo">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    @if ($texto)
                        <a href="{{ route('usuarios.index') }}" class="btn btn-link">Limpiar</a>
                    @endif
                </div>
            </form>

            {{-- 3. Tabla (Listar): nombre, usuario, correo, rol y estado (detallar, paso 2) --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($usuarios as $usuario)
                            @php($esYo = (int) $usuario->id_usuario === (int) auth()->id())
                            <tr>
                                <td class="fw-semibold">
                                    {{ $usuario->nombre_completo }}
                                    @if ($esYo) <span class="badge text-bg-light border ms-1">Tú</span> @endif
                                </td>
                                <td>{{ $usuario->nombre_usuario }}</td>
                                <td class="text-muted">{{ $usuario->correo ?? '—' }}</td>
                                <td>{{ $usuario->rol->nombre ?? '—' }}</td>
                                <td>
                                    @if ($usuario->activo)
                                        <span class="badge text-bg-success">Activo</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    @endif
                                    @if ($usuario->bloqueado)
                                        <span class="badge text-bg-warning"><i class="bi bi-lock-fill"></i> Bloqueado</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('usuarios.editar', $usuario) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>

                                    {{-- Desbloquear: solo si está bloqueado --}}
                                    @if ($usuario->bloqueado)
                                        <form method="POST" action="{{ route('usuarios.desbloquear', $usuario) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-unlock"></i> Desbloquear
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Desactivar (no aparece en la cuenta propia) / Activar --}}
                                    @if ($usuario->activo)
                                        @unless ($esYo)
                                            <form method="POST" action="{{ route('usuarios.desactivar', $usuario) }}" class="d-inline"
                                                  data-confirmar="¿Desactivar la cuenta de {{ $usuario->nombre_completo }}?">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-slash-circle"></i> Desactivar
                                                </button>
                                            </form>
                                        @endunless
                                    @else
                                        <form method="POST" action="{{ route('usuarios.reactivar', $usuario) }}" class="d-inline">
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
                                <td colspan="6" class="text-center text-muted py-4">No se encontraron usuarios</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación (15 por página) --}}
            <div class="mt-3">
                {{ $usuarios->links() }}
            </div>

        </div>
    </div>

@endsection
