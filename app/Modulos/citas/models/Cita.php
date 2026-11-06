<?php

namespace App\Modulos\citas\models;

use App\Modulos\agenda_medica\models\AtencionMedica;
use App\Modulos\compartido\Texto;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\pagos\models\Pago;
use App\Modulos\usuarios_acceso\models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/** Entidad Cita (tabla "cita"). */
class Cita extends Model
{
    protected $table = 'cita';

    protected $primaryKey = 'id_cita';

    public $timestamps = false;

    public const ESTADOS = ['Pendiente de pago', 'Programada', 'Confirmada', 'Atendida', 'Cancelada', 'Inasistencia'];

    /** CU21: minutos que el horario queda reservado mientras la cita está "Pendiente de pago". */
    public const MINUTOS_RESERVA_PAGO = 10;

    protected $fillable = [
        'id_paciente', 'id_medico', 'id_especialidad', 'id_usuario_cancelacion',
        'codigo', 'fecha', 'hora_inicio', 'hora_fin', 'motivo', 'estado',
        'motivo_cancelacion', 'fecha_cancelacion', 'cancelada_por',
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

    // Cita 1 — 0..1 Pago (CU21)
    public function pago(): HasOne
    {
        return $this->hasOne(Pago::class, 'id_cita', 'id_cita');
    }

    public function atencionMedica(): HasOne
    {
        return $this->hasOne(AtencionMedica::class, 'id_cita', 'id_cita');
    }

    /** CU08: lista todas las citas del paciente, opcionalmente filtradas por estado. */
    public static function listarPorPaciente(int $idPaciente, ?string $estado = null): Builder
    {
        return self::query()
            ->where('id_paciente', $idPaciente)
            ->when($estado !== null, fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->orderByDesc('fecha_creacion');
    }

    /** CU16: citas que pueden confirmarse, atenderse o marcarse como inasistencia. */
    public static function listarGestionables(): Builder
    {
        return self::query()
            ->whereIn('estado', ['Programada', 'Confirmada'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio');
    }

    /** CU16: busca citas gestionables por código, CI, nombre/apellido o fecha. */
    public static function buscarGestionables(?string $texto = null, ?string $fecha = null): Builder
    {
        $consulta = self::listarGestionables();

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

    /** CU16: aplica únicamente las transiciones permitidas según el estado actual. */
    public static function cambiarEstado(int $idCita, string $nuevoEstado): bool
    {
        $estadosOrigen = match ($nuevoEstado) {
            'Confirmada' => ['Programada'],
            'Atendida', 'Inasistencia' => ['Programada', 'Confirmada'],
            default => [],
        };

        if ($estadosOrigen === []) {
            return false;
        }

        return self::query()
            ->whereKey($idCita)
            ->whereIn('estado', $estadosOrigen)
            ->update(['estado' => $nuevoEstado]) > 0;
    }

    /** CU17: citas de un médico filtradas por fecha y estado, incluyendo reservas vigentes. */
    public static function listarPorMedicoFechaEstado(int $idMedico, ?string $fecha, ?string $estado): Builder
    {
        return self::query()
            ->where('id_medico', $idMedico)
            ->where(function ($consulta) {
                $consulta->where('estado', '<>', 'Pendiente de pago')
                    ->orWhere(function ($pendiente) {
                        $pendiente->where('estado', 'Pendiente de pago')
                            ->where('fecha_creacion', '>', now()->subMinutes(self::MINUTOS_RESERVA_PAGO));
                    });
            })
            ->when($fecha, fn ($consulta) => $consulta->whereDate('fecha', $fecha))
            ->when($estado, fn ($consulta) => $consulta->where('estado', $estado))
            ->orderBy('fecha')
            ->orderBy('hora_inicio');
    }

    /** CU18: citas Programadas, Confirmadas o Atendidas del médico. */
    public static function listarPorMedicoYEstados(int $idMedico, array $estados): Builder
    {
        return self::query()
            ->where('id_medico', $idMedico)
            ->whereIn('estado', $estados)
            ->orderByDesc('fecha')
            ->orderBy('hora_inicio');
    }

    /** CU18: comprueba que la cita pertenece al médico y permite registrar atención. */
    public static function validarPuedeRegistrarAtencion(int $idCita, int $idMedico): bool
    {
        return self::query()
            ->whereKey($idCita)
            ->where('id_medico', $idMedico)
            ->whereIn('estado', ['Programada', 'Confirmada', 'Atendida'])
            ->exists();
    }

    /** CU18: marca como Atendida una cita Programada o Confirmada del médico. */
    public static function marcarAtendida(int $idCita): bool
    {
        return self::query()
            ->whereKey($idCita)
            ->whereIn('estado', ['Programada', 'Confirmada'])
            ->update(['estado' => 'Atendida']) > 0;
    }

    // ---------------------------------------------------------------
    // CU21 Pagar cita: reserva de 10 minutos
    // ---------------------------------------------------------------

    /** Fecha y hora en que vence la reserva del horario (fecha de creación + 10 minutos). */
    public function venceReserva(): Carbon
    {
        return $this->fecha_creacion->copy()->addMinutes(self::MINUTOS_RESERVA_PAGO);
    }

    /** CU21 (calcularTiempoRestante): segundos que quedan para pagar (0 si ya venció). */
    public function segundosRestantesPago(): int
    {
        return max(0, (int) now()->diffInSeconds($this->venceReserva(), false));
    }

    /** CU21 (retencionVigente): ¿sigue "Pendiente de pago" y dentro de los 10 minutos? */
    public function retencionVigente(): bool
    {
        return $this->estado === 'Pendiente de pago' && $this->venceReserva()->isFuture();
    }

    /** CU21 (paso 1): citas pendientes de pago; si se indica el paciente, solo las suyas. */
    public static function listarPendientesDePago(?int $idPaciente = null): Collection
    {
        return self::query()
            ->with(['paciente.usuario', 'medico.usuario', 'especialidad'])
            ->where('estado', 'Pendiente de pago')
            ->when($idPaciente, fn ($q) => $q->where('id_paciente', $idPaciente))
            ->orderBy('fecha_creacion')
            ->get();
    }

    /** CU21 (7a): ids de las citas cuya reserva de 10 minutos ya venció. */
    public static function idsReservasVencidas(): array
    {
        return self::query()
            ->where('estado', 'Pendiente de pago')
            ->where('fecha_creacion', '<=', now()->subMinutes(self::MINUTOS_RESERVA_PAGO))
            ->pluck('id_cita')
            ->all();
    }

    /** CU21 (paso 7 marcarProgramada): el pago se completó; la cita ocupa el horario. */
    public function marcarProgramada(): void
    {
        $this->update(['estado' => 'Programada']);
    }

    /** CU21 (7a y Cancelar): la cancela el sistema y el horario queda libre. */
    public function cancelarPorSistema(string $motivo): void
    {
        $this->update([
            'estado' => 'Cancelada',
            'motivo_cancelacion' => $motivo,
            'fecha_cancelacion' => now(),
            'cancelada_por' => 'Sistema',
            'id_usuario_cancelacion' => null,
        ]);
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

    /** CU20 (4.2): ¿hay citas de la especialidad esperando su pago? (no se puede desactivar su tarifa) */
    public static function existenPendientesDePago(int $idEspecialidad): bool
    {
        return self::query()
            ->where('id_especialidad', $idEspecialidad)
            ->where('estado', 'Pendiente de pago')
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

    /**
     * CU13 (secuencia 5.8) y CU14: ¿el paciente ya tiene una cita vigente que se superpone?
     * En CU14 se excluye la propia cita que se está reprogramando.
     */
    public static function existeCitaSuperpuestaPaciente(
        int $idPaciente,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int $idCitaExcluida = null
    ): bool {
        return self::query()
            ->where('id_paciente', $idPaciente)
            ->whereDate('fecha', $fecha)
            ->where('estado', '<>', 'Cancelada')
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->when($idCitaExcluida, fn ($q) => $q->where('id_cita', '<>', $idCitaExcluida))
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

    /**
     * CU11 (secuencia 4 y 5): ¿existen citas pendientes (de hoy en adelante) que caen en este horario del médico?
     * Las citas pasadas no cuentan: aunque sigan "Programadas" (CU16 aún no las cierra), ya no se atenderán.
     */
    public static function existenCitasPendientes(int $idMedico, int $diaSemana, string $horaInicio, string $horaFin): bool
    {
        $diaPg = $diaSemana % 7;   // PostgreSQL: 0 = domingo ... 6 = sábado

        return self::query()
            ->where('id_medico', $idMedico)
            ->whereIn('estado', ['Programada', 'Confirmada'])
            ->whereDate('fecha', '>=', now()->toDateString())
            ->whereRaw('EXTRACT(DOW FROM fecha) = ?', [$diaPg])
            ->whereRaw('hora_inicio < ?', [$horaFin])
            ->whereRaw('hora_fin > ?', [$horaInicio])
            ->exists();
    }
}
