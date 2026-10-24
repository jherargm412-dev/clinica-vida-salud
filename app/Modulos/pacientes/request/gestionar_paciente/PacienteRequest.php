<?php

namespace App\Modulos\pacientes\request\gestionar_paciente;

use App\Modulos\compartido\Texto;
use Illuminate\Foundation\Http\FormRequest;

/** CU06: validación del formulario de paciente (Registrar y Modificar). Detallar 6a, 6c y 6d (6b y 6e en el controller). */
class PacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // el permiso pacientes.gestionar ya lo revisó la ruta
    }

    // ¿Es Modificar? En Modificar la URL trae {paciente}; en Registrar no.
    private function esModificar(): bool
    {
        return $this->route('paciente') !== null;
    }

    // Antes de validar: dejar los datos con un formato uniforme
    protected function prepareForValidation(): void
    {
        $this->merge([
            'ci'        => is_string($this->ci) ? mb_strtoupper(trim($this->ci)) : $this->ci,
            'nombres'   => is_string($this->nombres) ? Texto::formatoTitulo($this->nombres) : $this->nombres,
            'apellidos' => is_string($this->apellidos) ? Texto::formatoTitulo($this->apellidos) : $this->apellidos,
            'telefono'  => is_string($this->telefono) ? trim($this->telefono) : $this->telefono,
            'correo'    => is_string($this->correo) ? mb_strtolower(trim($this->correo)) : $this->correo,
        ]);
    }

    public function rules(): array
    {
        return [
            // El CI solo se ingresa al registrar; en Modificar es de solo lectura y no se valida ni se guarda
            'ci'               => $this->esModificar() ? [] : ['required', 'string', 'max:15', 'regex:/^[A-Z0-9-]+$/'],
            'nombres'          => ['required', 'string', 'max:60'],
            'apellidos'        => ['required', 'string', 'max:60'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'sexo'             => ['required', 'in:M,F'],
            'telefono'         => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{7,20}$/'],
            'correo'           => ['nullable', 'string', 'max:100', 'email', 'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/'],
            'direccion'        => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'                         => 'Complete los campos obligatorios',                  // 6a
            'max'                              => 'El dato ingresado es demasiado largo',
            'ci.regex'                         => 'El CI solo puede tener letras, números y guion',
            'fecha_nacimiento.date'            => 'La fecha de nacimiento no es válida',               // 6c
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no es válida',
            'sexo.in'                          => 'Seleccione el sexo',
            'telefono.regex'                   => 'Ingrese un teléfono válido (solo números, espacios, + o -; mínimo 7)',
            'correo.email'                     => 'El correo electrónico no es válido',                // 6d
            'correo.regex'                     => 'El correo electrónico no es válido',
        ];
    }
}