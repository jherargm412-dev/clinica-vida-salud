{{-- CU23 Gestionar Preguntas Frecuentes · UI_PreguntasFrecuentes: mostrar formulario (Registrar y Modificar) --}}
@extends('compartido.app')

{{-- $pregunta->exists: false = nueva (Registrar) · true = ya existe en la BD (Modificar) --}}
@php($esNueva = ! $pregunta->exists)

@section('titulo', $esNueva ? 'Nueva pregunta frecuente' : 'Editar pregunta frecuente')

@section('contenido')

    <div class="mb-3">
        <a href="{{ route('faq.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a preguntas frecuentes
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">{{ $esNueva ? 'Nueva pregunta frecuente' : 'Editar pregunta frecuente' }}</h1>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 760px">
        <div class="card-body">

            {{-- Validación doble: en el navegador (needs-validation, public/js/app.js) y en el servidor (PreguntaFrecuenteRequest) --}}
            <form method="POST" class="needs-validation" novalidate
                  action="{{ $esNueva ? route('faq.guardar') : route('faq.actualizar', $pregunta) }}">
                @csrf

                {{-- Categoría (obligatoria) --}}
                <div class="mb-3">
                    <label for="categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                    <select id="categoria" name="categoria" required
                            class="form-select @error('categoria') is-invalid @enderror">
                        <option value="">Seleccione una categoría</option>
                        @foreach ($categorias as $opcion)
                            <option value="{{ $opcion }}" @selected(old('categoria', $pregunta->categoria) === $opcion)>{{ $opcion }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">@error('categoria'){{ $message }}@else Complete los campos obligatorios @enderror</div>
                </div>

                {{-- Pregunta (obligatoria, máx. 200) --}}
                <div class="mb-3">
                    <label for="pregunta" class="form-label">Pregunta <span class="text-danger">*</span></label>
                    <input type="text" id="pregunta" name="pregunta" maxlength="200" required
                           value="{{ old('pregunta', $pregunta->pregunta) }}"
                           placeholder="Ejemplo: ¿Cómo saco una cita?"
                           class="form-control @error('pregunta') is-invalid @enderror">
                    <div class="invalid-feedback">@error('pregunta'){{ $message }}@else Complete los campos obligatorios @enderror</div>
                </div>

                {{-- Respuesta (obligatoria, máx. 1000) --}}
                <div class="mb-3">
                    <label for="respuesta" class="form-label">Respuesta <span class="text-danger">*</span></label>
                    <textarea id="respuesta" name="respuesta" rows="5" maxlength="1000" required
                              class="form-control @error('respuesta') is-invalid @enderror">{{ old('respuesta', $pregunta->respuesta) }}</textarea>
                    <div class="invalid-feedback">@error('respuesta'){{ $message }}@else Complete los campos obligatorios @enderror</div>
                    <div class="form-text">Es el texto con el que responde el asistente virtual. Máximo 1000 caracteres.</div>
                </div>

                {{-- Palabras clave (obligatorias, máx. 300) --}}
                <div class="mb-4">
                    <label for="palabras_clave" class="form-label">Palabras clave <span class="text-danger">*</span></label>
                    <input type="text" id="palabras_clave" name="palabras_clave" maxlength="300" required
                           value="{{ old('palabras_clave', $pregunta->palabras_clave) }}"
                           placeholder="Ejemplo: sacar, reservar, agendar, nueva cita"
                           class="form-control @error('palabras_clave') is-invalid @enderror">
                    <div class="invalid-feedback">@error('palabras_clave'){{ $message }}@else Complete los campos obligatorios @enderror</div>
                    <div class="form-text">
                        Sepárelas con comas. Incluya las formas en que un paciente lo preguntaría
                        (por ejemplo: <em>pagar, pago, tarjeta</em>). Se puede usar una frase: <em>no puedo ir</em>.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar
                    </button>
                    <a href="{{ route('faq.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

@endsection
