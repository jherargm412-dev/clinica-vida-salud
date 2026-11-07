<?php

namespace App\Modulos\pagos\service;

use App\Modulos\citas\models\Cita;
use App\Modulos\pagos\models\Pago;
use Illuminate\Support\Facades\DB;

/**
 * Reserva de 10 minutos de CU21 (flujo alternativo 7a y "Cancelar").
 *
 * Una cita "Pendiente de pago" ocupa su horario durante 10 minutos. Si no se paga a tiempo,
 * el sistema la cancela (canceladaPor = 'Sistema'), anula el pago y libera el horario.
 *
 * No hace falta un cron: liberarVencidas() se llama al inicio de cada operación que depende
 * de las reservas (consultar disponibilidad, registrar cita, pagar, desactivar tarifa, cerrar caja).
 * Además existe el comando "php artisan pagos:liberar-reservas" (routes/console.php) por si se
 * programa el scheduler.
 */
class ReservaPago
{
    public const MOTIVO_VENCIDA = 'Pago no realizado a tiempo';
    public const MOTIVO_CANCELADA = 'Pago cancelado';

    /** Cancela todas las citas cuya reserva venció. Devuelve cuántas canceló. */
    public static function liberarVencidas(): int
    {
        $canceladas = 0;

        foreach (Cita::idsReservasVencidas() as $idCita) {
            if (self::anularReserva($idCita, self::MOTIVO_VENCIDA, soloSiVencida: true) !== null) {
                $canceladas++;
            }
        }

        return $canceladas;
    }

    /**
     * Cancela la cita "Pendiente de pago", anula su pago y sus detalles pendientes.
     * Después de guardar, cierra en Stripe la sesión que estaba abierta (o la reembolsa si alcanzó a pagarse).
     *
     * Devuelve ['codigo' => 'CIT-...', 'efectivoDevolver' => '50.00'] o null si la cita ya no estaba pendiente.
     */
    public static function anularReserva(int $idCita, string $motivo, bool $soloSiVencida = false): ?array
    {
        return DB::transaction(function () use ($idCita, $motivo, $soloSiVencida): ?array {
            $cita = Cita::query()->whereKey($idCita)->lockForUpdate()->first();

            if (! $cita || $cita->estado !== 'Pendiente de pago') {
                return null;   // ya se pagó o ya se canceló (p. ej. otra petición se adelantó)
            }
            if ($soloSiVencida && $cita->retencionVigente()) {
                return null;
            }

            $efectivoCentavos = 0;
            $sesionesStripe = [];

            $pago = Pago::obtenerPorCita($idCita, bloquear: true);
            if ($pago && $pago->estado === 'Pendiente') {
                $pendientes = $pago->detalles()->with('metodoPago')->where('estado', 'Pendiente')->get();
                foreach ($pendientes as $detalle) {
                    if ($detalle->esEfectivo()) {
                        $efectivoCentavos += Pago::aCentavos($detalle->monto);   // se devuelve en el momento
                    } elseif ($detalle->id_sesion_pasarela) {
                        $sesionesStripe[] = $detalle->id_sesion_pasarela;
                    }
                }
                $pago->anular();
            }

            $cita->cancelarPorSistema($motivo);

            // Stripe se llama solo si la transacción se guardó bien (afterCommit)
            if ($sesionesStripe) {
                DB::afterCommit(function () use ($sesionesStripe) {
                    $pasarela = app(PasarelaPagoEnLinea::class);
                    foreach ($sesionesStripe as $idSesion) {
                        $pasarela->cerrarSesion($idSesion);
                    }
                });
            }

            return [
                'codigo' => $cita->codigo,
                'efectivoDevolver' => Pago::deCentavos($efectivoCentavos),
            ];
        });
    }
}
