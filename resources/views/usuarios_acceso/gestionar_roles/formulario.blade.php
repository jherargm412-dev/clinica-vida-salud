{{-- CU03 Gestionar Roles y Permisos · UI_Roles: mostrar formulario (Registrar y Modificar) --}}
@extends('compartido.app')

@php
    $esNuevo = ! $rol->exists;
    $esSistema = $rol->esDelSistema();
    $esAdmin = $rol->esAdministrador();
    // Permisos marcados: lo enviado (si hubo error) o los que ya tiene el rol
    $seleccionados = array_map('intval', old('permisos', $marcados));
@endphp

@section('titulo', $esNuevo ? 'Nuevo rol' : 'Editar rol')

@section('contenido')

    <div class="mb-3">
        <a href="{{ route('roles.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a roles
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">{{ $esNuevo ? 'Nuevo rol' : 'Editar rol' }}</h1>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 900px">
        <div class="card-body">

            <form method="POST" novalidate
                  action="{{ $esNuevo ? route('roles.guardar') : route('roles.actualizar', $rol) }}">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-5">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="nombre" name="nombre" maxlength="30"
                               value="{{ old('nombre', $rol->nombre) }}" @readonly($esSistema)
                               class="form-control @error('nombre') is-invalid @enderror">
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($esSistema)
                            <div class="form-text"><i class="bi bi-lock"></i> Rol del sistema: el nombre no se puede cambiar.</div>
                        @endif
                    </div>
                    <div class="col-md-7">
                        <label for="descripcion" class="form-label">Descripción <span class="text-muted">(opcional)</span></label>
                        <input type="text" id="descripcion" name="descripcion" maxlength="150"
                               value="{{ old('descripcion', $rol->descripcion) }}"
                               class="form-control @error('descripcion') is-invalid @enderror">
                        @error('descripcion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Permisos agrupados por paquete --}}
                <h2 class="h6 text-muted text-uppercase mb-2">Permisos <span class="text-danger">*</span></h2>
                @error('permisos') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
                @error('permisos.*') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror

                <div class="row g-3 mb-2">
                    @foreach ($grupos as $grupo => $permisos)
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">{{ $grupo }}</div>
                                @foreach ($permisos as $permiso)
                                    @php($bloqueado = $esAdmin && $permiso->clave === \App\Modulos\usuarios_acceso\models\Permiso::CLAVE_ROLES)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="permisos[]"
                                               value="{{ $permiso->id_permiso }}" id="perm{{ $permiso->id_permiso }}"
                                               @checked($bloqueado || in_array($permiso->id_permiso, $seleccionados))
                                               @disabled($bloqueado)>
                                        @if ($bloqueado)
                                            {{-- Un checkbox deshabilitado no se envía: este campo oculto lo envía igual --}}
                                            <input type="hidden" name="permisos[]" value="{{ $permiso->id_permiso }}">
                                        @endif
                                        <label class="form-check-label" for="perm{{ $permiso->id_permiso }}">
                                            {{ $permiso->nombre }}
                                            @if ($bloqueado) <i class="bi bi-lock text-muted" title="El rol Administrador debe conservar este permiso"></i> @endif
                                            <div class="small text-muted">{{ $permiso->descripcion }}</div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

@endsection
