<?php

namespace App\Modulos\citas\models;

use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Entidad Cita (tabla "cita"). */
class Cita extends Model
{
    protected $table = 'cita';
    protected $primaryKey = 'id_cita';
    public $timestamps = false;

    public const ESTADOS = ['Programada', 'Confirmada', 'Atendida', 'Cancelada', 'Inasistencia'];

    protected $fillable = [
        'id_paciente', 'id_medico', 'id_especialidad', 'id_usuario_cancelacion',
        'codigo', 'fecha', 'hora_inicio', 'hora_fin', 'motivo', 'estado',
        'motivo_cancelacion', 'fecha_cancelacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_creacion' => 'datetime',
        'fecha_cancelacion' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad');
    }

    public function usuarioCancelacion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_cancelacion', 'id_usuario');
    }

    /** CU06 (secuencia 5): ¿tiene el paciente citas futuras aún programadas o confirmadas? */
    public static function existenCitasPendientesPaciente(int $idPaciente): bool
    {
        return self::query()
            ->where('id_paciente', $idPaciente)
            ->whereDate('fecha', '>=', now()->toDateString())
            ->whereIn('estado', ['Programada', 'Confirmada'])
            ->exists();
    }

    /** CU12 (secuencia 3): intervalos ocupados por citas no canceladas en una fecha. */
    public static function listarHorasOcupadas(int $idMedico, string $fecha): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->select(['hora_inicio', 'hora_fin'])
            ->where('id_medico', $idMedico)
            ->whereDate('fecha', $fecha)
            ->where('estado', '<>', 'Cancelada')
            ->orderBy('hora_inicio')
            ->get();
    }

    /** CU11 (secuencia 4 y 5): ¿existen citas pendientes que colisionan con este horario del médico? */
    public static function existenCitasPendientes(int $idMedico, int $diaSemana, string $horaInicio, string $horaFin, ?int $idHorarioExcluido = null): bool
    {
        $diaPg = $diaSemana % 7;

        return self::query()
            ->where('id_medico', $idMedico)
            ->whereIn('estado', ['Programada', 'Confirmada'])
            ->whereRaw('EXTRACT(DOW FROM fecha) = ?', [$diaPg])
            ->whereRaw('hora_inicio < ?', [$horaFin])
            ->whereRaw('hora_fin > ?', [$horaInicio])
            ->when($idHorarioExcluido, fn ($q) => $q->where('id_cita', '<>', $idHorarioExcluido))
            ->exists();
    }
}
