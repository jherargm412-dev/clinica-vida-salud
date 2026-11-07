<?php

namespace App\Modulos\pagos\models;

use App\Modulos\citas\models\Cita;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad Notificacion (tabla "notificacion"): avisos al paciente sobre su cita (RF29, RF31).
 *
 * Cada aviso se intenta primero por push (en todos los dispositivos donde el paciente las activó, RF30)
 * y, si no tiene ninguno o fallan todos, por correo (Brevo). Lo envía App\Modulos\pagos\service\Notificador.
 *   canal:  'Push' o 'Correo' = por dónde salió (mientras está Pendiente vale 'Push': es lo primero que se intenta)
 *   estado: Pendiente → Enviada / Fallida, o Anulada (recordatorio que ya no corresponde)
 */
class Notificacion extends Model
{
    protected $table = 'notificacion';
    protected $primaryKey = 'id_notificacion';
    public $timestamps = false;

    // Tipos de aviso (RF31)
    public const PROGRAMADA = 'Cita programada';      // CU21: tras el pago
    public const REPROGRAMADA = 'Cita reprogramada';  // CU14
    public const CANCELADA = 'Cita cancelada';        // CU15 (solo citas Programadas o Confirmadas)
    public const RECORDATORIO = 'Recordatorio';       // 24 h antes de la cita

    /** Horas de anticipación del recordatorio. */
    public const HORAS_RECORDATORIO = 24;

    protected $fillable = ['id_cita', 'tipo', 'canal', 'programada_para', 'enviada_en', 'estado'];

    protected $casts = [
        'programada_para' => 'datetime',
        'enviada_en' => 'datetime',
    ];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    /**
     * CU21 paso 8, CU14 y CU15: guarda un aviso para enviarlo YA ("Pendiente").
     * Se envía con Notificador::enviar() DESPUÉS de guardar la operación (DB::afterCommit),
     * así un fallo del envío nunca deshace el pago, la reprogramación o la cancelación.
     */
    public static function registrarAviso(int $idCita, string $tipo): self
    {
        return self::create([
            'id_cita' => $idCita,
            'tipo' => $tipo,
            'canal' => 'Push',
            'programada_para' => now(),
            'estado' => 'Pendiente',
        ]);
    }

    /** Fecha y hora en que empieza la cita. */
    private static function inicioDe(Cita $cita): Carbon
    {
        return Carbon::parse($cita->fecha->toDateString() . ' ' . $cita->hora_inicio);
    }

    /**
     * RF31: programa el recordatorio 24 h antes de la cita (anula el que hubiera).
     * Si faltan menos de 24 h (cita agendada o reprogramada a último momento) no se programa:
     * el paciente acaba de recibir el aviso de programada o reprogramada.
     */
    public static function programarRecordatorio(Cita $cita): void
    {
        self::anularRecordatorio($cita->id_cita);

        $momento = self::inicioDe($cita)->subHours(self::HORAS_RECORDATORIO);
        if ($momento->lessThanOrEqualTo(now())) {
            return;
        }

        self::create([
            'id_cita' => $cita->id_cita,
            'tipo' => self::RECORDATORIO,
            'canal' => 'Push',
            'programada_para' => $momento,
            'estado' => 'Pendiente',
        ]);
    }

    /** CU15 / CU14: el recordatorio pendiente de la cita ya no debe enviarse. */
    public static function anularRecordatorio(int $idCita): void
    {
        self::where('id_cita', $idCita)
            ->where('tipo', self::RECORDATORIO)
            ->where('estado', 'Pendiente')
            ->update(['estado' => 'Anulada']);
    }

    /**
     * Respaldo del scheduler: citas Programadas o Confirmadas a más de 24 h que nunca tuvieron
     * recordatorio (p. ej. agendadas antes de que existiera esta función). Devuelve cuántos creó.
     */
    public static function crearRecordatoriosFaltantes(): int
    {
        $citas = Cita::query()
            ->whereIn('estado', ['Programada', 'Confirmada'])
            ->whereRaw('(fecha + hora_inicio) > ?', [now()->addHours(self::HORAS_RECORDATORIO)->toDateTimeString()])
            ->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('notificacion')
                ->whereColumn('notificacion.id_cita', 'cita.id_cita')
                ->where('notificacion.tipo', self::RECORDATORIO))
            ->get();

        foreach ($citas as $cita) {
            self::programarRecordatorio($cita);
        }

        return $citas->count();
    }

    /** Recordatorios que ya toca enviar (los revisa el scheduler cada 5 minutos). */
    public static function recordatoriosPorEnviar(): Collection
    {
        return self::where('tipo', self::RECORDATORIO)
            ->where('estado', 'Pendiente')
            ->where('programada_para', '<=', now())
            ->orderBy('programada_para')
            ->get();
    }

    public function marcarEnviada(string $canal): void
    {
        $this->update(['canal' => $canal, 'estado' => 'Enviada', 'enviada_en' => now()]);
    }

    public function marcarFallida(): void
    {
        $this->update(['canal' => 'Correo', 'estado' => 'Fallida']);
    }

    public function marcarAnulada(): void
    {
        $this->update(['estado' => 'Anulada']);
    }
}
