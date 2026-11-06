{{--
    CU19 · Archivo PDF del reporte (lo convierte Dompdf; A4, listo para imprimir).
    Dompdf entiende HTML y CSS sencillos: tablas y estilos en línea, sin Bootstrap.
--}}
@php
    $fmt = [\App\Modulos\reportes\service\ExportadorReporte::class, 'formatear'];
    $color = [\App\Modulos\reportes\controller\generar_reporte\ReporteController::class, 'colorEstado'];
    // Mismos colores que los badges de Bootstrap en pantalla (fondo, texto)
    $pastilla = [
        'primary' => ['#0d6efd', '#fff'], 'success' => ['#198754', '#fff'], 'info' => ['#0dcaf0', '#000'],
        'warning' => ['#ffc107', '#000'], 'danger' => ['#dc3545', '#fff'], 'secondary' => ['#6c757d', '#fff'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $reporte['titulo'] }}</title>
    <style>
        @page { margin: 24mm 14mm 18mm 14mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #222; }
        .encabezado { border-bottom: 2px solid #1F5FBF; padding-bottom: 6px; margin-bottom: 10px; }
        .clinica { font-size: 15px; font-weight: bold; color: #1F5FBF; }
        .titulo { font-size: 13px; font-weight: bold; margin-top: 2px; }
        .generado { color: #666; margin-top: 2px; }
        h3 { font-size: 10.5px; margin: 10px 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        .datos td { padding: 2px 6px 2px 0; vertical-align: top; }
        .datos td.etiqueta { color: #666; width: 30%; }
        .detalle th { background: #DDEBF7; text-align: left; padding: 5px 4px; border: 1px solid #b7c9de; }
        .detalle td { padding: 4px; border: 1px solid #d5dbe3; }
        .detalle tr:nth-child(even) td { background: #f6f8fb; }
        .detalle tfoot td { font-weight: bold; background: #eef2f7; }
        .derecha { text-align: right; white-space: nowrap; }
        .estado { padding: 1px 6px; border-radius: 8px; font-size: 8.5px; }
    </style>
</head>
<body>
    {{-- Encabezado: clínica, tipo de reporte, fecha y hora de generación y usuario --}}
    <div class="encabezado">
        <div class="clinica">{{ $clinica }}</div>
        <div class="titulo">{{ $reporte['titulo'] }}</div>
        <div class="generado">Generado el {{ $generado }} por {{ $usuario->nombre_completo }} ({{ $usuario->nombre_usuario }})</div>
    </div>

    <table>
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <h3>Filtros aplicados</h3>
                <table class="datos">
                    @foreach ($reporte['filtros'] as $filtro => $valor)
                        <tr><td class="etiqueta">{{ $filtro }}</td><td>{{ $valor }}</td></tr>
                    @endforeach
                </table>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <h3>Totales</h3>
                <table class="datos">
                    @foreach ($reporte['totales'] as $t)
                        <tr><td class="etiqueta" style="width: 60%;">{{ $t['etiqueta'] }}</td><td><strong>{{ $fmt($t['valor'], $t['tipo']) }}</strong></td></tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    <h3>Detalle</h3>
    <table class="detalle">
        <thead>
            <tr>
                @foreach ($reporte['columnas'] as $col)
                    <th class="{{ in_array($col['tipo'], ['numero', 'moneda'], true) ? 'derecha' : '' }}">{{ $col['titulo'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($reporte['filas'] as $fila)
                <tr>
                    @foreach ($reporte['columnas'] as $i => $col)
                        @if ($col['tipo'] === 'estado')
                            @php([$fondo, $tinta] = $pastilla[$color($fila[$i])])
                            <td><span class="estado" style="background: {{ $fondo }}; color: {{ $tinta }};">{{ $fila[$i] }}</span></td>
                        @else
                            <td class="{{ $col['tipo'] === 'texto' ? '' : 'derecha' }}">{{ $fmt($fila[$i] ?? null, $col['tipo']) }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
        @if ($reporte['pie'])
            <tfoot>
                <tr>
                    @foreach ($reporte['columnas'] as $i => $col)
                        <td class="{{ $col['tipo'] === 'texto' ? '' : 'derecha' }}">{{ $fmt($reporte['pie'][$i] ?? null, $col['tipo']) }}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
