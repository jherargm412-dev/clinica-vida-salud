<?php

namespace App\Modulos\reportes\service;

use App\Modulos\usuarios_acceso\models\Usuario;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * CU19 (generarArchivo): arma el archivo PDF (Dompdf) o Excel .xlsx (PhpSpreadsheet) de un reporte.
 * El archivo no se guarda en el servidor: se devuelve su contenido para descargarlo.
 *
 * Todos los reportes llegan con la misma forma (la arma ReporteController):
 *   titulo   => 'Reporte de citas'
 *   filtros  => ['Período' => '...', 'Estado' => 'Todos', ...]
 *   totales  => [['etiqueta' => 'Total cobrado', 'valor' => 700.0, 'tipo' => 'moneda'], ...]   (valor: número)
 *   columnas => [['titulo' => 'Monto', 'tipo' => 'moneda'], ...]   tipo: texto | estado | numero | moneda
 *   filas    => [[valor, valor, ...], ...]                (moneda y numero llegan como números)
 *   pie      => null o [valor, ...]                       (fila de totales al final de la tabla)
 */
class ExportadorReporte
{
    public const NOMBRE_CLINICA = 'Clínica Vida Salud';

    /** Formato de los montos en Excel: siguen siendo números (se pueden sumar), pero se ven como "Bs 700.00". */
    private const FORMATO_BS = '"Bs "#,##0.00';

