<?php

namespace App\Modulos\pagos\request\gestionar_tarifas_metodos_pago;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** CU20: validación del formulario de tarifa (Registrar y Modificar). */
class TarifaRequest extends FormRequest
{
    // El permiso ya lo revisó el filtro de la ruta (permiso:pagos.configurar)
    public function authorize(): bool
    {
        return true;
    }

    // Antes de validar: acepta "150,50" y lo guarda como "150.50"
    protected function prepareForValidation(): void
    {
        if (is_string($this->monto)) {
            $this->merge(['monto' => str_replace(',', '.', trim($this->monto))]);
        }
    }

    // ¿Es Registrar? En Modificar la URL trae la tarifa ({tarifa}) y la especialidad no cambia.
    private function esNueva(): bool
    {
        return $this->route('tarifa') === null;
    }

    public function rules(): array
    {
        $reglas = [
            // 6a: vacío, no numérico o menor o igual a 0
            'monto' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99'],
        ];

        if ($this->esNueva()) {
            // Solo especialidades activas (paso 4)
            $reglas['id_especialidad'] = [
                'required', 'integer',
                Rule::exists('especialidad', 'id_especialidad')->where('activo', true),
            ];
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'Ingrese un monto mayor a 0',
            'monto.numeric' => 'Ingrese un monto mayor a 0',
            'monto.gt' => 'Ingrese un monto mayor a 0',
            'monto.decimal' => 'El monto puede tener como máximo 2 decimales',
            'monto.max' => 'El monto es demasiado alto',
            'id_especialidad.required' => 'Seleccione una especialidad',
            'id_especialidad.integer' => 'Seleccione una especialidad',
            'id_especialidad.exists' => 'Seleccione una especialidad activa',
        ];
    }
}
