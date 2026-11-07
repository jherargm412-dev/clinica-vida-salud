<?php

namespace App\Modulos\citas\service;

use App\Modulos\citas\models\Cita;
use App\Modulos\medicos_especialidades\models\HorarioAtencion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cálculo de horarios libres de un médico (CU12 Consultar disponibilidad).
 * Lo usan la pantalla "Disponibilidad" (CU12) y el asistente de "Registrar cita" (CU13),
 * así las dos muestran exactamente los mismos bloques.
 *
 * Un bloque está libre si: pertenece a un horario activo del médico ese día de la semana,
 * no se cruza con una cita que no esté cancelada (las "Pendiente de pago" también ocupan)
 * y, si la fecha es hoy, todavía no empezó.
 */
class Disponibilidad
{
    /**
     * Horarios del médico en la fecha, cada uno con sus bloques libres:
     * [['horario' => HorarioAtencion,
     *   'bloques' => [['hora_inicio' => '08:00', 'hora_fin' => '08:30'], ...],                      (solo los libres)
     *   'todos'   => [['hora_inicio' => '08:00', 'hora_fin' => '08:30', 'ocupado' => false], ...]],  (libres y ocupados)
     *  ...]
     * Las horas que ya pasaron (si la fecha es hoy) no están en ninguna de las dos listas.
     * $idCitaExcluida: en CU14 la cita que se reprograma no ocupa su propio horario.
     */
    public static function horariosConBloques(int $idMedico, Carbon $fecha, ?int $idCitaExcluida = null): Collection
    {
        $horarios = HorarioAtencion::listarHorariosActivosPorDia($idMedico, $fecha->dayOfWeekIso);
        $citasOcupadas = Cita::listarHorasOcupadas($idMedico, $fecha->toDateString(), $idCitaExcluida);

        return $horarios->map(function (HorarioAtencion $horario) use ($fecha, $citasOcupadas): array {
            $bloques = [];
            $todos = [];
            $inicioHorario = Carbon::parse($fecha->toDateString() . ' ' . $horario->hora_inicio);
            $finHorario = Carbon::parse($fecha->toDateString() . ' ' . $horario->hora_fin);

            while ($inicioHorario->copy()->addMinutes($horario->duracion_cita)->lessThanOrEqualTo($finHorario)) {
                $finBloque = $inicioHorario->copy()->addMinutes($horario->duracion_cita);
                $horaInicio = $inicioHorario->format('H:i:s');
                $horaFin = $finBloque->format('H:i:s');
                $esHoraPasada = $inicioHorario->lessThanOrEqualTo(now());
                $estaOcupado = $citasOcupadas->contains(fn (Cita $cita): bool =>
                    $cita->hora_inicio < $horaFin && $cita->hora_fin > $horaInicio
                );

                if (! $esHoraPasada) {
                    $bloque = [
                        'hora_inicio' => $inicioHorario->format('H:i'),
                        'hora_fin' => $finBloque->format('H:i'),
                    ];
                    $todos[] = $bloque + ['ocupado' => $estaOcupado];
                    if (! $estaOcupado) {
                        $bloques[] = $bloque;
                    }
                }

                $inicioHorario->addMinutes($horario->duracion_cita);
            }

            return ['horario' => $horario, 'bloques' => $bloques, 'todos' => $todos];
        });
    }

    /** Lista simple de bloques libres del día, ordenados por hora. */
    public static function bloquesLibres(int $idMedico, Carbon $fecha, ?int $idCitaExcluida = null): array
    {
        return self::horariosConBloques($idMedico, $fecha, $idCitaExcluida)
            ->flatMap(fn (array $h) => $h['bloques'])
            ->sortBy('hora_inicio')
            ->values()
            ->all();
    }

    /**
     * Todas las horas del día (libres y ocupadas por otra cita), ordenadas, para mostrarlas en pantalla:
     * [['hora_inicio' => '08:00', 'hora_fin' => '08:30', 'ocupado' => false], ...]
     * Las ocupadas se muestran en rojo y no se pueden elegir (no dice de quién es la cita).
     */
    public static function horasDelDia(int $idMedico, Carbon $fecha, ?int $idCitaExcluida = null): array
    {
        return self::horariosConBloques($idMedico, $fecha, $idCitaExcluida)
            ->flatMap(fn (array $h) => $h['todos'])
            ->sortBy('hora_inicio')
            ->values()
            ->all();
    }

    /**
     * Separa los bloques en "Mañana" (antes de las 12:00) y "Tarde".
     * Solo devuelve los turnos que tienen bloques: ['Mañana' => [...], 'Tarde' => [...]]
     */
    public static function porTurno(array $bloques): array
    {
        $turnos = ['Mañana' => [], 'Tarde' => []];
        foreach ($bloques as $bloque) {
            $turnos[$bloque['hora_inicio'] < '12:00' ? 'Mañana' : 'Tarde'][] = $bloque;
        }

        return array_filter($turnos);
    }

    /**
     * Resumen de varios días seguidos (selector de fechas del asistente y semana de CU12):
     * [['fecha' => Carbon, 'tieneHorario' => bool, 'libres' => int], ...]
     */
    public static function resumenDias(int $idMedico, Carbon $desde, int $dias = 7, ?int $idCitaExcluida = null): array
    {
        $resumen = [];

        for ($i = 0; $i < $dias; $i++) {
            $fecha = $desde->copy()->addDays($i);
            $horarios = self::horariosConBloques($idMedico, $fecha, $idCitaExcluida);
            $resumen[] = [
                'fecha' => $fecha,
                'tieneHorario' => $horarios->isNotEmpty(),
                'libres' => $horarios->sum(fn (array $h) => count($h['bloques'])),
            ];
        }

        return $resumen;
    }
}
