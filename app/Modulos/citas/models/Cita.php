<?php

namespace App\Modulos\citas\models;

use App\Modulos\compartido\Texto;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
    public static function listarHorasOcupadas(
        int $idMedico,
        string $fecha,
        ?int $idCitaExcluida = null
    ): Collection {
        return self::query()
            ->select(['hora_inicio', 'hora_fin'])
            ->where('id_medico', $idMedico)
            ->whereDate('fecha', $fecha)
            ->where('estado', '<>', 'Cancelada')
            ->when($idCitaExcluida, fn ($q) => $q->where('id_cita', '<>', $idCitaExcluida))
            ->orderBy('hora_inicio')
            ->get();
    }

    /** CU14 (secuencia 1): citas futuras Programadas o Confirmadas. */
    public static function listarReprogramables(): Builder
    {
        return self::query()
            ->with(['paciente.usuario', 'medico.usuario', 'especialidad'])
            ->whereIn('estado', ['Programada', 'Confirmada'])
            ->where(function ($q) {
                $q->whereDate('fecha', '>', now()->toDateString())
                    ->orWhere(function ($hoy) {
                        $hoy->whereDate('fecha', now()->toDateString())
                            ->where('hora_inicio', '>', now()->format('H:i:s'));
                    });
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio');
    }

    /** CU15 (secuencia 1): citas que todavía se pueden cancelar. */
    public static function listarCancelables(): Builder
    {
        return self::query()
            ->with(['paciente.usuario', 'medico.usuario', 'especialidad'])
            ->whereIn('estado', ['Programada', 'Confirmada'])
            ->where(function ($q) {
                $q->whereDate('fecha', '>', now()->toDateString())
                    ->orWhere(function ($hoy) {
                        $hoy->whereDate('fecha', now()->toDateString())
                            ->where('hora_inicio', '>', now()->format('H:i:s'));
                    });
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio');
    }

    /** CU15 (secuencia 2): búsqueda de citas cancelables por código, paciente y fecha. */
    public static function buscarCancelables(?string $texto = null, ?string $fecha = null): Builder
    {
        $consulta = self::listarCancelables();

        if ($texto !== null && trim($texto) !== '') {
            $buscado = '%'.Texto::sinTildes(trim($texto)).'%';
            $consulta->where(function ($q) use ($buscado) {
                $q->where('codigo', 'ILIKE', $buscado)
                    ->orWhereHas('paciente', fn ($paciente) => $paciente
                        ->where('ci', 'ILIKE', $buscado)
                        ->orWhereHas('usuario', fn ($usuario) => $usuario->whereRaw(
                            Texto::sqlSinTildes("nombres || ' ' || apellidos").' ILIKE ?',
                            [$buscado]
                        )));
            });
        }

        return $consulta->when($fecha, fn ($q) => $q->whereDate('fecha', $fecha));
    }

    /** CU15 (secuencia 4): cancela solo citas que siguen Programadas o Confirmadas. */
    public static function marcarCitaCancelada(
        int $idCita,
        string $motivoCancelacion,
        int $idUsuario
    ): bool {
        return self::query()
            ->whereKey($idCita)
            ->whereIn('estado', ['Programada', 'Confirmada'])
            ->update([
                'estado' => 'Cancelada',
                'motivo_cancelacion' => $motivoCancelacion,
                'fecha_cancelacion' => now(),
                'id_usuario_cancelacion' => $idUsuario,
            ]) > 0;
    }

    /** CU14 (secuencia 2): búsqueda por código, CI, nombre o apellido y fecha. */
    public static function buscarReprogramables(?string $texto = null, ?string $fecha = null): Builder
    {
        $consulta = self::listarReprogramables();

        if ($texto !== null && trim($texto) !== '') {
            $buscado = '%'.Texto::sinTildes(trim($texto)).'%';
            $consulta->where(function ($q) use ($buscado) {
                $q->where('codigo', 'ILIKE', $buscado)
                    ->orWhereHas('paciente', fn ($paciente) => $paciente
                        ->where('ci', 'ILIKE', $buscado)
                        ->orWhereHas('usuario', fn ($usuario) => $usuario->whereRaw(
                            Texto::sqlSinTildes("nombres || ' ' || apellidos").' ILIKE ?',
                            [$buscado]
                        )));
            });
        }

        return $consulta->when($fecha, fn ($q) => $q->whereDate('fecha', $fecha));
    }

    /** CU14 (secuencia 3): detalle de la cita y sus relaciones. */
    public static function obtenerDetallePorId(int $idCita): ?self
    {
        return self::query()
            ->with(['paciente.usuario', 'medico.usuario', 'especialidad'])
            ->find($idCita);
    }

    /** CU14 (secuencia 5.6): cambia únicamente la fecha y las horas. */
    public function actualizarFechaHoraCita(string $fecha, string $horaInicio, string $horaFin): void
    {
        $this->update([
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
        ]);
    }

    /** CU13 (secuencia 5.7): ¿el médico ya tiene una cita vigente que se superpone? */
    public static function existeCitaSuperpuesta(
        int $idMedico,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int $idCitaExcluida = null
    ): bool {
        return self::query()
            ->where('id_medico', $idMedico)
            ->whereDate('fecha', $fecha)
            ->where('estado', '<>', 'Cancelada')
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->when($idCitaExcluida, fn ($q) => $q->where('id_cita', '<>', $idCitaExcluida))
            ->exists();
    }

    /** CU13 (secuencia 5.8): ¿el paciente ya tiene una cita vigente que se superpone? */
    public static function existeCitaSuperpuestaPaciente(
        int $idPaciente,
        string $fecha,
        string $horaInicio,
        string $horaFin
    ): bool {
        return self::query()
            ->where('id_paciente', $idPaciente)
            ->whereDate('fecha', $fecha)
            ->where('estado', '<>', 'Cancelada')
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->exists();
    }

    /** CU13 (secuencia 5.9): siguiente código CIT-000001, CIT-000002, ... */
    public static function generarCodigoCita(): string
    {
        $idCita = (int) DB::scalar("SELECT nextval(pg_get_serial_sequence('cita', 'id_cita'))");

        return sprintf('CIT-%06d', $idCita);
    }

    /** CU13 (secuencia 5.10): inserta la cita con sus datos ya validados. */
    public static function insertarCita(array $datos): self
    {
        $cita = new self;
        $cita->setAttribute('id_cita', (int) substr($datos['codigo'], 4));
        $cita->fill($datos);
        $cita->save();

        return $cita;
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
