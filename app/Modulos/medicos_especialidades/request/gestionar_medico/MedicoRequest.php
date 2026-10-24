<?php

namespace App\Modulos\medicos_especialidades\request\gestionar_medico;

use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** CU10: validación del formulario de médico (Registrar y Modificar). Detallar 6a, 6b y 6d (6c en el controller). */
class MedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // el permiso medicos.gestionar ya lo revisó la ruta
    }

    // Antes de validar: registro profesional en mayúsculas y sin espacios en los extremos
    protected function prepareForValidation(): void
    {
        $this->merge([
            'registro_profesional' => is_string($this->registro_profesional)
                ? mb_strtoupper(trim($this->registro_profesional)) : $this->registro_profesional,
            'telefono' => is_string($this->telefono) ? trim($this->telefono) : $this->telefono,
        ]);
    }

    public function rules(): array
    {
        // En Modificar la URL trae {medico}; sirve para incluir su cuenta y sus especialidades actuales
        $medico = $this->route('medico');

        $cuentasPermitidas = Usuario::medicosSinVincular($medico?->id_usuario)->pluck('id_usuario')->all();
        $especialidadesActuales = $medico ? $medico->especialidades()->pluck('especialidad.id_especialidad')->all() : [];
        $especialidadesPermitidas = Especialidad::paraAsignar($especialidadesActuales)->pluck('id_especialidad')->all();

        return [
            'id_usuario'           => ['required', 'integer', Rule::in($cuentasPermitidas)],
            'registro_profesional' => ['required', 'string', 'max:30'],
            'telefono'             => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{7,20}$/'],
            'especialidades'       => ['required', 'array', 'min:1'],
            'especialidades.*'     => ['integer', Rule::in($especialidadesPermitidas)],
        ];
    }

    public function messages(): array
    {
        return [
            'required'                => 'Complete los campos obligatorios',                    // 6a
            'especialidades.required' => 'Seleccione al menos una especialidad',                // 6b
            'especialidades.min'      => 'Seleccione al menos una especialidad',
            'especialidades.*.in'     => 'Seleccione especialidades válidas',
            'id_usuario.required'     => 'Seleccione la cuenta de usuario del médico',          // 6d
            'id_usuario.in'           => 'Seleccione la cuenta de usuario del médico',
            'id_usuario.integer'      => 'Seleccione la cuenta de usuario del médico',
            'telefono.regex'          => 'Ingrese un teléfono válido (solo números, espacios, + o -; mínimo 7)',
            'max'                     => 'El dato ingresado es demasiado largo',
        ];
    }
}
