{{-- CU11 Gestionar Horario de Atención · "Asignar horario": la semana del médico, con uno o varios tramos por día.
     Los botones "Otro tramo", "Quitar" y "Copiar el lunes" funcionan con public/js/app.js (punto 8). --}}
@extends('compartido.app')

@section('titulo', 'Asignar horario')

@php
    // Si volvió con errores, se muestra lo que había escrito; si no, lo que está guardado
    $conErrores = old('_semana') !== null;
    $n = 0;   // número de cada tramo en el formulario (para los nombres de los campos)
@endphp

@section('contenido')
    <div class="mb-3">
        <a href="{{ route('horarios.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a horarios de atención
        </a>
        <h1 class="h3 fw-semibold mt-2 mb-0">Asignar horario — {{ $medico->nombre_completo }}</h1>
        <p class="text-muted mb-0">
            {{ $medico->codigo }} · {{ $medico->especialidades->pluck('nombre')->join(', ') }}.
            Marque los días que atiende y sus horas; un día puede tener varios tramos (p. ej. mañana y tarde).
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-1"></i> No se guardó ningún cambio. Revise los días marcados en rojo.
        </div>
    @endif

    <form method="POST" action="{{ route('horarios.semana.guardar', $medico) }}" class="needs-validation" novalidate data-semana>
        @csrf
        <input type="hidden" name="_semana" value="1">

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 9rem">Día</th>
                            <th style="width: 6rem" class="text-center">Atiende</th>
                            <th>Tramos (entrada – salida · duración de cada cita)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dias as $dia => $nombre)
                            @php
                                $atiende = $conErrores ? ! empty(old("atiende.$dia")) : ! empty($tramos[$dia]);
                                $filas = $conErrores ? array_values((array) old("tramos.$dia", [])) : $tramos[$dia];
                            @endphp
                            <tr data-dia="{{ $dia }}" class="{{ $errors->has("dias.$dia") ? 'table-danger' : '' }}">
                                <td class="fw-semibold">{{ $nombre }}</td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input fs-5" name="atiende[{{ $dia }}]" value="1"
                                           data-atiende @checked($atiende) aria-label="Atiende el {{ $nombre }}">
                                </td>
                                <td>
                                    <div data-tramos>
                                        @foreach ($filas as $fila)
                                            @include('medicos_especialidades.gestionar_horario_atencion._tramo', [
                                                'dia' => $dia, 'n' => $n++, 'fila' => $fila,
                                            ])
                                        @endforeach
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-agregar-tramo>
                                            <i class="bi bi-plus-lg"></i> Otro tramo
                                        </button>
                                        <span class="text-muted small" data-no-atiende>No atiende</span>
                                    </div>
                                    @error("dias.$dia")
                                        <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                <button type="button" class="btn btn-outline-primary" data-copiar-lunes
                        data-aviso="Marque primero los días a los que quiere copiar el horario del lunes.">
                    <i class="bi bi-copy me-1"></i> Copiar el lunes a los días marcados
                </button>
                <div class="d-flex gap-2">
                    <a href="{{ route('horarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Guardar
                    </button>
                </div>
            </div>
        </div>

        {{-- Plantilla de un tramo nuevo: el JavaScript cambia __DIA__ y __N__ --}}
        <template id="plantilla-tramo">
            @include('medicos_especialidades.gestionar_horario_atencion._tramo', [
                'dia' => '__DIA__', 'n' => '__N__', 'fila' => ['hora_inicio' => '', 'hora_fin' => '', 'duracion_cita' => 30],
            ])
        </template>
    </form>
@endsection
