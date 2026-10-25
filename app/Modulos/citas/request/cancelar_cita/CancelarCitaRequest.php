<?php

namespace App\Modulos\citas\request\cancelar_cita;

use Illuminate\Foundation\Http\FormRequest;

/** CU15: valida el motivo obligatorio de la cancelación. */
class CancelarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_cancelacion' => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_cancelacion.required' => 'Ingrese el motivo de la cancelación.',
            'motivo_cancelacion.max' => 'El motivo no puede superar los 200 caracteres.',
        ];
    }
}
