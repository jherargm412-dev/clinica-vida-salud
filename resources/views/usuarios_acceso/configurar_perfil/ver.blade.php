{{-- CU04 Configurar Perfil de Usuario · UI_Perfil: datos del perfil, formulario de contraseña y mensajes --}}
@extends('compartido.app')

@section('titulo', 'Mi perfil')

@php
    // Iniciales para el avatar: "Pedro De la Paz" -> "PD"
    $iniciales = mb_strtoupper(mb_substr($usuario->nombres, 0, 1) . mb_substr($usuario->apellidos, 0, 1));
@endphp

@section('contenido')

    {{-- Encabezado: iniciales, nombre, correo y rol --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="rounded-circle d-grid fw-semibold text-white flex-shrink-0"
             style="width:56px;height:56px;place-items:center;background:var(--cvs-primario, #0f766e);font-size:1.25rem">
            {{ $iniciales }}
        </div>
        <div>
            <h1 class="h4 fw-semibold mb-0">
                {{ $usuario->nombre_completo }}
                <span class="badge rounded-pill text-bg-light border align-middle fs-6 fw-normal">{{ $usuario->rol->nombre ?? '' }}</span>
            </h1>
            <div class="text-muted">{{ $usuario->correo ?? $usuario->nombre_usuario }}</div>
        </div>
    </div>

    <div class="row g-3">

        {{-- 1. Datos personales (editables): nombres, apellidos y correo --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3">Datos personales</h2>
                    <form method="POST" action="{{ route('perfil.actualizar') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                            <input type="text" id="nombres" name="nombres" maxlength="60"
                                   value="{{ old('nombres', $usuario->nombres) }}"
                                   class="form-control @error('nombres') is-invalid @enderror">
                            @error('nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" id="apellidos" name="apellidos" maxlength="60"
                                   value="{{ old('apellidos', $usuario->apellidos) }}"
                                   class="form-control @error('apellidos') is-invalid @enderror">
                            @error('apellidos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="correo" class="form-label">Correo electrónico <span class="text-muted">(opcional)</span></label>
                            <input type="email" id="correo" name="correo" maxlength="100"
                                   value="{{ old('correo', $usuario->correo) }}"
                                   class="form-control @error('correo') is-invalid @enderror">
                            @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            {{-- Cancelar: descarta los cambios y vuelve a mostrar los datos guardados --}}
                            <a href="{{ route('perfil.ver') }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 2. Cambiar contraseña --}}
        <div class="col-lg-6" id="cambiar-contrasena">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3">Cambiar contraseña</h2>
                    <form method="POST" action="{{ route('perfil.cambiar_contrasena') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="contrasena_actual" class="form-label">Contraseña actual <span class="text-danger">*</span></label>
                            <input type="password" id="contrasena_actual" name="contrasena_actual" maxlength="100" autocomplete="current-password"
                                   class="form-control @error('contrasena_actual') is-invalid @enderror">
                            @error('contrasena_actual') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="contrasena" class="form-label">Nueva contraseña <span class="text-danger">*</span></label>
                            <input type="password" id="contrasena" name="contrasena" maxlength="100" autocomplete="new-password"
                                   class="form-control @error('contrasena') is-invalid @enderror">
                            @error('contrasena') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            {{-- Requisitos que se marcan en verde al escribir (public/js/app.js, punto 4) --}}
                            <ul class="list-unstyled small text-muted mt-2 mb-0" data-requisitos-contrasena="contrasena">
                                <li data-regla="largo"><i class="bi bi-circle"></i> Al menos 8 caracteres</li>
                                <li data-regla="mayuscula"><i class="bi bi-circle"></i> Una letra mayúscula</li>
                                <li data-regla="minuscula"><i class="bi bi-circle"></i> Una letra minúscula</li>
                                <li data-regla="numero"><i class="bi bi-circle"></i> Un número</li>
                                <li data-regla="especial"><i class="bi bi-circle"></i> Un carácter especial (! @ # $ % &amp; *)</li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <label for="contrasena_confirmation" class="form-label">Confirmar nueva contraseña <span class="text-danger">*</span></label>
                            <input type="password" id="contrasena_confirmation" name="contrasena_confirmation" maxlength="100"
                                   autocomplete="new-password" class="form-control">
                        </div>
                        <div class="form-check mb-4">
                            {{-- Muestra u oculta las 3 contraseñas a la vez (public/js/app.js, punto 5) --}}
                            <input class="form-check-input" type="checkbox" id="mostrarContrasenas"
                                   data-mostrar-contrasenas="contrasena_actual,contrasena,contrasena_confirmation">
                            <label class="form-check-label" for="mostrarContrasenas">Mostrar contraseñas</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-key me-1"></i> Cambiar contraseña</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 3. Información de la cuenta (solo lectura; la asigna el administrador) --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-1">Información de la cuenta</h2>
                    <p class="small text-muted mb-3">Solo lectura. Si algún dato es incorrecto, comuníquelo al administrador.</p>
                    <dl class="row mb-0">
                        <dt class="col-sm-5 fw-normal text-muted">Nombre de usuario</dt> <dd class="col-sm-7">{{ $usuario->nombre_usuario }}</dd>
                        <dt class="col-sm-5 fw-normal text-muted">Rol</dt>               <dd class="col-sm-7">{{ $usuario->rol->nombre ?? '—' }}</dd>
                        <dt class="col-sm-5 fw-normal text-muted">Estado</dt>            <dd class="col-sm-7"><span class="badge text-bg-success">Activo</span></dd>
                        <dt class="col-sm-5 fw-normal text-muted">Fecha de registro</dt> <dd class="col-sm-7">{{ $usuario->fecha_registro?->format('d/m/Y') ?? '—' }}</dd>

                        {{-- Solo si la cuenta es de un médico (ficha del CU10) --}}
                        @if ($medico)
                            <dt class="col-12 mt-2 mb-2 border-top pt-3">Datos de médico</dt>
                            <dt class="col-sm-5 fw-normal text-muted">Código</dt>               <dd class="col-sm-7">{{ $medico->codigo }}</dd>
                            <dt class="col-sm-5 fw-normal text-muted">Registro profesional</dt> <dd class="col-sm-7">{{ $medico->registro_profesional }}</dd>
                            <dt class="col-sm-5 fw-normal text-muted">Teléfono</dt>             <dd class="col-sm-7">{{ $medico->telefono }}</dd>
                            <dt class="col-sm-5 fw-normal text-muted">Especialidades</dt>
                            <dd class="col-sm-7 mb-0">
                                @forelse ($medico->especialidades->sortBy('nombre') as $especialidad)
                                    <span class="badge rounded-pill text-bg-info fw-normal">{{ $especialidad->nombre }}</span>
                                @empty
                                    —
                                @endforelse
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- 4. Inicios de sesión recientes (bitácora) --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3">Inicios de sesión recientes</h2>
                    <ul class="list-unstyled mb-0">
                        @forelse ($inicios as $inicio)
                            <li class="d-flex gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                                <i class="bi bi-box-arrow-in-right text-muted fs-5"></i>
                                <div>
                                    <div>{{ $inicio->fecha_hora?->format('d/m/Y H:i:s') }}</div>
                                    <div class="small text-muted">Desde {{ $inicio->ip }}</div>
                                </div>
                            </li>
                        @empty
                            <li class="text-muted">Sin registros.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

    </div>

@endsection
