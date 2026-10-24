<?php

namespace App\Modulos\citas\request\registrar_cita;

use Illuminate\Foundation\Http\FormRequest;

/** CU14: valida la fecha y el bloque de destino de una reprogramación. */
class ReprogramarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nueva_fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'nueva_hora_inicio' => ['required', 'date_format:H:i'],
            'nueva_hora_fin' => ['required', 'date_format:H:i', 'after:nueva_hora_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'nueva_fecha.after_or_equal' => 'No se pueden reprogramar citas en fechas pasadas.',
            'nueva_hora_inicio.required' => 'Seleccione un nuevo horario disponible.',
            'nueva_hora_fin.required' => 'Seleccione un nuevo horario disponible.',
        ];
    }
}
