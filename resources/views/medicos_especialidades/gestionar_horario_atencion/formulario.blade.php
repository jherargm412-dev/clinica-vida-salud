{{-- CU11 Gestionar Horario de Atención --}}
@extends('compartido.app')

@php
    $esNuevo = ! $horario->exists;
@endphp

@section('titulo', $esNuevo ? 'Nuevo horario' : 'Editar horario')

@section('contenido')
    <div class="mb-3">
        <a href="{{ route('horarios.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a horarios
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">
            {{ $esNuevo ? 'Nuevo horario' : 'Editar horario' }}
        </h1>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 700px">
        <div class="card-body">
            <form method="POST" novalidate
                  action="{{ $esNuevo ? route('horarios.guardar') : route('horarios.actualizar', $horario) }}">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="id_medico" class="form-label">Médico <span class="text-danger">*</span></label>
                        <select id="id_medico" name="id_medico" class="form-select @error('id_medico') is-invalid @enderror">
                            <option value="">Seleccione…</option>
                            @foreach ($medicos as $medico)
                                <option value="{{ $medico->id_medico }}" @selected(old('id_medico', $horario->id_medico) == $medico->id_medico)>
                                    {{ $medico->nombre_completo }} ({{ $medico->codigo }})
                                </option>
                            @endforeach
                        </select>
                        @error('id_medico') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="dia_semana" class="form-label">Día <span class="text-danger">*</span></label>
                        <select id="dia_semana" name="dia_semana" class="form-select @error('dia_semana') is-invalid @enderror">
                            <option value="">Seleccione…</option>
                            @foreach ($dias as $valor => $nombre)
                                <option value="{{ $valor }}" @selected(old('dia_semana', $horario->dia_semana) == $valor)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('dia_semana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="hora_inicio" class="form-label">Hora inicio <span class="text-danger">*</span></label>
                        <input type="time" id="hora_inicio" name="hora_inicio" value="{{ old('hora_inicio', $horario->hora_inicio) }}"
                               class="form-control @error('hora_inicio') is-invalid @enderror">
                        @error('hora_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="hora_fin" class="form-label">Hora fin <span class="text-danger">*</span></label>
                        <input type="time" id="hora_fin" name="hora_fin" value="{{ old('hora_fin', $horario->hora_fin) }}"
                               class="form-control @error('hora_fin') is-invalid @enderror">
                        @error('hora_fin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="duracion_cita" class="form-label">Duración cita (min) <span class="text-danger">*</span></label>
                        <input type="number" id="duracion_cita" name="duracion_cita" min="15" max="180" step="5"
                               value="{{ old('duracion_cita', $horario->duracion_cita ?: 30) }}"
                               class="form-control @error('duracion_cita') is-invalid @enderror">
                        @error('duracion_cita') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    El sistema valida que el horario no se superponga con otro del mismo médico y que no existan citas pendientes para ese bloque.
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar
                    </button>
                    <a href="{{ route('horarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
