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
}
