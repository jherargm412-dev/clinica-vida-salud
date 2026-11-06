<?php

namespace App\Modulos\pagos\service;

use App\Modulos\citas\models\Cita;
use App\Modulos\pagos\models\DetallePago;
use App\Modulos\pagos\models\Pago;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\StripeClient;
use Throwable;

/**
 * PasarelaPagoEnLinea (boundary en los diagramas de CU21): conversa con Stripe Checkout.
 *
 * Cómo funciona Stripe Checkout incrustado (ui_mode "embedded_page"):
 *  1. crearSesionPago(): el servidor crea una "sesión de pago" en Stripe y recibe un client_secret.
 *  2. La vista (pagar.blade.php) carga Stripe.js con la clave PUBLICABLE y muestra, dentro de nuestra
 *     página, el formulario de Stripe. Los datos de la tarjeta van directo a Stripe, nunca a nuestro servidor.
 *  3. Al pagar, Stripe lleva al usuario a nuestra "return_url" con ?session_id=cs_test_...
 *  4. verificarPago(): el servidor consulta a Stripe esa sesión. NUNCA se confía en que el usuario
 *     llegó a la return_url: solo se aprueba si Stripe responde payment_status = 'paid'.
 *
 * La clave secreta se lee de STRIPE_SECRET_KEY (.env), nunca se escribe en el código.
 */
class PasarelaPagoEnLinea
{
    private ?StripeClient $stripe = null;

    private function cliente(): StripeClient
    {
        if ($this->stripe === null) {
            $clave = config('services.stripe.secret');
            if (! $clave) {
                throw new RuntimeException('Falta configurar STRIPE_SECRET_KEY en el .env');
            }
            $this->stripe = new StripeClient($clave);
        }

        return $this->stripe;
    }

    /**
     * crearSesionPago(monto, idCita): crea la sesión de Stripe Checkout (incrustada) por el monto del detalle en línea.
     * Devuelve ['idSesion' => 'cs_test_...', 'clientSecret' => '...'] (el client_secret lo usa Stripe.js en la vista).
     */
    public function crearSesionPago(DetallePago $detalle, Cita $cita, string $urlRetorno): array
    {
        $metadata = [
            'idCita' => (string) $cita->id_cita,
            'idPago' => (string) $detalle->id_pago,
            'idDetallePago' => (string) $detalle->id_detalle_pago,
        ];

        $parametros = [
            'mode' => 'payment',
            'ui_mode' => 'embedded_page',   // el formulario de Stripe se muestra dentro de nuestra página
            'locale' => 'es',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => config('services.stripe.moneda'),
                    'unit_amount' => Pago::aCentavos($detalle->monto),   // Stripe trabaja en centavos
                    'product_data' => [
                        'name' => 'Consulta de ' . $cita->especialidad->nombre,
                        'description' => 'Cita ' . $cita->codigo . ' - ' . $cita->fecha->format('d/m/Y')
                            . ' ' . substr($cita->hora_inicio, 0, 5),
                    ],
                ],
            ]],
            // Al terminar, Stripe lleva al usuario aquí; {CHECKOUT_SESSION_ID} lo reemplaza por el id real
            'return_url' => $urlRetorno . '?session_id={CHECKOUT_SESSION_ID}',
            'metadata' => $metadata,
            'payment_intent_data' => ['metadata' => $metadata],
            // Mínimo que permite Stripe (30 min). La reserva es de 10 min: al vencer, el sistema cierra la sesión.
            'expires_at' => now()->addMinutes(30)->timestamp,
        ];

        $correo = $cita->paciente?->usuario?->correo;
        if ($correo) {
            $parametros['customer_email'] = $correo;
        }

        $sesion = $this->cliente()->checkout->sessions->create($parametros);

        return ['idSesion' => $sesion->id, 'clientSecret' => $sesion->client_secret];
    }

    /**
     * verificarPago(idSesion): consulta la sesión en Stripe.
     * Devuelve: pagado (bool), referenciaExterna (payment_intent), centavos, moneda y metadata.
     */
    public function verificarPago(string $idSesion): array
    {
        $sesion = $this->cliente()->checkout->sessions->retrieve($idSesion);

        return [
            'pagado' => $sesion->payment_status === 'paid',
            'abierta' => $sesion->status === 'open',
            'clientSecret' => $sesion->status === 'open' ? $sesion->client_secret : null,   // para volver a mostrar el formulario
            'referenciaExterna' => is_string($sesion->payment_intent) ? $sesion->payment_intent : $sesion->payment_intent?->id,
            'centavos' => (int) $sesion->amount_total,
            'moneda' => (string) $sesion->currency,
            'metadata' => $sesion->metadata?->toArray() ?? [],
        ];
    }

    /**
     * Cierra una sesión que ya no debe cobrarse (cita cancelada o reserva vencida):
     *  - si sigue abierta, se expira y ya nadie puede pagarla;
     *  - si alcanzó a pagarse (caso raro: pagó justo cuando vencía), se reembolsa automáticamente.
     * Nunca lanza error: si Stripe no responde, se anota en el log.
     */
    public function cerrarSesion(string $idSesion): void
    {
        try {
            $estado = $this->verificarPago($idSesion);

            if ($estado['abierta']) {
                $this->cliente()->checkout->sessions->expire($idSesion);
            } elseif ($estado['pagado'] && $estado['referenciaExterna']) {
                $this->cliente()->refunds->create(['payment_intent' => $estado['referenciaExterna']]);
                Log::info('Pago en línea reembolsado (cita cancelada): ' . $estado['referenciaExterna']);
            }
        } catch (Throwable $e) {
            Log::error('No se pudo cerrar la sesión de Stripe ' . $idSesion . ': ' . $e->getMessage());
        }
    }
}
