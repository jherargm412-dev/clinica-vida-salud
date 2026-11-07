{{-- CU23 Gestionar Preguntas Frecuentes · UI_PreguntasFrecuentes: mostrar lista / mostrar resultado --}}
@extends('compartido.app')

@section('titulo', 'Preguntas frecuentes')

@section('contenido')

    {{-- Encabezado: título + botón "Nueva pregunta" (detallar, paso 3) --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Preguntas frecuentes</h1>
            <p class="text-muted mb-0">Preguntas y respuestas que usa el asistente virtual</p>
        </div>
        <a href="{{ route('faq.crear') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva pregunta
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            {{-- Filtros (paso 2): texto, categoría y estado --}}
            <form method="GET" action="{{ route('faq.index') }}" class="row g-2 mb-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="buscar" value="{{ $texto }}" class="form-control" maxlength="100"
                               placeholder="Buscar en la pregunta o en las palabras clave">
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <select name="categoria" class="form-select" aria-label="Categoría">
                        <option value="">Categoría: Todas</option>
                        @foreach ($categorias as $opcion)
                            <option value="{{ $opcion }}" @selected($categoria === $opcion)>{{ $opcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <select name="estado" class="form-select" aria-label="Estado">
                        <option value="">Estado: Todos</option>
                        <option value="activo" @selected($estado === 'activo')>Activo</option>
                        <option value="inactivo" @selected($estado === 'inactivo')>Inactivo</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-outline-primary">Buscar</button>
                    @if ($texto || $categoria || $estado)
                        <a href="{{ route('faq.index') }}" class="btn btn-link">Limpiar</a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Categoría</th>
                            <th>Pregunta</th>
                            <th>Palabras clave</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preguntas as $pregunta)
                            <tr>
                                <td class="text-nowrap">
                                    <i class="bi {{ \App\Modulos\asistencia_usuario\models\PreguntaFrecuente::ICONOS[$pregunta->categoria] ?? 'bi-question-circle' }} me-1 text-primary"></i>
                                    {{ $pregunta->categoria }}
                                </td>
                                <td style="min-width: 240px">
                                    <div class="fw-semibold">{{ $pregunta->pregunta }}</div>
                                    {{-- Vista previa de la respuesta (las primeras 110 letras) --}}
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($pregunta->respuesta, 110) }}</small>
                                </td>
                                <td style="min-width: 200px">
                                    @foreach ($pregunta->listaPalabrasClave() as $clave)
                                        <span class="badge rounded-pill text-bg-light border fw-normal">{{ $clave }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if ($pregunta->activo)
                                        <span class="badge text-bg-success">Activo</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('faq.editar', $pregunta) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>

                                    {{-- Desactivar / Activar: con confirmación (public/js/app.js, punto 3) --}}
                                    @if ($pregunta->activo)
                                        <form method="POST" action="{{ route('faq.desactivar', $pregunta) }}" class="d-inline"
                                              data-confirmar="¿Desactivar la pregunta «{{ $pregunta->pregunta }}»? El asistente virtual dejará de usarla.">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-slash-circle"></i> Desactivar
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('faq.activar', $pregunta) }}" class="d-inline"
                                              data-confirmar="¿Activar la pregunta «{{ $pregunta->pregunta }}»?">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle"></i> Activar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No se encontraron preguntas frecuentes</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
