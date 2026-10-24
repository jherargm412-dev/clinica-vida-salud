<?php
namespace App\Modulos\medicos_especialidades\request\gestionar_especialidad;
use App\Modulos\compartido\Texto;
use Illuminate\Foundation\Http\FormRequest;

class EspecialidadRequest extends FormRequest
{
    // ¿Quién puede enviar este formulario? true = todos,
    // porque el permiso ya lo revisó el filtro de la ruta.
    public function authorize(): bool
    {
        return true;
    }

    // Se ejecuta ANTES de validar: limpia el nombre para que todas las especialidades
    // tengan el mismo formato ("tipo título").   "  peDiaTRía  " -> "Pediatría"
    // La función está en app/Modulos/compartido/Texto.php (se reutiliza en usuarios y pacientes).
    protected function prepareForValidation(): void
    {
        if (is_string($this->nombre)) {
            $this->merge(['nombre' => Texto::formatoTitulo($this->nombre)]);
        }
    }

    // Las reglas de cada campo
    public function rules(): array
    {
        return [
            //Se lee así: “al campo nombre le corresponden estas reglas, y al campo descripcion estas otras”. 'clave' => [valores que le corresponden]
            'nombre'    => ['required', 'string', 'max:60'],
            'descripcion' => ['nullable', 'string', 'max:200'],
        ];
    }

    // Los mensajes que verá el usuario (del detallar)
    public function messages(): array
    {
        return [
            'nombre.required' => 'Ingrese el nombre de la especialidad',
            'nombre.max' => 'El nombre no puede superar 60 caracteres',
            'descripcion.max' => 'La descripción no puede superar 200 caracteres',
        ];
    }
}