{{-- CU19 · mostrarReporte(reporte): totales, gráfico y detalle (dentro del Paso 2 "Resultado"; 6b se muestra en los filtros). --}}
@php
    $fmt = [\App\Modulos\reportes\service\ExportadorReporte::class, 'formatear'];
    $color = [\App\Modulos\reportes\controller\generar_reporte\ReporteController::class, 'colorEstado'];
    // Tarjetas: los totales generales · Etiquetas de color: los totales por estado
    $tarjetas = array_values(array_filter($reporte['totales'], fn ($t) => ! isset($t['estado'])));
    $porEstado = array_values(array_filter($reporte['totales'], fn ($t) => isset($t['estado'])));
@endphp

    {{-- Totales: cifras clave grandes --}}
    <div class="row g-3 mt-1">
        @foreach ($tarjetas as $t)
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card h-100 bg-transparent">
                    <div class="card-body">
                        <div class="small text-muted">{{ $t['etiqueta'] }}</div>
                        <div class="fs-3 fw-semibold text-nowrap">{{ $fmt($t['valor'], $t['tipo']) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Total por estado: una fila de etiquetas de color (el texto dice el estado; el color solo ayuda) --}}
    @if ($porEstado)
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            <span class="small text-muted me-1">Por estado:</span>
            @foreach ($porEstado as $t)
                <span class="badge rounded-pill text-bg-{{ $color($t['estado']) }} fs-6 fw-normal {{ $t['valor'] ? '' : 'opacity-50' }}">
                    {{ $t['etiqueta'] }} <strong class="ms-1">{{ $t['valor'] }}</strong>
                </span>
            @endforeach
        </div>
    @endif

    {{-- Gráfico (Chart.js, se dibuja en public/js/app.js sección 13). Solo en pantalla. --}}
    @if ($reporte['grafico'])
        @php($idGrafico = 'grafico-' . $reporte['tipo'])
        <div class="card bg-transparent mt-3">
            <div class="card-body">
                <h3 class="h6 fw-semibold mb-3">{{ $reporte['grafico']['titulo'] }}</h3>
                <div style="position: relative; height: 280px;">
                    <canvas data-grafico="{{ $idGrafico }}" role="img"
                            aria-label="{{ $reporte['grafico']['titulo'] }} (el detalle está en la tabla de abajo)"></canvas>
                </div>
                <script type="application/json" id="{{ $idGrafico }}">@json($reporte['grafico'])</script>
            </div>
        </div>
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        @endpush
    @endif

    {{-- Detalle --}}
    <div class="mt-4">
        <div>
            <h3 class="h6 fw-semibold mb-2">Detalle</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            @foreach ($reporte['columnas'] as $col)
                                <th class="text-muted fw-semibold small text-nowrap {{ in_array($col['tipo'], ['numero', 'moneda'], true) ? 'text-end' : '' }}">{{ $col['titulo'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reporte['filas'] as $fila)
                            <tr>
                                @foreach ($reporte['columnas'] as $i => $col)
                                    @php($valor = $fila[$i] ?? null)
                                    @if ($col['tipo'] === 'estado')
                                        <td><span class="badge text-bg-{{ $color($valor) }}">{{ $valor }}</span></td>
                                    @else
                                        <td class="{{ $col['tipo'] === 'texto' ? '' : 'text-end text-nowrap' }}">{{ $fmt($valor, $col['tipo']) }}</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    @if ($reporte['pie'])
                        <tfoot class="fw-semibold">
                            <tr>
                                @foreach ($reporte['columnas'] as $i => $col)
                                    <td class="{{ $col['tipo'] === 'texto' ? '' : 'text-end text-nowrap' }}">{{ $fmt($reporte['pie'][$i] ?? null, $col['tipo']) }}</td>
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            <div class="small text-muted mt-2">{{ count($reporte['filas']) }} {{ count($reporte['filas']) === 1 ? 'fila' : 'filas' }}</div>
        </div>
    </div>
