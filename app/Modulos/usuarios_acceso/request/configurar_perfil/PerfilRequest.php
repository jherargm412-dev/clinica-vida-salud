<?php

namespace App\Modulos\usuarios_acceso\request\configurar_perfil;

use App\Modulos\compartido\Texto;
use Illuminate\Foundation\Http\FormRequest;

/** CU04: validación de "Editar datos personales" (detallar 4a; 4b en el controller). */
class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // cada usuario solo edita su propio perfil (el controller usa auth()->user())
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombres'   => is_string($this->nombres) ? Texto::formatoTitulo($this->nombres) : $this->nombres,
            'apellidos' => is_string($this->apellidos) ? Texto::formatoTitulo($this->apellidos) : $this->apellidos,
            'correo'    => is_string($this->correo) ? mb_strtolower(trim($this->correo)) : $this->correo,
        ]);
    }

    public function rules(): array
    {
        return [
            'nombres'   => ['required', 'string', 'max:60'],
            'apellidos' => ['required', 'string', 'max:60'],
            'correo'    => ['nullable', 'email', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'     => 'Complete los campos obligatorios',      // 4a
            'correo.email' => 'Ingrese un correo electrónico válido',
            'max'          => 'El dato ingresado es demasiado largo',
        ];
    }
}
