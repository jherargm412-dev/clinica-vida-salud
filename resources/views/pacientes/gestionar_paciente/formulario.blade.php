{{-- CU06 Gestionar Paciente · UI_paciente: crearPaciente / editarPaciente (formulario) --}}
@extends('compartido.app')

@php
    $esNuevo = ! $paciente->exists;
    $usuario = $paciente->usuario;      // la cuenta del paciente (en "Nuevo" no existe todavía)
@endphp

@section('titulo', $esNuevo ? 'Nuevo paciente' : 'Editar paciente')

@section('contenido')

    <div class="mb-3">
        <a href="{{ route('pacientes.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a pacientes
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">
            {{ $esNuevo ? 'Nuevo paciente' : 'Editar paciente' }}
            @unless ($esNuevo) <span class="badge text-bg-light border align-middle">{{ $paciente->codigo }}</span> @endunless
        </h1>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 760px">
        <div class="card-body">

            <form method="POST" novalidate
                  action="{{ $esNuevo ? route('pacientes.guardar') : route('pacientes.actualizar', $paciente) }}">
                @csrf

                {{-- 1. Identificación --}}
                <h2 class="h6 text-muted text-uppercase mb-3">Datos personales</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="ci" class="form-label">CI <span class="text-danger">*</span></label>
                        <input type="text" id="ci" name="ci" maxlength="15"
                               value="{{ old('ci', $paciente->ci) }}" @readonly(! $esNuevo)
                               class="form-control @error('ci') is-invalid @enderror">
                        @error('ci') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @unless ($esNuevo)
                        <div class="col-md-6">
                            <label class="form-label">Código</label>
                            <input type="text" class="form-control" value="{{ $paciente->codigo }}" readonly>
                        </div>
                    @endunless

                    <div class="col-md-6">
                        <label for="nombres" class="form-label">Nombres <span class="text-danger">*</span></label>
                        <input type="text" id="nombres" name="nombres" maxlength="60"
                               value="{{ old('nombres', $usuario?->nombres) }}"
                               class="form-control @error('nombres') is-invalid @enderror">
                        @error('nombres') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" id="apellidos" name="apellidos" maxlength="60"
                               value="{{ old('apellidos', $usuario?->apellidos) }}"
                               class="form-control @error('apellidos') is-invalid @enderror">
                        @error('apellidos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento <span class="text-danger">*</span></label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" max="{{ now()->toDateString() }}"
                               value="{{ old('fecha_nacimiento', $paciente->fecha_nacimiento?->format('Y-m-d')) }}"
                               class="form-control @error('fecha_nacimiento') is-invalid @enderror">
                        @error('fecha_nacimiento') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="sexo" class="form-label">Sexo <span class="text-danger">*</span></label>
                        <select id="sexo" name="sexo" class="form-select @error('sexo') is-invalid @enderror">
                            <option value="">Seleccione…</option>
                            <option value="M" @selected(old('sexo', $paciente->sexo) === 'M')>Masculino</option>
                            <option value="F" @selected(old('sexo', $paciente->sexo) === 'F')>Femenino</option>
                        </select>
                        @error('sexo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- 2. Contacto --}}
                <h2 class="h6 text-muted text-uppercase mb-3">Contacto</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                        <input type="text" id="telefono" name="telefono" maxlength="20"
                               value="{{ old('telefono', $paciente->telefono) }}"
                               class="form-control @error('telefono') is-invalid @enderror">
                        @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input type="email" id="correo" name="correo" maxlength="100"
                               value="{{ old('correo', $usuario?->correo) }}"
                               class="form-control @error('correo') is-invalid @enderror">
                        @error('correo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label for="direccion" class="form-label">Dirección <span class="text-muted small">(opcional)</span></label>
                        <input type="text" id="direccion" name="direccion" maxlength="150"
                               value="{{ old('direccion', $paciente->direccion) }}"
                               class="form-control @error('direccion') is-invalid @enderror">
                        @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                @if ($esNuevo)
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-1"></i>
                        Al guardar se creará la cuenta del paciente: su usuario será su CI y se generará una contraseña temporal
                        que se mostrará una sola vez para que se la entregue.
                    </div>
                @endif

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar
                    </button>
                    <a href="{{ route('pacientes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

@endsection