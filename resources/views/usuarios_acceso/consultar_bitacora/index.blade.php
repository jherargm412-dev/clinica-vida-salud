{{-- CU05 Consultar Bitácora · UI_Bitacora: mostrar registros / mostrar detalle (modal) / mostrar mensaje --}}
@extends('compartido.app')

@section('titulo', 'Bitácora')

@section('contenido')

    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-0">Bitácora</h1>
        <p class="text-muted mb-0">Acciones realizadas en el sistema: quién, cuándo y desde qué IP. Solo lectura.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            {{-- Filtros (detallar paso 3): Desde, Hasta, Usuario, Acción, Tabla afectada --}}
            <form method="GET" action="{{ route('bitacora.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="desde" class="form-label small text-muted mb-1">Desde</label>
                    <input type="date" id="desde" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="form-control">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label for="hasta" class="form-label small text-muted mb-1">Hasta</label>
                    <input type="date" id="hasta" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="usuario" class="form-label small text-muted mb-1">Usuario</label>
                    <select id="usuario" name="usuario" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($usuarios as $u)
                            <option value="{{ $u->id_usuario }}" @selected(($filtros['usuario'] ?? '') == $u->id_usuario)>
                                {{ $u->nombre_completo }} ({{ $u->nombre_usuario }})
                            </option>
                        @endforeach
                        <option value="ninguno" @selected(($filtros['usuario'] ?? '') === 'ninguno')>Desconocido (usuario inexistente)</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-3">
                    <label for="accion" class="form-label small text-muted mb-1">Acción</label>
                    <select id="accion" name="accion" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($acciones as $accion)
                            <option value="{{ $accion }}" @selected(($filtros['accion'] ?? '') === $accion)>
                                {{ \App\Modulos\usuarios_acceso\models\Bitacora::legible($accion) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="tabla" class="form-label small text-muted mb-1">Tabla afectada</label>
                    <select id="tabla" name="tabla" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($tablas as $tabla)
                            <option value="{{ $tabla }}" @selected(($filtros['tabla'] ?? '') === $tabla)>{{ $tabla }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Buscar</button>
                    @if ($hayFiltros)
                        {{-- Limpiar filtros: vuelve a la lista sin filtros --}}
                        <a href="{{ route('bitacora.index') }}?desde=&amp;hasta=" class="btn btn-link">Limpiar filtros</a>
                    @endif
                </div>
            </form>

            {{-- 4a: fechas inválidas --}}
            @if ($error)
                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i> {{ $error }}</div>
            @endif

            {{-- Registros (detallar paso 2): del más reciente al más antiguo --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Fecha y hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Detalle</th>
                            <th class="text-nowrap">Tabla afectada</th>
                            <th class="text-nowrap">IP de origen</th>
                            <th class="text-end">Consulta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($registros as $registro)
                            @php
                                $nombre = $registro->usuario?->nombre_completo ?? 'Desconocido';
                                $correo = $registro->usuario?->correo ?? ($registro->usuario?->nombre_usuario ?? '');
                                $fecha  = $registro->fecha_hora?->format('d/m/Y H:i:s');
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ $fecha }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $nombre }}</div>
                                    <div class="small text-muted">{{ $correo }}</div>
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $registro->color_accion }}" title="{{ $registro->accion }}">
                                        {{ $registro->accion_legible }}
                                    </span>
                                </td>
                                <td class="text-muted" style="max-width: 360px">
                                    <div class="text-truncate">{{ $registro->detalle ?? '—' }}</div>
                                </td>
                                <td>{{ $registro->tabla_afectada ?? '—' }}</td>
                                <td class="text-nowrap"><code class="text-body">{{ $registro->ip }}</code></td>
                                <td class="text-end">
                                    {{-- Ver detalle: abre el modal con los datos de esta fila --}}
                                    <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap"
                                            data-bs-toggle="modal" data-bs-target="#modalDetalle"
                                            data-fecha="{{ $fecha }}"
                                            data-usuario="{{ $nombre }}{{ $correo ? ' (' . $correo . ')' : '' }}"
                                            data-accion="{{ $registro->accion_legible }} ({{ $registro->accion }})"
                                            data-detalle="{{ $registro->detalle ?? '—' }}"
                                            data-tabla="{{ $registro->tabla_afectada ?? '—' }}"
                                            data-ip="{{ $registro->ip }}">
                                        <i class="bi bi-eye"></i> Ver detalle
                                    </button>
                                </td>
                            </tr>
                        @empty
                            {{-- 5a (si hubo error de fechas, solo se muestra el mensaje 4a) --}}
                            @unless ($error)
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No se encontraron registros</td>
                                </tr>
                            @endunless
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $registros->links() }}
            </div>

        </div>
    </div>

    {{-- Modal "Detalle del registro" (Ver detalle) --}}
    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="modalDetalleTitulo" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="modalDetalleTitulo">Detalle del registro</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Fecha y hora</dt>   <dd class="col-sm-8" data-campo="fecha"></dd>
                        <dt class="col-sm-4">Usuario</dt>        <dd class="col-sm-8" data-campo="usuario"></dd>
                        <dt class="col-sm-4">Acción</dt>         <dd class="col-sm-8" data-campo="accion"></dd>
                        <dt class="col-sm-4">Detalle</dt>        <dd class="col-sm-8" data-campo="detalle" style="white-space: pre-wrap"></dd>
                        <dt class="col-sm-4">Tabla afectada</dt> <dd class="col-sm-8" data-campo="tabla"></dd>
                        <dt class="col-sm-4">IP de origen</dt>   <dd class="col-sm-8 mb-0" data-campo="ip"></dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Al abrir el modal, copia los datos del botón "Ver detalle" que se pulsó (textContent: sin riesgo de HTML)
    document.getElementById('modalDetalle').addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;
        evento.target.querySelectorAll('[data-campo]').forEach((dd) => {
            dd.textContent = boton.dataset[dd.dataset.campo] || '—';
        });
    });
</script>
@endpush
