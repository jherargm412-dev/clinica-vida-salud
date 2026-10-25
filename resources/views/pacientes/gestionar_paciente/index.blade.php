{{-- CU06 Gestionar Paciente · UI_paciente: mostrarPacientes / buscarPaciente --}}
@extends('compartido.app')

@section('titulo', 'Pacientes')

@section('contenido')

    {{-- Encabezado: título y botón "Nuevo paciente" (paso 3 del detalle) --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Pacientes</h1>
            <p class="text-muted mb-0">Pacientes de la clínica y su cuenta de acceso</p>
        </div>
        <a href="{{ route('pacientes.crear') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo paciente
        </a>
    </div>

    {{-- Datos de acceso del paciente recién registrado (paso 8 del detalle) --}}
    @if (session('credenciales'))
        <div class="alert alert-warning shadow-sm">
            <div class="fw-semibold mb-1"><i class="bi bi-key me-1"></i> Entregue estos datos de acceso al paciente</div>
            <div>Usuario: <strong>{{ session('credenciales')['usuario'] }}</strong></div>
            <div>Contraseña temporal: <strong>{{ session('credenciales')['contrasena'] }}</strong></div>
            <div class="small text-muted mt-1">No se volverá a mostrar. El paciente podrá cambiarla desde "Mi perfil".</div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            {{-- Buscador (Consultar): CI, nombre o apellido --}}
            <form method="GET" action="{{ route('pacientes.index') }}" class="row g-2 mb-3">
                <div class="col-12 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="buscar" value="{{ $texto }}" class="form-control"
                               placeholder="Buscar por CI, nombre o apellido">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    @if ($texto)
                        <a href="{{ route('pacientes.index') }}" class="btn btn-link">Limpiar</a>
                    @endif
                </div>
            </form>

            {{-- Tabla (Listar): código, CI, nombre completo, teléfono y estado (paso 2 del detalle) --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>CI</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pacientes as $paciente)
                            <tr>
                                <td class="text-nowrap"><span class="badge text-bg-light border">{{ $paciente->codigo }}</span></td>
                                <td class="text-nowrap">{{ $paciente->ci }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $paciente->nombre_completo }}</div>
                                    <div class="small text-muted">{{ $paciente->usuario?->correo }}</div>
                                </td>
                                <td class="text-nowrap">{{ $paciente->telefono }}</td>
                                <td>
                                    @if ($paciente->activo)
                                        <span class="badge text-bg-success">Activo</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('pacientes.editar', $paciente) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>

                                    @if ($paciente->activo)
                                        <form method="POST" action="{{ route('pacientes.desactivar', $paciente) }}" class="d-inline"
                                              data-confirmar="¿Desactivar al paciente {{ $paciente->nombre_completo }}? No podrá recibir nuevas citas ni iniciar sesión.">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-slash-circle"></i> Desactivar
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('pacientes.activar', $paciente) }}" class="d-inline">
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
                                <td colspan="6" class="text-center text-muted py-4">No se encontraron pacientes</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $pacientes->links() }}
            </div>

        </div>
    </div>

@endsection