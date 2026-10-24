<?php

namespace App\Modulos\compartido;

/**
 * Utilidades de texto compartidas por todos los paquetes.
 * Se usa desde los Request (prepareForValidation) para guardar los datos con un formato uniforme.
 */
class Texto
{
    /** Palabras que quedan en minúscula cuando no son la primera: "Juan de la Cruz". */
    private const MENORES = ['de', 'del', 'y', 'e', 'la', 'las', 'el', 'los'];

    /**
     * Formato tipo título: primera letra de cada palabra en mayúscula, conserva tildes
     * y quita espacios de más.   "  peDiaTRía  " -> "Pediatría" · "MEDICINA DE FAMILIA" -> "Medicina de Familia"
     * Sirve para nombres de especialidades y para nombres/apellidos de personas.
     * NO usar en nombre de usuario, correo, CI ni contraseña.
     */
    public static function formatoTitulo(string $texto): string
    {
        $palabras = preg_split('/\s+/', trim($texto), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($palabras as $i => $palabra) {
            $palabra = mb_strtolower($palabra);
            $palabras[$i] = ($i > 0 && in_array($palabra, self::MENORES))
                ? $palabra
                : mb_convert_case($palabra, MB_CASE_TITLE);
        }

        return implode(' ', $palabras);
    }

    /** Vocales con tilde -> sin tilde (para buscar "perez" y encontrar "Pérez"). La ñ se conserva. */
    private const TILDES = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
                            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U'];

    /** Quita las tildes de un texto en PHP. */
    public static function sinTildes(string $texto): string
    {
        return strtr($texto, self::TILDES);
    }

    /** Lo mismo pero dentro de una consulta SQL (PostgreSQL), para una columna o expresión. */
    public static function sqlSinTildes(string $columna): string
    {
        return "translate({$columna}, 'áéíóúüÁÉÍÓÚÜ', 'aeiouuAEIOUU')";
    }
}
