<?php

namespace App\Modulos\pagos\models;

use App\Modulos\citas\models\Cita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Entidad Notificacion (tabla "notificacion"): avisos al paciente sobre su cita (RF29).
 * Por ahora solo se envía por correo (Brevo). PENDIENTE: notificaciones push.
 */
class Notificacion extends Model
{
    protected $table = 'notificacion';
    protected $primaryKey = 'id_notificacion';
    public $timestamps = false;

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
     * CU21 (paso 8 registrarAviso): guarda el aviso "Pendiente".
     * 8a: el paciente no tiene suscripción push (todavía no existe), así que el canal es Correo.
     */
    public static function registrarAviso(int $idCita, string $tipo): self
    {
        return self::create([
            'id_cita' => $idCita,
            'tipo' => $tipo,
            'canal' => 'Correo',
            'programada_para' => now(),
            'estado' => 'Pendiente',
        ]);
    }

    /**
     * Envía el aviso por correo. Se llama DESPUÉS de guardar el pago: si el correo falla,
     * la notificación queda "Fallida" y el pago NO se revierte (8a).
     */
    public function enviarCorreo(): void
    {
        $cita = Cita::obtenerDetallePorId($this->id_cita);
        $correo = $cita?->paciente?->usuario?->correo;

        if (! $correo) {
            $this->update(['estado' => 'Fallida']);   // el paciente no tiene correo registrado
            return;
        }

        try {
            Mail::send('pagos.pagar_cita.correo_cita_programada', ['cita' => $cita], function ($mensaje) use ($correo, $cita) {
                $mensaje->to($correo, $cita->paciente->nombre_completo)
                    ->subject('Cita programada ' . $cita->codigo . ' - ' . config('app.name'));
            });
            $this->update(['estado' => 'Enviada', 'enviada_en' => now()]);
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el aviso de la cita ' . $cita->codigo . ': ' . $e->getMessage());
            $this->update(['estado' => 'Fallida']);
        }
    }
}
