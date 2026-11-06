{{--
    CU19 Generar Reporte · UI_Reportes: mostrarReportes / mostrarFiltros (Paso 1 "Filtros").
    El resultado (Paso 2) está en resultado.blade.php.
--}}
@extends('compartido.app')

@section('titulo', 'Reportes')

@section('contenido')

    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-0">Reportes</h1>
        <p class="text-muted mb-0">Información resumida de citas, pacientes, ingresos y caja</p>
    </div>

    @include('reportes.generar_reporte._pestanas')

    @if (! $tipo)
        {{-- Aún no eligió un reporte (paso 3) --}}
        <div class="row g-3">
            @foreach ($reportes as $grupo => $deGrupo)
                @foreach ($deGrupo as $clave => $r)
                    <div class="col-md-6 col-xl-4">
                        <a href="{{ route('reportes.index', ['tipo' => $clave]) }}"
                           class="card h-100 border-0 shadow-sm text-decoration-none">
                            <div class="card-body">
                                <div class="small text-muted">{{ $grupo }}</div>
                                <div class="fw-semibold fs-5"><i class="bi {{ $r['icono'] }} me-1"></i> {{ $r['titulo'] }}</div>
                                <div class="small text-muted">{{ $r['descripcion'] }}</div>
                            </div>
                        </a>
                    </div>
                @endforeach
            @endforeach
        </div>
    @else
        <div class="card border-0 shadow-sm">
            {{-- Barra de pasos (la misma de "Registrar cita") --}}
            <div class="card-header bg-transparent py-3">
                @include('citas.registrar_cita._pasos', [
                    'pasos' => ['filtros', 'resultado'], 'actual' => 'filtros',
                    'nombresPasos' => ['filtros' => 'Filtros', 'resultado' => 'Resultado'],
                    'etiquetaPasos' => 'Pasos para generar el reporte',
                ])
            </div>

            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h2 class="h5 fw-semibold mb-0"><i class="bi {{ $info['icono'] }} me-1"></i> {{ $info['titulo'] }}</h2>
                        <p class="text-muted small">{{ $info['descripcion'] }}</p>
                    </div>
                    {{-- Exportar solo se habilita en el resultado (paso 7); aquí se ve deshabilitado (6b) --}}
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-danger" disabled title="Genere el reporte para poder exportarlo">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Exportar PDF
                        </button>
                        @if ($info['excel'])
                            <button type="button" class="btn btn-outline-success" disabled title="Genere el reporte para poder exportarlo">
                                <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
                            </button>
                        @endif
                    </div>
                </div>

                {{-- 6b: no hay datos para los filtros elegidos (vuelve al paso 5) --}}
                @if ($reporte && $reporte['vacio'])
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-1"></i>
                        {{ \App\Modulos\reportes\controller\generar_reporte\ReporteController::MSJ_SIN_RESULTADOS }}
                    </div>
                @endif

                {{--
                    Pasos 4 y 5: filtros + "Generar" (GET: los filtros quedan en la URL; si hay datos se abre el Paso 2).
                    autocomplete="off": que el navegador no recupere valores viejos al recargar o volver atrás.
                --}}
                <form method="GET" action="{{ route('reportes.index') }}" class="needs-validation" novalidate autocomplete="off">
                    <input type="hidden" name="tipo" value="{{ $tipo }}">
                    <input type="hidden" name="generar" value="1">

                    @include('reportes.generar_reporte._rapidos')
                    @include('reportes.generar_reporte._filtros')

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i> Generar
                        </button>
                        <a href="{{ route('reportes.index', ['tipo' => $tipo]) }}" class="btn btn-link">Limpiar filtros</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endsection
