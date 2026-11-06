{{--
    CU19 Generar Reporte · UI_Reportes: mostrarReporte (Paso 2 "Resultado") y exportar (pasos 7 y 8).
    Se abre al presionar "Generar" con filtros válidos y datos. "Atrás" vuelve a los filtros con los mismos valores.
--}}
@extends('compartido.app')

@section('titulo', $reporte['titulo'] . ' · Reportes')

@section('contenido')

    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-0">Reportes</h1>
        <p class="text-muted mb-0">Información resumida de citas, pacientes, ingresos y caja</p>
    </div>

    @include('reportes.generar_reporte._pestanas')

    <div class="card border-0 shadow-sm">
        {{-- Barra de pasos: "Filtros" ya hecho (se puede volver a abrir) y "Resultado" actual --}}
        <div class="card-header bg-transparent py-3">
            @include('citas.registrar_cita._pasos', [
                'pasos' => ['filtros', 'resultado'], 'actual' => 'resultado',
                'nombresPasos' => ['filtros' => 'Filtros', 'resultado' => 'Resultado'],
                'etiquetaPasos' => 'Pasos para generar el reporte',
                'urlsPasos' => ['filtros' => $urlFiltros],
            ])
        </div>

        <div class="card-body p-4">
            {{-- Encabezado: reporte, filtros aplicados y exportar --}}
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-2">
                <div>
                    <h2 class="h5 fw-semibold mb-1"><i class="bi {{ $info['icono'] }} me-1"></i> {{ $reporte['titulo'] }}</h2>
                    <div class="small">
                        @foreach ($reporte['filtros'] as $filtro => $valor)
                            <span class="text-nowrap me-3"><span class="text-muted">{{ $filtro }}:</span> <span class="fw-semibold">{{ $valor }}</span></span>
                        @endforeach
                    </div>
                </div>

                {{-- Pasos 7 y 8: exportar con los mismos filtros (ya validados) del reporte que se ve --}}
                <form method="POST" action="{{ route('reportes.exportar') }}" class="d-flex flex-wrap gap-2">
                    @csrf
                    <input type="hidden" name="tipo" value="{{ $tipo }}">
                    @foreach ($filtrosExportar as $campo => $valor)
                        @if ($valor !== null && $valor !== '')
                            <input type="hidden" name="{{ $campo }}" value="{{ $valor }}">
                        @endif
                    @endforeach

                    <button type="submit" name="formato" value="pdf" class="btn btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Exportar PDF
                    </button>
                    @if ($info['excel'])
                        <button type="submit" name="formato" value="excel" class="btn btn-outline-success">
                            <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
                        </button>
                    @endif
                </form>
            </div>

            {{-- Paso 6: totales, gráfico y detalle --}}
            @include('reportes.generar_reporte._resultado')
        </div>

        {{-- Atrás: vuelve a los filtros conservando lo elegido (como en "Registrar cita") --}}
        <div class="card-footer bg-transparent py-3">
            <a href="{{ $urlFiltros }}" class="btn btn-link text-decoration-none px-0">
                <i class="bi bi-arrow-left me-1"></i> Atrás
            </a>
        </div>
    </div>

@endsection
