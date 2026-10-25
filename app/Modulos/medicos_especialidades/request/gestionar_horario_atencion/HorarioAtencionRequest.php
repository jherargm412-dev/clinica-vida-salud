<?php

namespace App\Modulos\medicos_especialidades\request\gestionar_horario_atencion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** CU11: validación del formulario de horario de atención. */
class HorarioAtencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'hora_inicio' => is_string($this->hora_inicio) ? trim($this->hora_inicio) : $this->hora_inicio,
            'hora_fin' => is_string($this->hora_fin) ? trim($this->hora_fin) : $this->hora_fin,
        ]);
    }

    public function rules(): array
    {
        return [
            'id_medico' => ['required', 'integer', 'exists:medico,id_medico'],
            'dia_semana' => ['required', 'integer', 'between:1,7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'duracion_cita' => ['required', 'integer', 'min:15', 'max:180'],
        ];
    }

    /** 8c: el rango de horas debe dividirse exactamente en citas de la duración indicada. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Si ya hay errores en las horas o la duración, no se revisa esta regla
            if ($validator->errors()->hasAny(['hora_inicio', 'hora_fin', 'duracion_cita'])) {
                return;
            }

            [$hi, $mi] = array_map('intval', explode(':', $this->hora_inicio));
            [$hf, $mf] = array_map('intval', explode(':', $this->hora_fin));
            $minutosRango = ($hf * 60 + $mf) - ($hi * 60 + $mi);
            $duracion = (int) $this->duracion_cita;

            if ($minutosRango % $duracion !== 0) {
                $validator->errors()->add(
                    'hora_fin',
                    "El rango de horas debe dividirse exactamente en citas de {$duracion} minutos"
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'required' => 'Complete los campos obligatorios',
            'id_medico.exists' => 'Seleccione un médico válido',
            'dia_semana.between' => 'Seleccione un día válido',
            'hora_inicio.date_format' => 'La hora de inicio no es válida',
            'hora_fin.date_format' => 'La hora de fin no es válida',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio',
            'duracion_cita.min' => 'La duración mínima de la cita es 15 minutos',
            'duracion_cita.max' => 'La duración máxima de la cita es 180 minutos',
            'duracion_cita.integer' => 'La duración de la cita debe ser un número entero',
        ];
    }
}
