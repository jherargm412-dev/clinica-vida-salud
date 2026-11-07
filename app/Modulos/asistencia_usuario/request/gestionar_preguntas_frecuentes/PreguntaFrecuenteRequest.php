<?php

namespace App\Modulos\asistencia_usuario\request\gestionar_preguntas_frecuentes;

use App\Modulos\asistencia_usuario\models\PreguntaFrecuente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** CU23 Gestionar preguntas frecuentes: validación del formulario (Registrar y Modificar). */
class PreguntaFrecuenteRequest extends FormRequest
{
    // El permiso ya lo revisó el filtro de la ruta (permiso:faq.gestionar)
    public function authorize(): bool
    {
        return true;
    }

    // Antes de validar: quita espacios de más. La respuesta conserva sus saltos de línea.
    protected function prepareForValidation(): void
    {
        $limpiar = fn ($valor) => is_string($valor) ? trim(preg_replace('/[ \t]+/u', ' ', $valor)) : $valor;

        $this->merge([
            'pregunta' => $limpiar($this->pregunta),
            'respuesta' => is_string($this->respuesta) ? trim($this->respuesta) : $this->respuesta,
            'palabras_clave' => $limpiar($this->palabras_clave),
        ]);
    }

    public function rules(): array
    {
        return [
            'categoria' => ['required', 'string', Rule::in(PreguntaFrecuente::CATEGORIAS)],
            'pregunta' => ['required', 'string', 'max:200'],
            'respuesta' => ['required', 'string', 'max:1000'],
            'palabras_clave' => ['required', 'string', 'max:300'],
        ];
    }

    // Mensajes del detallar (flujos 6a y 6b)
    public function messages(): array
    {
        return [
            'categoria.required' => 'Complete los campos obligatorios',
            'pregunta.required' => 'Complete los campos obligatorios',
            'respuesta.required' => 'Complete los campos obligatorios',
            'palabras_clave.required' => 'Complete los campos obligatorios',
            'categoria.in' => 'Seleccione una categoría válida',
            'pregunta.max' => 'El campo pregunta no puede superar 200 caracteres',
            'respuesta.max' => 'El campo respuesta no puede superar 1000 caracteres',
            'palabras_clave.max' => 'El campo palabras clave no puede superar 300 caracteres',
        ];
    }

    // Flujo 6d: después de las reglas, revisa que quede al menos una palabra clave válida
    // (por ejemplo, ",,, " pasa "required" pero no tiene ninguna palabra).
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('palabras_clave')) {
                    return;
                }
                if (PreguntaFrecuente::normalizarPalabrasClave((string) $this->palabras_clave) === '') {
                    $validator->errors()->add('palabras_clave', 'Ingrese al menos una palabra clave');
                }
            },
        ];
    }
}
