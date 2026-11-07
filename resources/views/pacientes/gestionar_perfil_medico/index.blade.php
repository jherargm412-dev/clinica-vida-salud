{{-- CU07 Gestionar Perfil Médico · UI_perfil_medico --}}
@extends('compartido.app')

@section('titulo', 'Mi perfil médico')

@section('contenido')

    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-0">Mi perfil médico</h1>
        <p class="text-muted mb-0">Información médica básica y contacto de emergencia</p>
    </div>

    @if ($error)
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ $error }}
        </div>
    @else
        <div class="card border-0 shadow-sm" style="max-width: 760px">
            <div class="card-body">
                <p class="small text-muted mb-4">
                    Los datos son opcionales. Si agrega un contacto de emergencia, complete sus tres campos.
                </p>

                @error('perfil_medico')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ route('perfil_medico.actualizar') }}"
                      class="needs-validation" novalidate>
                    @csrf

                    <h2 class="h6 text-muted text-uppercase mb-3">Información médica</h2>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="tipo_sangre" class="form-label">Tipo de sangre</label>
                            <select id="tipo_sangre" name="tipo_sangre" class="form-select @error('tipo_sangre') is-invalid @enderror">
                                <option value="">Seleccione…</option>
                                @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $tipo)
                                    <option value="{{ $tipo }}" @selected(old('tipo_sangre', $perfil->tipo_sangre) === $tipo)>{{ $tipo }}</option>
                                @endforeach
                            </select>
                            @error('tipo_sangre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="alergias" class="form-label">Alergias</label>
                            <textarea id="alergias" name="alergias" rows="2" maxlength="500"
                                      class="form-control @error('alergias') is-invalid @enderror">{{ old('alergias', $perfil->alergias) }}</textarea>
                            @error('alergias') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="enfermedades_cronicas" class="form-label">Enfermedades crónicas</label>
                            <textarea id="enfermedades_cronicas" name="enfermedades_cronicas" rows="2" maxlength="500"
                                      class="form-control @error('enfermedades_cronicas') is-invalid @enderror">{{ old('enfermedades_cronicas', $perfil->enfermedades_cronicas) }}</textarea>
                            @error('enfermedades_cronicas') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="medicamentos" class="form-label">Medicamentos</label>
                            <textarea id="medicamentos" name="medicamentos" rows="2" maxlength="500"
                                      class="form-control @error('medicamentos') is-invalid @enderror">{{ old('medicamentos', $perfil->medicamentos) }}</textarea>
                            @error('medicamentos') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <h2 class="h6 text-muted text-uppercase mb-3">Contacto de emergencia</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre_contacto_emergencia" class="form-label">Nombre</label>
                            <input type="text" id="nombre_contacto_emergencia" name="nombre_contacto_emergencia" maxlength="500"
                                   value="{{ old('nombre_contacto_emergencia', $perfil->nombre_contacto_emergencia) }}"
                                   class="form-control @error('nombre_contacto_emergencia') is-invalid @enderror">
                            @error('nombre_contacto_emergencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="parentesco_contacto_emergencia" class="form-label">Parentesco</label>
                            <input type="text" id="parentesco_contacto_emergencia" name="parentesco_contacto_emergencia" maxlength="500"
                                   value="{{ old('parentesco_contacto_emergencia', $perfil->parentesco_contacto_emergencia) }}"
                                   class="form-control @error('parentesco_contacto_emergencia') is-invalid @enderror">
                            @error('parentesco_contacto_emergencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="telefono_contacto_emergencia" class="form-label">Teléfono</label>
                            <input type="tel" id="telefono_contacto_emergencia" name="telefono_contacto_emergencia"
                                   minlength="7" maxlength="20" pattern="[0-9+\s-]{7,20}" inputmode="tel"
                                   value="{{ old('telefono_contacto_emergencia', $perfil->telefono_contacto_emergencia) }}"
                                   class="form-control @error('telefono_contacto_emergencia') is-invalid @enderror">
                            @error('telefono_contacto_emergencia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Guardar
                        </button>
                        <a href="{{ route('perfil_medico.mostrar') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endsection
