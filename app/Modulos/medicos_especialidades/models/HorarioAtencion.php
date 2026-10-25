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
