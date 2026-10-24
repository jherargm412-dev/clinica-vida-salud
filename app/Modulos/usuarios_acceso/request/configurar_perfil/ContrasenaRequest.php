<?php

namespace App\Modulos\usuarios_acceso\request\configurar_perfil;

use App\Modulos\compartido\Contrasena;
use Illuminate\Foundation\Http\FormRequest;

/** CU04: validación de "Cambiar contraseña" (misma política que el CU01). */
class ContrasenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contrasena_actual' => ['required', 'string'],
            // nueva: mín. 8, mayúscula, minúscula, número, especial y confirmación (campo contrasena_confirmation)
            'contrasena'        => Contrasena::reglas(true, true),
        ];
    }

    public function messages(): array
    {
        return [
            'contrasena_actual.required' => 'Ingrese su contraseña actual',
            'contrasena.required'        => 'Ingrese la nueva contraseña',
            'contrasena.min'             => Contrasena::MENSAJE,
            'contrasena.regex'           => Contrasena::MENSAJE,
            'contrasena.confirmed'       => Contrasena::MENSAJE_CONFIRMAR,
            'max'                        => 'El dato ingresado es demasiado largo',
        ];
    }
}