    /** Cómo se ve un valor en pantalla y en el PDF. */
    public static function formatear(mixed $valor, string $tipo): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        return match ($tipo) {
            'moneda' => 'Bs ' . number_format((float) $valor, 2),
            'numero' => (string) $valor,
            default  => (string) $valor,
        };
    }

    /** Nombre del archivo: "reporte_de_citas_2026-11-06_1530.pdf" */
    public static function nombreArchivo(array $reporte, string $extension): string
    {
        $base = str_replace('-', '_', \Illuminate\Support\Str::slug($reporte['titulo']));
        if (! str_starts_with($base, 'reporte')) {
            $base = 'reporte_' . $base;
        }

        return $base . '_' . now()->format('Y-m-d_Hi') . '.' . $extension;
    }

    // ---------------------------------------------------------------
    // PDF
    // ---------------------------------------------------------------

    /** Devuelve el contenido del PDF (A4). */
    public static function pdf(array $reporte, Usuario $usuario): string
    {
        // Dompdf guarda su caché de fuentes: se usa una carpeta de storage (vendor no se puede escribir en Railway)
        $cache = storage_path('framework/cache/dompdf');
        File::ensureDirectoryExists($cache);

        $opciones = new Options();
        $opciones->set('defaultFont', 'DejaVu Sans');   // incluye tildes y ñ
        $opciones->set('isRemoteEnabled', false);        // no descarga nada de internet
        $opciones->set('fontCache', $cache);
        $opciones->set('fontDir', $cache);
        $opciones->set('tempDir', sys_get_temp_dir());

        $html = view('reportes.generar_reporte.pdf', [
            'reporte' => $reporte,
            'clinica' => self::NOMBRE_CLINICA,
            'generado' => now()->format('d/m/Y H:i'),
            'usuario' => $usuario,
        ])->render();

        $dompdf = new Dompdf($opciones);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $reporte['orientacion'] ?? 'portrait');
        $dompdf->render();

        // Número de página abajo a la derecha, en todas las hojas
        $canvas = $dompdf->getCanvas();
        $fuente = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 110, $canvas->get_height() - 28,
            'Página {PAGE_NUM} de {PAGE_COUNT}', $fuente, 8, [0.4, 0.4, 0.4]);

        return $dompdf->output();
    }

    // ---------------------------------------------------------------
    // Excel (.xlsx)
    // ---------------------------------------------------------------

    /** Guarda el Excel en un archivo temporal y devuelve su ruta (se borra después de enviarlo). */
    public static function excel(array $reporte, Usuario $usuario): string
    {
        $libro = new Spreadsheet();
        $libro->getProperties()->setCreator(self::NOMBRE_CLINICA)->setTitle($reporte['titulo']);
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle(mb_substr($reporte['titulo'], 0, 31));   // Excel admite hasta 31 caracteres

        $columnas = $reporte['columnas'];
        $ultimaCol = Coordinate::stringFromColumnIndex(count($columnas));

        // 1. Encabezado: clínica, tipo de reporte, fecha y usuario
        $hoja->setCellValue('A1', self::NOMBRE_CLINICA);
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $hoja->setCellValue('A2', $reporte['titulo']);
        $hoja->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $hoja->setCellValue('A3', 'Generado el ' . now()->format('d/m/Y H:i') . ' por '
            . $usuario->nombre_completo . ' (' . $usuario->nombre_usuario . ')');
        // Celdas combinadas a lo ancho de la tabla: así el texto largo no ensancha la columna A
        if (count($columnas) > 1) {
            foreach ([1, 2, 3] as $f) {
                $hoja->mergeCells("A{$f}:{$ultimaCol}{$f}");
            }
        }
        $fila = 5;

        // 2. Filtros aplicados (texto) y totales (números), con la etiqueta en A y el valor en B
        $hoja->setCellValue("A{$fila}", 'Filtros aplicados');
        $hoja->getStyle("A{$fila}")->getFont()->setBold(true);
        $fila++;
        foreach ($reporte['filtros'] as $filtro => $valor) {
            $hoja->setCellValue("A{$fila}", $filtro);
            $hoja->setCellValueExplicit("B{$fila}", (string) $valor, DataType::TYPE_STRING);
            if (count($columnas) > 2) {
                $hoja->mergeCells("B{$fila}:{$ultimaCol}{$fila}");
            }
            $fila++;
        }
        $fila++;

        $hoja->setCellValue("A{$fila}", 'Totales');
        $hoja->getStyle("A{$fila}")->getFont()->setBold(true);
        $fila++;
        foreach ($reporte['totales'] as $total) {
            $hoja->setCellValue("A{$fila}", $total['etiqueta']);
            $hoja->setCellValue("B{$fila}", $total['valor']);   // número: Excel no lo marca como "texto"
            $hoja->getStyle("B{$fila}")->getAlignment()->setHorizontal('left');
            if ($total['tipo'] === 'moneda') {
                $hoja->getStyle("B{$fila}")->getNumberFormat()->setFormatCode(self::FORMATO_BS);
            }
            $fila++;
        }
        $fila++;

        // 3. Tabla: títulos de columnas
        $filaTitulos = $fila;
        foreach ($columnas as $i => $columna) {
            $hoja->setCellValue([$i + 1, $fila], $columna['titulo']);
        }
        $hoja->getStyle("A{$fila}:{$ultimaCol}{$fila}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']],
        ]);
        $fila++;

        // 4. Filas (y la fila de totales, si el reporte la tiene)
        $filas = $reporte['filas'];
        if (! empty($reporte['pie'])) {
            $filas[] = $reporte['pie'];
        }
        foreach ($filas as $valores) {
            foreach ($columnas as $i => $columna) {
                $valor = $valores[$i] ?? null;
                if ($valor === null || $valor === '') {
                    continue;
                }
                if (in_array($columna['tipo'], ['texto', 'estado'], true)) {
                    // Como texto, para no perder ceros a la izquierda (CI, teléfono)
                    $hoja->setCellValueExplicit([$i + 1, $fila], (string) $valor, DataType::TYPE_STRING);
                } else {
                    $hoja->setCellValue([$i + 1, $fila], $valor);
                }
            }
            $fila++;
        }
        $ultimaFila = $fila - 1;

        // Formato de montos, bordes y fila de totales en negrita
        foreach ($columnas as $i => $columna) {
            if ($columna['tipo'] === 'moneda') {
                $letra = Coordinate::stringFromColumnIndex($i + 1);
                $hoja->getStyle("{$letra}" . ($filaTitulos + 1) . ":{$letra}{$ultimaFila}")
                     ->getNumberFormat()->setFormatCode(self::FORMATO_BS);
            }
        }
        $hoja->getStyle("A{$filaTitulos}:{$ultimaCol}{$ultimaFila}")
             ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        if (! empty($reporte['pie'])) {
            $hoja->getStyle("A{$ultimaFila}:{$ultimaCol}{$ultimaFila}")->getFont()->setBold(true);
        }

        // Ancho automático y títulos fijos al bajar
        for ($i = 1; $i <= count($columnas); $i++) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $hoja->freezePane('A' . ($filaTitulos + 1));

        $ruta = tempnam(sys_get_temp_dir(), 'reporte_');
        (new Xlsx($libro))->save($ruta);
        $libro->disconnectWorksheets();

        return $ruta;
    }
}
