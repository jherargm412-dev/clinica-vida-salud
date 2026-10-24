{{-- CU09 Gestionar Especialidad · UI_Especialidades: mostrar formulario (Registrar y Modificar) --}}
@extends('compartido.app')

{{-- $especialidad->exists: false = nueva (Registrar) · true = ya existe en la BD (Modificar) --}}
@php($esNueva = ! $especialidad->exists)

@section('titulo', $esNueva ? 'Nueva especialidad' : 'Editar especialidad')

@section('contenido')

    <div class="mb-3">
        <a href="{{ route('especialidades.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a especialidades
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">{{ $esNueva ? 'Nueva especialidad' : 'Editar especialidad' }}</h1>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 640px">
        <div class="card-body">

            {{-- Registrar envía a "guardar"; Modificar envía a "actualizar" con el id --}}
            <form method="POST" novalidate
                  action="{{ $esNueva ? route('especialidades.guardar') : route('especialidades.actualizar', $especialidad) }}">
                @csrf

                {{-- Nombre (obligatorio) --}}
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" id="nombre" name="nombre" maxlength="60"
                           value="{{ old('nombre', $especialidad->nombre) }}"
                           class="form-control @error('nombre') is-invalid @enderror">
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Descripción (opcional) --}}
                <div class="mb-4">
                    <label for="descripcion" class="form-label">Descripción <span class="text-muted">(opcional)</span></label>
                    <textarea id="descripcion" name="descripcion" rows="3" maxlength="200"
                              class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $especialidad->descripcion) }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar
                    </button>
                    <a href="{{ route('especialidades.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

@endsection
