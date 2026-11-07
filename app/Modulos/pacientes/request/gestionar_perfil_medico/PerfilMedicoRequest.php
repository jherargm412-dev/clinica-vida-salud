<?php

namespace App\Modulos\pacientes\request\gestionar_perfil_medico;

use Illuminate\Foundation\Http\FormRequest;

/** CU07: validación del formulario del perfil médico. */
class PerfilMedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // El permiso perfil_medico.gestionar ya lo revisó la ruta.
    }

    protected function prepareForValidation(): void
    {
        $campos = [
            'tipo_sangre',
            'alergias',
            'enfermedades_cronicas',
            'medicamentos',
            'nombre_contacto_emergencia',
            'parentesco_contacto_emergencia',
            'telefono_contacto_emergencia',
        ];

        $datos = [];
        foreach ($campos as $campo) {
            if (is_string($this->input($campo))) {
                $valor = trim($this->input($campo));
                $datos[$campo] = $valor === '' ? null : $valor;
            }
        }

        $this->merge($datos);
    }

    public function rules(): array
    {
        return [
            'tipo_sangre' => ['nullable', 'string', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'alergias' => ['nullable', 'string', 'max:500'],
            'enfermedades_cronicas' => ['nullable', 'string', 'max:500'],
            'medicamentos' => ['nullable', 'string', 'max:500'],
            'nombre_contacto_emergencia' => ['nullable', 'string', 'max:500', 'required_with:parentesco_contacto_emergencia,telefono_contacto_emergencia'],
            'parentesco_contacto_emergencia' => ['nullable', 'string', 'max:500', 'required_with:nombre_contacto_emergencia,telefono_contacto_emergencia'],
            'telefono_contacto_emergencia' => ['nullable', 'string', 'max:20', 'required_with:nombre_contacto_emergencia,parentesco_contacto_emergencia', 'regex:/^[0-9+\-\s]{7,20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_sangre.in' => 'Seleccione un tipo de sangre válido',
            'alergias.max' => 'El texto no puede superar los 500 caracteres',
            'enfermedades_cronicas.max' => 'El texto no puede superar los 500 caracteres',
            'medicamentos.max' => 'El texto no puede superar los 500 caracteres',
            'nombre_contacto_emergencia.max' => 'El texto no puede superar los 500 caracteres',
            'parentesco_contacto_emergencia.max' => 'El texto no puede superar los 500 caracteres',
            'nombre_contacto_emergencia.required_with' => 'Complete el nombre, el parentesco y el teléfono del contacto de emergencia',
            'parentesco_contacto_emergencia.required_with' => 'Complete el nombre, el parentesco y el teléfono del contacto de emergencia',
            'telefono_contacto_emergencia.required_with' => 'Complete el nombre, el parentesco y el teléfono del contacto de emergencia',
            'telefono_contacto_emergencia.max' => 'Ingrese un teléfono válido',
            'telefono_contacto_emergencia.regex' => 'Ingrese un teléfono válido',
        ];
    }
}
