<?php

namespace App\Modulos\usuarios_acceso\request\gestionar_usuarios;

use App\Modulos\compartido\Contrasena;
use App\Modulos\compartido\Texto;
use App\Modulos\usuarios_acceso\models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** CU01: validación del formulario de usuario (Registrar y Modificar). Detallar 6a. */
class UsuarioRequest extends FormRequest
{
    // El permiso usuarios.gestionar ya lo revisó el filtro de la ruta
    public function authorize(): bool
    {
        return true;
    }

    // ¿Es Modificar? En Modificar la URL trae {usuario}; en Registrar no.
    private function esModificar(): bool
    {
        return $this->route('usuario') !== null;
    }

    // Antes de validar: dejar los datos con un formato uniforme
    protected function prepareForValidation(): void
    {
        $this->merge([
            // "jUAN carlos" -> "Juan Carlos" · "PÉREZ DE LA CRUZ" -> "Pérez de la Cruz"
            'nombres'        => is_string($this->nombres) ? Texto::formatoTitulo($this->nombres) : $this->nombres,
            'apellidos'      => is_string($this->apellidos) ? Texto::formatoTitulo($this->apellidos) : $this->apellidos,
            // Usuario y correo siempre en minúscula y sin espacios
            'nombre_usuario' => is_string($this->nombre_usuario) ? mb_strtolower(trim($this->nombre_usuario)) : $this->nombre_usuario,
            'correo'         => is_string($this->correo) ? mb_strtolower(trim($this->correo)) : $this->correo,
        ]);
    }

    public function rules(): array
    {
        // Roles permitidos: activos y sin Paciente (+ el rol actual si se está editando)
        $idRolActual = $this->esModificar() ? $this->route('usuario')->id_rol : null;
        $rolesPermitidos = Rol::paraAsignar($idRolActual)->pluck('id_rol')->all();

        return [
            'nombres'        => ['required', 'string', 'max:60'],
            'apellidos'      => ['required', 'string', 'max:60'],
            'nombre_usuario' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9._-]+$/'],
            'correo'         => ['nullable', 'email', 'max:100'],
            // Registrar: obligatoria · Modificar: si se deja vacía se mantiene la actual
            // Política: mín. 8, mayúscula, minúscula, número y carácter especial (app/Modulos/compartido/Contrasena.php)
            'contrasena'     => Contrasena::reglas(! $this->esModificar()),
            'id_rol'         => ['required', 'integer', Rule::in($rolesPermitidos)],
        ];
    }

    public function messages(): array
    {
        return [
            'required'             => 'Complete los campos obligatorios',     // detallar 6a (para todos los campos)
            'max'                  => 'El dato ingresado es demasiado largo',
            'nombre_usuario.regex' => 'El nombre de usuario solo puede tener letras, números, punto, guion o guion bajo (sin espacios)',
            'correo.email'         => 'Ingrese un correo electrónico válido',
            'contrasena.min'       => Contrasena::MENSAJE,
            'contrasena.regex'     => Contrasena::MENSAJE,
            'contrasena.confirmed' => Contrasena::MENSAJE_CONFIRMAR,
            'id_rol.in'            => 'Seleccione un rol válido',
            'id_rol.integer'       => 'Seleccione un rol válido',
        ];
    }
}
