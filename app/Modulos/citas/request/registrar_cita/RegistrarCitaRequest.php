<?php

namespace App\Modulos\citas\request\registrar_cita;

use Illuminate\Foundation\Http\FormRequest;

/** CU13: valida los datos enviados al registrar una cita. */
class RegistrarCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_paciente' => ['nullable', 'integer', 'exists:paciente,id_paciente'],
            'id_especialidad' => ['required', 'integer', 'exists:especialidad,id_especialidad'],
            'id_medico' => ['required', 'integer', 'exists:medico,id_medico'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'motivo' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.after_or_equal' => 'No se pueden registrar citas en fechas pasadas.',
            'hora_inicio.required' => 'Seleccione un horario disponible.',
            'hora_fin.required' => 'Seleccione un horario disponible.',
            'motivo.max' => 'El motivo no puede superar los 200 caracteres.',
        ];
    }
}
