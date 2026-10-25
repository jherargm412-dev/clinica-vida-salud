<?php

namespace App\Modulos\compartido;

/**
 * Política de contraseñas del sistema (una sola para todos los CU que crean o cambian contraseñas:
 * CU01 Gestionar usuarios, CU04 Configurar perfil, CU06 Registrar paciente).
 * Mínimo 8 caracteres, con al menos 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial. Ej.: Clave123!
 */
class Contrasena
{
    /** (?=.*[a-z]) minúscula · (?=.*[A-Z]) mayúscula · (?=.*\d) número · (?=.*[^A-Za-z0-9]) especial · .{8,} mínimo 8 */
    public const REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';

    public const MENSAJE = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial (ej.: Clave123!)';

    public const MENSAJE_CONFIRMAR = 'Las contraseñas no coinciden';

    /**
     * Reglas para el Request.
     * $obligatoria = false: puede venir vacía (se mantiene la actual).
     * $confirmar = true: exige el campo "<campo>_confirmation" con el mismo valor (Confirmar contraseña).
     */
    public static function reglas(bool $obligatoria = true, bool $confirmar = true): array
    {
        $reglas = [$obligatoria ? 'required' : 'nullable', 'string', 'min:8', 'max:100', 'regex:' . self::REGEX];

        if ($confirmar) {
            $reglas[] = 'confirmed';
        }

        return $reglas;
    }

        /**
     * CU06 (secuencia 3, paso 7): contraseña temporal de 8 caracteres al azar.
     * Cumple la política: al menos 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.
     * Se evitan letras y números parecidos (I, O, l, o, 0, 1) para que se lea bien al dictarla.
     */
    public static function generarTemporal(): string
    {
        $mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $minusculas = 'abcdefghijkmnpqrstuvwxyz';
        $numeros = '23456789';
        $especiales = '!@#$%*?';
        $todos = $mayusculas . $minusculas . $numeros . $especiales;

        // Un carácter de cada tipo (así siempre cumple la política)...
        $caracteres = [
            $mayusculas[random_int(0, strlen($mayusculas) - 1)],
            $minusculas[random_int(0, strlen($minusculas) - 1)],
            $numeros[random_int(0, strlen($numeros) - 1)],
            $especiales[random_int(0, strlen($especiales) - 1)],
        ];

        // ...y el resto al azar hasta completar 8
        while (count($caracteres) < 8) {
            $caracteres[] = $todos[random_int(0, strlen($todos) - 1)];
        }

        // Se mezclan para que los tipos no queden siempre en las mismas posiciones
        for ($i = count($caracteres) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$caracteres[$i], $caracteres[$j]] = [$caracteres[$j], $caracteres[$i]];
        }

        return implode('', $caracteres);
    }
}
