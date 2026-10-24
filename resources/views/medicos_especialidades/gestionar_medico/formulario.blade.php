{{-- CU10 Gestionar Médico · UI_Medicos: mostrar formulario (Registrar y Modificar) --}}
@extends('compartido.app')

@php
    $esNuevo = ! $medico->exists;
    // Especialidades marcadas: lo que se envió (si hubo error) o las que ya tiene el médico
    $seleccionadas = array_map('intval', old('especialidades', $marcadas));
    $cuentaElegida = old('id_usuario', $medico->id_usuario);
@endphp

@section('titulo', $esNuevo ? 'Nuevo médico' : 'Editar médico')

@section('contenido')

    <div class="mb-3">
        <a href="{{ route('medicos.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a médicos
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">
            {{ $esNuevo ? 'Nuevo médico' : 'Editar médico' }}
            @unless ($esNuevo) <span class="badge text-bg-light border align-middle">{{ $medico->codigo }}</span> @endunless
        </h1>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 760px">
        <div class="card-body">

            <form method="POST" novalidate
                  action="{{ $esNuevo ? route('medicos.guardar') : route('medicos.actualizar', $medico) }}">
                @csrf

                {{-- 1. Cuenta de usuario (los datos personales vienen de aquí y no se editan) --}}
                <h2 class="h6 text-muted text-uppercase mb-3">Cuenta de usuario</h2>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label for="id_usuario" class="form-label">Cuenta del médico <span class="text-danger">*</span></label>
                        <select id="id_usuario" name="id_usuario" class="form-select @error('id_usuario') is-invalid @enderror">
                            <option value="" data-nombre="" data-correo="">Seleccione una cuenta con rol Médico…</option>
                            @foreach ($cuentas as $cuenta)
                                <option value="{{ $cuenta->id_usuario }}" @selected($cuentaElegida == $cuenta->id_usuario)
                                        data-nombre="{{ $cuenta->nombre_completo }}" data-correo="{{ $cuenta->correo ?? '—' }}">
                                    {{ $cuenta->nombre_completo }} ({{ $cuenta->nombre_usuario }})
                                </option>
                            @endforeach
                        </select>
                        @error('id_usuario') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($cuentas->isEmpty())
                            <div class="form-text text-danger">
                                No hay cuentas con rol Médico sin vincular. Créela primero en
                                <a href="{{ route('usuarios.crear') }}">Usuarios</a>.
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" id="cuenta_nombre" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correo electrónico</label>
                        <input type="text" id="cuenta_correo" class="form-control" readonly>
                    </div>
                </div>

                {{-- 2. Datos profesionales --}}
                <h2 class="h6 text-muted text-uppercase mb-3">Datos profesionales</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="registro_profesional" class="form-label">Registro profesional <span class="text-danger">*</span></label>
                        <input type="text" id="registro_profesional" name="registro_profesional" maxlength="30"
                               value="{{ old('registro_profesional', $medico->registro_profesional) }}"
                               class="form-control @error('registro_profesional') is-invalid @enderror">
                        @error('registro_profesional') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                        <input type="text" id="telefono" name="telefono" maxlength="20"
                               value="{{ old('telefono', $medico->telefono) }}"
                               class="form-control @error('telefono') is-invalid @enderror">
                        @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- 3. Especialidades (una o varias) --}}
                <h2 class="h6 text-muted text-uppercase mb-2">Especialidades <span class="text-danger">*</span></h2>
                <div class="row row-cols-1 row-cols-sm-2 g-2 mb-1">
                    @forelse ($especialidades as $especialidad)
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="especialidades[]"
                                       value="{{ $especialidad->id_especialidad }}" id="esp{{ $especialidad->id_especialidad }}"
                                       @checked(in_array($especialidad->id_especialidad, $seleccionadas))>
                                <label class="form-check-label" for="esp{{ $especialidad->id_especialidad }}">
                                    {{ $especialidad->nombre }}
                                    @unless ($especialidad->activo) <span class="text-muted small">(inactiva)</span> @endunless
                                </label>
                            </div>
                        </div>
                    @empty
                        <div class="col text-danger small">
                            No hay especialidades activas. Regístrelas primero en <a href="{{ route('especialidades.crear') }}">Especialidades</a>.
                        </div>
                    @endforelse
                </div>
                @error('especialidades') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                @error('especialidades.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar
                    </button>
                    <a href="{{ route('medicos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Al elegir la cuenta, muestra su nombre y correo (solo lectura). Detallar paso 5.
    (() => {
        const select = document.getElementById('id_usuario');
        const mostrar = () => {
            const opcion = select.options[select.selectedIndex];
            document.getElementById('cuenta_nombre').value = opcion.dataset.nombre || '';
            document.getElementById('cuenta_correo').value = opcion.dataset.correo || '';
        };
        select.addEventListener('change', mostrar);
        mostrar();
    })();
</script>
@endpush
