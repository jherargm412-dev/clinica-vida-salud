<?php

namespace App\Modulos\reportes\service;

use Carbon\Carbon;

/**
 * CU19: convierte el filtro "Período" (Diario, Semanal, Mensual o Rango de fechas)
 * en una fecha de inicio y una fecha de fin, y en un texto para mostrar en el reporte.
 *
 * Campos del formulario que usa:
 *  - periodo:      'diario' | 'semanal' | 'mensual' | 'rango'
 *  - fecha:        Y-m-d  (diario: ese día; semanal: cualquier día de la semana)
 *  - mes:          Y-m    (mensual)
 *  - fecha_inicio / fecha_fin: Y-m-d (rango)
 */
class Periodo
{
    public const TIPOS = [
        'diario'  => 'Diario',
        'semanal' => 'Semanal',
        'mensual' => 'Mensual',
        'rango'   => 'Rango de fechas',
    ];

    private const MESES = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
        'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    /**
     * Devuelve [fechaInicio, fechaFin] (Carbon, sin hora) según el período elegido.
     * Los datos ya vienen validados por el controlador.
     */
    public static function resolver(array $datos): array
    {
        return match ($datos['periodo']) {
            'diario'  => [Carbon::parse($datos['fecha'])->startOfDay(), Carbon::parse($datos['fecha'])->startOfDay()],
            // Semana de lunes a domingo que contiene la fecha elegida
            'semanal' => [Carbon::parse($datos['fecha'])->startOfWeek(Carbon::MONDAY)->startOfDay(),
                          Carbon::parse($datos['fecha'])->endOfWeek(Carbon::SUNDAY)->startOfDay()],
            'mensual' => [Carbon::createFromFormat('Y-m-d', $datos['mes'] . '-01')->startOfDay(),
                          Carbon::createFromFormat('Y-m-d', $datos['mes'] . '-01')->endOfMonth()->startOfDay()],
            'rango'   => [Carbon::parse($datos['fecha_inicio'])->startOfDay(), Carbon::parse($datos['fecha_fin'])->startOfDay()],
        };
    }

    /** Texto del período para los filtros aplicados, p. ej. "Mensual: noviembre de 2026 (01/11/2026 al 30/11/2026)". */
    public static function describir(array $datos): string
    {
        [$inicio, $fin] = self::resolver($datos);
        $rango = $inicio->format('d/m/Y') . ' al ' . $fin->format('d/m/Y');

        return match ($datos['periodo']) {
            'diario'  => 'Diario: ' . $inicio->format('d/m/Y'),
            'semanal' => 'Semanal: ' . $rango,
            'mensual' => 'Mensual: ' . self::MESES[$inicio->month] . ' de ' . $inicio->year . ' (' . $rango . ')',
            'rango'   => 'Del ' . $rango,
        };
    }

    /**
     * Fecha corta y legible para tablas por día y gráficos: "Lun 2 nov" (o "Lun 2 nov 2026" si $conAnio).
     */
    public static function etiquetaCorta(Carbon $fecha, bool $conAnio = false): string
    {
        $dias = [1 => 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $meses = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

        return $dias[$fecha->dayOfWeekIso] . ' ' . $fecha->day . ' ' . $meses[$fecha->month] . ($conAnio ? ' ' . $fecha->year : '');
    }

    /** "nov 2026" (para gráficos agrupados por mes). */
    public static function etiquetaMes(Carbon $fecha): string
    {
        $meses = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

        return $meses[$fecha->month] . ' ' . $fecha->year;
    }

    /** "jueves 06/11/2026" (para la agenda diaria). */
    public static function diaConNombre(string $fecha): string
    {
        $dias = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $f = Carbon::parse($fecha);

        return $dias[$f->dayOfWeekIso] . ' ' . $f->format('d/m/Y');
    }
}
