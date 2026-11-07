<?php

namespace App\Modulos\usuarios_acceso\request\configurar_perfil;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RF30 (CU04): datos que entrega el navegador al activar las notificaciones push.
 * Los llena public/js/notificaciones.js en un formulario oculto (no es una API: es un POST normal).
 */
class SuscripcionPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Dirección del servicio push del navegador (siempre https)
            'endpoint' => ['required', 'string', 'url', 'starts_with:https://', 'max:2000'],
            // Claves del navegador en base64 (URL-safe)
            'p256dh' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\-=]+$/'],
            'auth' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\-=]+$/'],
            'dispositivo' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            '*' => 'El navegador no entregó datos válidos para activar las notificaciones. Intente nuevamente.',
        ];
    }
}
