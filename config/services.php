<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Brevo: envío de correos por API (aviso "Cita programada", CU21)
    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

    // Notificaciones push del navegador (RF30/RF31, librería minishlink/web-push).
    // Claves VAPID: identifican a nuestro servidor ante Google, Mozilla y Apple. Se generan una sola vez
    // y deben ser las mismas en todos los .env y en Railway (si cambian, las suscripciones dejan de servir).
    'webpush' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:clinicavidasalud.oficial@gmail.com'),
    ],

    // Stripe (pasarela de pago en línea, CU21). Usar SOLO claves de prueba (sk_test_ / pk_test_).
    'stripe' => [
        'secret' => env('STRIPE_SECRET_KEY'),
        'public' => env('STRIPE_PUBLISHABLE_KEY'),   // la usa Stripe.js para mostrar el formulario incrustado (CU21)
        'moneda' => strtolower(env('STRIPE_CURRENCY', 'bob')),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
