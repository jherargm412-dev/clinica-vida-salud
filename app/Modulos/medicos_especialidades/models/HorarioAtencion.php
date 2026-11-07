<?php

namespace App\Modulos\medicos_especialidades\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/** Entidad HorarioAtencion (tabla "horario_atencion"). dia_semana: 1 = lunes ... 7 = domingo. */
class HorarioAtencion extends Model
{
    protected $table = 'horario_atencion';
    protected $primaryKey = 'id_horario';
    public $timestamps = false;

    protected $fillable = [
        'id_medico', 'dia_semana', 'hora_inicio', 'hora_fin', 'duracion_cita', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'dia_semana' => 'integer',
        'duracion_cita' => 'integer',
    ];

    public const DIAS = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
    ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }

    /** CU11 (secuencia 2): horarios según médico y filtros. */
    public static function listarPorMedico(int $idMedico, array $filtros = []): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->where('id_medico', $idMedico)
            ->when(isset($filtros['dia']) && $filtros['dia'] !== null, fn ($q) => $q->where('dia_semana', $filtros['dia']))
            ->when(isset($filtros['estado']) && $filtros['estado'] !== null, fn ($q) => $q->where('activo', $filtros['estado'] === 'activo'))
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
    }

    /** CU12 (secuencia 3): horarios activos de un médico para el día consultado. */
    public static function listarHorariosActivosPorDia(int $idMedico, int $diaSemana): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->where('id_medico', $idMedico)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->orderBy('hora_inicio')
            ->get();
    }

    /** CU13 (secuencia 5.6): verifica que un bloque pertenezca a un horario activo. */
    public static function obtenerHorarioActivoParaBloque(
        int $idMedico,
        string $fecha,
        string $horaInicio,
        string $horaFin
    ): ?self {
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;
        $inicioCita = Carbon::parse($fecha . ' ' . $horaInicio);
        $finCita = Carbon::parse($fecha . ' ' . $horaFin);

        foreach (self::listarHorariosActivosPorDia($idMedico, $diaSemana) as $horario) {
            $inicioHorario = Carbon::parse($fecha . ' ' . $horario->hora_inicio);
            $finHorario = Carbon::parse($fecha . ' ' . $horario->hora_fin);
            $duracionSegundos = $horario->duracion_cita * 60;
            $desplazamiento = $inicioCita->getTimestamp() - $inicioHorario->getTimestamp();

            if (
                $duracionSegundos > 0
                && $inicioCita->greaterThanOrEqualTo($inicioHorario)
                && $finCita->lessThanOrEqualTo($finHorario)
                && $finCita->getTimestamp() - $inicioCita->getTimestamp() === $duracionSegundos
                && $desplazamiento % $duracionSegundos === 0
            ) {
                return $horario;
            }
        }

        return null;
    }

    /** Abreviaturas de los días para la lista (1 = lunes ... 7 = domingo). */
    public const DIAS_CORTOS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];

    /** "08:00:00" → "08:00" */
    public static function horaCorta(?string $hora): string
    {
        return substr((string) $hora, 0, 5);
    }

    /**
     * CU11 (lista por médico): resumen de la semana agrupando días seguidos con los mismos tramos.
     * Ej.: [['dias' => 'Lun – Vie', 'tramos' => '08:00–12:00 · 14:00–18:00', 'duracion' => '30 min'], ...]
     */
    public static function resumenSemanal(iterable $horarios): array
    {
        // Firma de cada día: sus tramos y duraciones, en orden
        $porDia = [];
        foreach ($horarios as $h) {
            $porDia[$h->dia_semana][] = $h;
        }

        $grupos = [];
        foreach (self::DIAS as $dia => $nombre) {
            if (empty($porDia[$dia])) {
                continue;
            }
            usort($porDia[$dia], fn ($a, $b) => strcmp($a->hora_inicio, $b->hora_inicio));
            $tramos = implode(' · ', array_map(fn ($h) => self::horaCorta($h->hora_inicio) . '–' . self::horaCorta($h->hora_fin), $porDia[$dia]));
            $duracion = implode(' / ', array_unique(array_map(fn ($h) => $h->duracion_cita . ' min', $porDia[$dia])));

            $ultimo = end($grupos);
            if ($ultimo && $ultimo['hasta'] === $dia - 1 && $ultimo['tramos'] === $tramos && $ultimo['duracion'] === $duracion) {
                $grupos[array_key_last($grupos)]['hasta'] = $dia;   // el día sigue al grupo anterior con lo mismo
            } else {
                $grupos[] = ['desde' => $dia, 'hasta' => $dia, 'tramos' => $tramos, 'duracion' => $duracion];
            }
        }

        return array_map(fn ($g) => [
            'dias' => $g['desde'] === $g['hasta']
                ? self::DIAS_CORTOS[$g['desde']]
                : self::DIAS_CORTOS[$g['desde']] . ($g['hasta'] - $g['desde'] === 1 ? ', ' : ' – ') . self::DIAS_CORTOS[$g['hasta']],
            'tramos' => $g['tramos'],
            'duracion' => $g['duracion'],
        ], $grupos);
    }

    /** CU11 (secuencia 3 y 6): ¿el horario se superpone con otro del mismo médico? */
    public static function existeSuperposicion(int $idMedico, int $diaSemana, string $horaInicio, string $horaFin, ?int $idHorarioExcluido = null): bool
    {
        return self::query()
            ->where('id_medico', $idMedico)
            ->where('dia_semana', $diaSemana)
            ->where('activo', true)
            ->when($idHorarioExcluido, fn ($q) => $q->where('id_horario', '<>', $idHorarioExcluido))
            ->whereRaw('hora_inicio < ?', [$horaFin])
            ->whereRaw('hora_fin > ?', [$horaInicio])
            ->exists();
    }
}
