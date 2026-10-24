{{-- CU01 Gestionar Usuarios · UI_Usuarios: mostrar formulario (Registrar y Modificar) --}}
@extends('compartido.app')

@php($esNuevo = ! $usuario->exists)

@section('titulo', $esNuevo ? 'Nuevo usuario' : 'Editar usuario')

@section('contenido')

    <div class="mb-3">
        <a href="{{ route('usuarios.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a usuarios
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">{{ $esNuevo ? 'Nuevo usuario' : 'Editar usuario' }}</h1>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 760px">
        <div class="card-body">

            {{-- Registrar -> guardar · Modificar -> actualizar --}}
            <form method="POST" novalidate
                  action="{{ $esNuevo ? route('usuarios.guardar') : route('usuarios.actualizar', $usuario) }}">
                @csrf

                <h2 class="h6 text-muted text-uppercase mb-3">Datos personales</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                        <input type="text" id="nombres" name="nombres" maxlength="60"
                               value="{{ old('nombres', $usuario->nombres) }}"
                               class="form-control @error('nombres') is-invalid @enderror">
                        @error('nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" id="apellidos" name="apellidos" maxlength="60"
                               value="{{ old('apellidos', $usuario->apellidos) }}"
                               class="form-control @error('apellidos') is-invalid @enderror">
                        @error('apellidos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo electrónico <span class="text-muted">(opcional)</span></label>
                        <input type="email" id="correo" name="correo" maxlength="100"
                               value="{{ old('correo', $usuario->correo) }}"
                               class="form-control @error('correo') is-invalid @enderror">
                        @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h2 class="h6 text-muted text-uppercase mb-3">Cuenta</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="nombre_usuario" class="form-label">Nombre de usuario <span class="text-danger">*</span></label>
                        <input type="text" id="nombre_usuario" name="nombre_usuario" maxlength="30" autocomplete="off"
                               value="{{ old('nombre_usuario', $usuario->nombre_usuario) }}"
                               class="form-control @error('nombre_usuario') is-invalid @enderror">
                        @error('nombre_usuario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Sin espacios. Se guarda en minúsculas.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="id_rol" class="form-label">Rol <span class="text-danger">*</span></label>
                        <select id="id_rol" name="id_rol" class="form-select @error('id_rol') is-invalid @enderror">
                            <option value="">Seleccione un rol…</option>
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->id_rol }}" @selected(old('id_rol', $usuario->id_rol) == $rol->id_rol)>
                                    {{ $rol->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_rol') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="contrasena" class="form-label">
                            Contraseña @if ($esNuevo)<span class="text-danger">*</span>@endif
                        </label>
                        <div class="input-group has-validation">
                            <input type="password" id="contrasena" name="contrasena" maxlength="100" autocomplete="new-password"
                                   class="form-control @error('contrasena') is-invalid @enderror">
                            <button class="btn btn-outline-secondary" type="button" data-mostrar-contrasena="contrasena"
                                    aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                            @error('contrasena') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @unless ($esNuevo)
                            <div class="form-text">Déjela vacía para mantener la contraseña actual.</div>
                        @endunless
                        {{-- Requisitos que se marcan en verde al escribir (public/js/app.js, punto 4) --}}
                        <ul class="list-unstyled small text-muted mt-2 mb-0" data-requisitos-contrasena="contrasena">
                            <li data-regla="largo"><i class="bi bi-circle"></i> Al menos 8 caracteres</li>
                            <li data-regla="mayuscula"><i class="bi bi-circle"></i> Una letra mayúscula</li>
                            <li data-regla="minuscula"><i class="bi bi-circle"></i> Una letra minúscula</li>
                            <li data-regla="numero"><i class="bi bi-circle"></i> Un número</li>
                            <li data-regla="especial"><i class="bi bi-circle"></i> Un carácter especial (! @ # $ % &amp; *)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <label for="contrasena_confirmation" class="form-label">
                            Confirmar contraseña @if ($esNuevo)<span class="text-danger">*</span>@endif
                        </label>
                        <div class="input-group">
                            <input type="password" id="contrasena_confirmation" name="contrasena_confirmation"
                                   maxlength="100" autocomplete="new-password" class="form-control">
                            <button class="btn btn-outline-secondary" type="button" data-mostrar-contrasena="contrasena_confirmation"
                                    aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="form-text">Repita la contraseña.</div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar
                    </button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

@endsection
