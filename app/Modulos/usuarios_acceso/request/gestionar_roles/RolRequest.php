<?php

namespace App\Modulos\usuarios_acceso\request\gestionar_roles;

use App\Modulos\compartido\Texto;
use App\Modulos\usuarios_acceso\models\Permiso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** CU03: validación del formulario de rol (Registrar y Modificar). Detallar 6a y 6c (6b en el controller). */
class RolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // el permiso roles.gestionar ya lo revisó la ruta
    }

    protected function prepareForValidation(): void
    {
        $rol = $this->route('rol');   // en Modificar la URL trae {rol}

        // Los roles del sistema no se renombran: se ignora lo que llegue y se conserva su nombre
        if ($rol && $rol->esDelSistema()) {
            $this->merge(['nombre' => $rol->nombre]);
        } elseif (is_string($this->nombre)) {
            $this->merge(['nombre' => Texto::formatoTitulo($this->nombre)]);   // "jefe de CAJA" -> "Jefe de Caja"
        }
    }

    public function rules(): array
    {
        return [
            'nombre'      => ['required', 'string', 'max:30'],
            'descripcion' => ['nullable', 'string', 'max:150'],
            'permisos'    => ['required', 'array', 'min:1'],
            'permisos.*'  => ['integer', Rule::in(Permiso::pluck('id_permiso')->all())],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'   => 'Ingrese el nombre del rol',          // 6a
            'permisos.required' => 'Seleccione al menos un permiso',     // 6c
            'permisos.min'      => 'Seleccione al menos un permiso',
            'permisos.*.in'     => 'Seleccione permisos válidos',
            'max'               => 'El dato ingresado es demasiado largo',
        ];
    }
}
