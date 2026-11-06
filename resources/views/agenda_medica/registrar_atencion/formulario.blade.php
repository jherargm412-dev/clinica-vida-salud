{{-- CU18 Registrar Atención de Cita · UI_atencionCita --}}
@extends('compartido.app')

@section('titulo', 'Registrar atención ' . $cita->codigo)

@section('contenido')
    <div class="mb-3">
        <a href="{{ route('atencion.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a citas para atender
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-1">Registrar atención</h1>
        <p class="text-muted mb-0">Cita {{ $cita->codigo }}</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Datos de la cita</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Paciente</dt>
                        <dd class="col-sm-7">{{ $paciente->nombre_completo }}</dd>
                        <dt class="col-sm-5">CI</dt>
                        <dd class="col-sm-7">{{ $paciente->ci }}</dd>
                        <dt class="col-sm-5">Fecha</dt>
                        <dd class="col-sm-7">{{ $cita->fecha->format('d/m/Y') }}</dd>
                        <dt class="col-sm-5">Horario</dt>
                        <dd class="col-sm-7">{{ substr($cita->hora_inicio, 0, 5) }}–{{ substr($cita->hora_fin, 0, 5) }}</dd>
                        <dt class="col-sm-5">Especialidad</dt>
                        <dd class="col-sm-7">{{ $cita->especialidad?->nombre ?? '—' }}</dd>
                        <dt class="col-sm-5">Estado</dt>
                        <dd class="col-sm-7">{{ $cita->estado }}</dd>
                        @if ($cita->motivo)
                            <dt class="col-sm-5">Motivo de consulta</dt>
                            <dd class="col-sm-7">{{ $cita->motivo }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Perfil médico del paciente</h2>
                    @if ($perfilMedico)
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Tipo de sangre</dt>
                            <dd class="col-sm-7">{{ $perfilMedico->tipo_sangre ?: 'No registrado' }}</dd>
                            <dt class="col-sm-5">Alergias</dt>
                            <dd class="col-sm-7">{{ $perfilMedico->alergias ?: 'No registradas' }}</dd>
                            <dt class="col-sm-5">Enfermedades crónicas</dt>
                            <dd class="col-sm-7">{{ $perfilMedico->enfermedades_cronicas ?: 'No registradas' }}</dd>
                            <dt class="col-sm-5">Medicamentos</dt>
                            <dd class="col-sm-7">{{ $perfilMedico->medicamentos ?: 'No registrados' }}</dd>
                            <dt class="col-sm-5">Contacto de emergencia</dt>
                            <dd class="col-sm-7">
                                @if ($perfilMedico->nombre_contacto_emergencia)
                                    {{ $perfilMedico->nombre_contacto_emergencia }} ({{ $perfilMedico->parentesco_contacto_emergencia }})
                                    <br>{{ $perfilMedico->telefono_contacto_emergencia }}
                                @else
                                    No registrado
                                @endif
                            </dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">El paciente no tiene un perfil médico registrado.</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Registro clínico</h2>
                    <form method="POST" action="{{ route('atencion.guardar', $cita->id_cita) }}"
                          class="needs-validation" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="diagnostico" class="form-label">Diagnóstico <span class="text-danger">*</span></label>
                            <textarea id="diagnostico" name="diagnostico" rows="3" maxlength="500" required
                                      class="form-control @error('diagnostico') is-invalid @enderror">{{ old('diagnostico') }}</textarea>
                            @error('diagnostico') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="notas_clinicas" class="form-label">Notas clínicas</label>
                            <textarea id="notas_clinicas" name="notas_clinicas" rows="5" maxlength="2000"
                                      class="form-control @error('notas_clinicas') is-invalid @enderror">{{ old('notas_clinicas') }}</textarea>
                            @error('notas_clinicas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('atencion.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Guardar atención
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
