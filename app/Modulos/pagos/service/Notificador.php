<?php

namespace App\Modulos\pagos\service;

use App\Modulos\citas\models\Cita;
use App\Modulos\pagos\models\Notificacion;
use App\Modulos\usuarios_acceso\models\SuscripcionPush;
use App\Modulos\usuarios_acceso\models\Usuario;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * RF31: envía los avisos de cita al paciente.
 *   1. Push en TODOS los dispositivos donde el paciente activó las notificaciones (RF30).
 *   2. Si no tiene ninguno activo o fallan todos → correo (Brevo).
 *   3. Si tampoco hay correo o falla → la notificación queda "Fallida" (no se deshace nada).
 *
 * Uso: Notificador::enviar($notificacion)  (o app(Notificador::class)->procesar($notificacion))
 * Se resuelve desde el contenedor de Laravel para que las pruebas puedan reemplazar el envío push.
 */
class Notificador
{
    /** Segundos máximos de espera al servicio push de cada navegador. */
    private const TIEMPO_ESPERA = 10;

    /** Atajo: Notificador::enviar($aviso). */
    public static function enviar(Notificacion $notificacion): void
    {
        app(self::class)->procesar($notificacion);
    }

    public function procesar(Notificacion $notificacion): void
    {
        $cita = Cita::obtenerDetallePorId($notificacion->id_cita);
        if (! $cita) {
            $notificacion->marcarFallida();
            return;
        }

        // Un recordatorio solo se envía si la cita sigue vigente y todavía no empezó
        if ($notificacion->tipo === Notificacion::RECORDATORIO && ! $this->recordatorioVigente($cita)) {
            $notificacion->marcarAnulada();
            return;
        }

        $usuario = $cita->paciente?->usuario;
        $contenido = $this->contenido($notificacion->tipo, $cita);
        $contenido['id_notificacion'] = $notificacion->id_notificacion;

        if ($usuario && $this->enviarPush($usuario, $contenido)) {
            $notificacion->marcarEnviada('Push');
        } elseif ($usuario && $this->enviarCorreo($usuario, $contenido, $cita)) {
            $notificacion->marcarEnviada('Correo');
        } else {
            $notificacion->marcarFallida();
        }
    }

    private function recordatorioVigente(Cita $cita): bool
    {
        $inicio = Carbon::parse($cita->fecha->toDateString() . ' ' . $cita->hora_inicio);

        return in_array($cita->estado, ['Programada', 'Confirmada'], true) && $inicio->isFuture();
    }

    /** Título y texto del aviso según su tipo. */
    private function contenido(string $tipo, Cita $cita): array
    {
        $cuando = $cita->fecha->format('d/m/Y') . ' a las ' . substr($cita->hora_inicio, 0, 5);
        $deQuien = $cita->especialidad->nombre . ' con ' . $cita->medico->nombre_completo;

        [$titulo, $texto] = match ($tipo) {
            Notificacion::PROGRAMADA => ['Cita programada',
                "Recibimos su pago. Su cita de {$deQuien} quedó programada para el {$cuando}."],
            Notificacion::REPROGRAMADA => ['Cita reprogramada',
                "Su cita de {$deQuien} fue reprogramada para el {$cuando}."],
            Notificacion::CANCELADA => ['Cita cancelada',
                "Su cita de {$deQuien} del {$cuando} fue cancelada."
                . ($cita->motivo_cancelacion ? " Motivo: {$cita->motivo_cancelacion}." : '')],
            default => ['Recordatorio de cita',
                "Le recordamos su cita de {$deQuien} el {$cuando}."],
        };

        return [
            'titulo' => $titulo,
            'texto' => $texto,
            'codigo' => $cita->codigo,
            'url' => route('historial.mis_citas'),   // al pulsar el aviso se abre "Mis citas"
        ];
    }

    /**
     * Envía el push a todos los dispositivos activos del paciente.
     * Devuelve true si llegó al menos a uno. Las suscripciones que el navegador borró se desactivan.
     */
    protected function enviarPush(Usuario $usuario, array $contenido): bool
    {
        $suscripciones = SuscripcionPush::activasDe($usuario->id_usuario);
        $clavePublica = config('services.webpush.public_key');
        $clavePrivada = config('services.webpush.private_key');

        if ($suscripciones->isEmpty() || ! $clavePublica || ! $clavePrivada) {
            return false;
        }

        try {
            $webPush = new WebPush(
                ['VAPID' => [
                    'subject' => config('services.webpush.subject'),
                    'publicKey' => $clavePublica,
                    'privateKey' => $clavePrivada,
                ]],
                ['TTL' => 86400, 'urgency' => 'high'],   // el servicio guarda el aviso hasta 24 h si el dispositivo está apagado
                new Client(['timeout' => self::TIEMPO_ESPERA])
            );

            // Lo que recibe public/sw.js (el service worker) para mostrar el aviso
            $mensaje = json_encode([
                'titulo' => $contenido['titulo'],
                'texto' => $contenido['texto'],
                'url' => $contenido['url'],
                // Etiqueta única por aviso: así ninguno reemplaza a otro (con la misma etiqueta, macOS
                // reemplazaba en silencio el aviso anterior de la cita y no mostraba el banner)
                'etiqueta' => 'aviso-' . ($contenido['id_notificacion'] ?? uniqid()),
            ], JSON_UNESCAPED_UNICODE);

            foreach ($suscripciones as $suscripcion) {
                $webPush->queueNotification(Subscription::create([
                    'endpoint' => $suscripcion->endpoint,
                    'keys' => ['p256dh' => $suscripcion->clave_p256dh, 'auth' => $suscripcion->clave_auth],
                    'contentEncoding' => 'aes128gcm',   // cifrado estándar (RFC 8291) de todos los navegadores actuales
                ]), $mensaje);
            }

            $alguno = false;
            foreach ($webPush->flush() as $reporte) {
                if ($reporte->isSuccess()) {
                    $alguno = true;
                } elseif ($reporte->isSubscriptionExpired()) {
                    SuscripcionPush::desactivarPorEndpoint($reporte->getEndpoint());   // 404/410: ya no existe
                } else {
                    Log::warning('Push no entregado: ' . $reporte->getReason());
                }
            }

            return $alguno;
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el push: ' . $e->getMessage());

            return false;
        }
    }

    /** Respaldo por correo (Brevo). */
    protected function enviarCorreo(Usuario $usuario, array $contenido, Cita $cita): bool
    {
        if (! $usuario->correo) {
            return false;   // el paciente no tiene correo registrado
        }

        try {
            Mail::send('pagos.notificaciones.correo_aviso_cita', [
                'cita' => $cita,
                'titulo' => $contenido['titulo'],
                'texto' => $contenido['texto'],
                'url' => $contenido['url'],
            ], function ($mensaje) use ($usuario, $contenido) {
                $mensaje->to($usuario->correo, $usuario->nombre_completo)
                    ->subject($contenido['titulo'] . ' ' . $contenido['codigo'] . ' - ' . config('app.name'));
            });

            return true;
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el correo de la cita ' . $cita->codigo . ': ' . $e->getMessage());

            return false;
        }
    }
}
