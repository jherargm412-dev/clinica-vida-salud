<?php

namespace App\Modulos\asistencia_usuario\service;

use App\Modulos\asistencia_usuario\models\PreguntaFrecuente;
use App\Modulos\compartido\Texto;
use App\Modulos\medicos_especialidades\models\Especialidad;
use Illuminate\Support\Collection;

/**
 * CU24 Consultar asistente virtual: las REGLAS del chatbot (no usa inteligencia artificial).
 *
 * responder() recibe lo que escribió el usuario y devuelve la respuesta del asistente:
 *   ['texto' => string, 'referencia' => ?string, 'sugerencias' => [['id' => int, 'pregunta' => string]], 'categorias' => bool]
 *   - referencia:  la pregunta frecuente que se usó (se muestra arriba de la respuesta)
 *   - sugerencias: preguntas que el usuario puede tocar (flujos 3a y 6a)
 *   - categorias:  true = mostrar los botones de categorías dentro de la respuesta
 *
 * Orden de búsqueda (detallar, paso 6):
 *   a) saludo o agradecimiento
 *   b) qué especialidades hay
 *   c) precio de la consulta de una especialidad
 *   d) la pregunta frecuente activa con más coincidencias
 */
class InterpreteAsistente
{
    /** Puntaje mínimo para dar una respuesta (equivale a una palabra clave que coincide). */
    public const PUNTOS_MINIMOS = 3;

    private const PUNTOS_FRASE_CLAVE = 4;      // palabra clave de varias palabras ("no puedo ir") encontrada completa
    private const PUNTOS_PALABRA_CLAVE = 3;    // palabra clave de una palabra ("cancelar")
    private const PUNTOS_PALABRA_PREGUNTA = 1; // palabra del texto de la pregunta frecuente

    /** Palabras comunes que no ayudan a reconocer la pregunta (paso 5). */
    private const COMUNES = [
        'a', 'al', 'algo', 'con', 'como', 'cual', 'cuales', 'cuando', 'de', 'debo', 'del', 'donde', 'el', 'en',
        'es', 'eso', 'esta', 'estan', 'este', 'esto', 'favor', 'hacer', 'hago', 'hay', 'la', 'las', 'le', 'les',
        'lo', 'los', 'me', 'mi', 'mis', 'necesito', 'no', 'o', 'para', 'pero', 'por', 'porfa', 'puede', 'pueden',
        'puedo', 'que', 'quiero', 'quisiera', 'saber', 'se', 'si', 'sin', 'sobre', 'son', 'su', 'sus', 'te',
        'tengo', 'tiene', 'tu', 'tus', 'un', 'una', 'uno', 'unos', 'unas', 'usted', 'ver', 'y', 'ya', 'yo',
    ];

    private const SALUDOS = ['hola', 'buenas', 'buenos', 'buen', 'dia', 'dias', 'tarde', 'tardes', 'noche', 'noches', 'saludos', 'hey', 'tal'];
    private const AGRADECIMIENTOS = ['gracias', 'muchas', 'agradezco', 'genial', 'perfecto', 'ok', 'okay', 'listo', 'vale', 'excelente', 'chau', 'adios', 'bueno'];

    /** Palabras con las que se pregunta un precio (regla c). */
    private const PALABRAS_PRECIO = ['precio', 'precios', 'cuesta', 'cuestan', 'costo', 'costos', 'cobran', 'cobra', 'tarifa', 'tarifas', 'valor'];

    /** Palabras con las que se pregunta por las especialidades (regla b). */
    private const PALABRAS_ESPECIALIDAD = ['especialidad', 'especialidades', 'especialista', 'especialistas'];

    /** Palabras que acompañan a una pregunta de precio sin nombrar una especialidad ("¿cuánto cuesta la consulta?"). */
    private const PALABRAS_DE_CONSULTA = ['consulta', 'consultas', 'cita', 'citas', 'medico', 'medicos', 'medica', 'doctor', 'doctora', 'cuanto', 'cuanta', 'sale', 'atencion'];

    // ------------------------------------------------------------------
    // Respuesta del asistente
    // ------------------------------------------------------------------

    public static function responder(string $mensaje): array
    {
        $texto = self::normalizar($mensaje);
        $todas = $texto === '' ? [] : explode(' ', $texto);   // todas las palabras escritas
        $palabras = self::sinComunes($todas);                 // solo las que sirven para buscar

        // a) Saludo o agradecimiento: el mensaje solo tiene palabras de saludo/agradecimiento
        $soloCortesia = array_diff($todas, self::SALUDOS, self::AGRADECIMIENTOS, self::COMUNES) === [];
        if ($todas !== [] && $soloCortesia) {
            return array_intersect($todas, self::AGRADECIMIENTOS) !== []
                ? self::respuesta('¡Con gusto! ¿Necesitas algo más?')
                : self::respuesta('¡Hola! Soy el asistente de la Clínica Vida Salud. Escribe tu pregunta o elige una categoría.', categorias: true);
        }

        $especialidades = Especialidad::listarParaRegistrarCita();   // activas, con tarifa vigente y médicos activos
        $pidePrecio = array_intersect($todas, self::PALABRAS_PRECIO) !== [];
        $especialidad = self::buscarEspecialidad($texto, $palabras, $especialidades);

        // c) Precio de una especialidad que se nombró
        if ($pidePrecio && $especialidad) {
            return self::respuesta('La consulta de ' . $especialidad->nombre . ' cuesta Bs '
                . number_format((float) $especialidad->monto_tarifa, 2) . '. Puede reservarla en "Registrar cita".');
        }

        // b) Qué especialidades hay (sin preguntar precio)
        if (! $pidePrecio && array_intersect($todas, self::PALABRAS_ESPECIALIDAD) !== []) {
            return $especialidades->isEmpty()
                ? self::respuesta('Por ahora no hay especialidades disponibles para registrar citas. Comuníquese con recepción.')
                : self::respuesta('La clínica atiende estas especialidades: ' . $especialidades->pluck('nombre')->implode(', ')
                    . '. Para saber el precio, pregunte por ejemplo "¿cuánto cuesta la consulta de ' . $especialidades->first()->nombre . '?".');
        }

        // d) Pregunta frecuente con más coincidencias
        $puntajes = self::puntuarPreguntas($texto, $palabras);
        $mejor = $puntajes->first();

        if ($mejor && $mejor['puntos'] >= self::PUNTOS_MINIMOS) {
            return self::respuesta($mejor['pregunta']->respuesta, referencia: $mejor['pregunta']->pregunta);
        }

        // Pregunta de precio que no nombró ninguna especialidad conocida (y no era otra pregunta frecuente)
        if ($pidePrecio) {
            $resto = array_diff($palabras, self::PALABRAS_PRECIO, self::PALABRAS_DE_CONSULTA, self::PALABRAS_ESPECIALIDAD);
            if ($especialidades->isEmpty()) {
                return self::respuesta('Por ahora no hay especialidades disponibles para registrar citas. Comuníquese con recepción.');
            }
            // "¿Cuánto cuesta la consulta?": lista de precios
            if ($resto === []) {
                $lista = $especialidades->map(fn ($e) => $e->nombre . ': Bs ' . number_format((float) $e->monto_tarifa, 2))->implode("\n");
                return self::respuesta("Estos son los precios de la consulta por especialidad:\n" . $lista);
            }
            // Flujo 6b: nombró una especialidad que no existe o no tiene tarifa vigente
            return self::respuesta('No encontré esa especialidad. Las especialidades disponibles son: '
                . $especialidades->pluck('nombre')->implode(', ') . '.');
        }

        // Flujo 6a: no se entendió; sugiere hasta 3 preguntas con alguna coincidencia
        $sugerencias = $puntajes->take(3)
            ->map(fn ($p) => ['id' => $p['pregunta']->id_pregunta_frecuente, 'pregunta' => $p['pregunta']->pregunta])
            ->values()->all();

        return $sugerencias !== []
            ? self::respuesta('No entendí tu pregunta. Quizás buscabas alguna de estas, o comunícate con recepción:', sugerencias: $sugerencias)
            : self::respuesta('No entendí tu pregunta. Elige una categoría o comunícate con recepción.', categorias: true);
    }

    /** Arma la respuesta con la forma que guarda la conversación. */
    public static function respuesta(string $texto, ?string $referencia = null, array $sugerencias = [], bool $categorias = false): array
    {
        return [
            'texto' => $texto,
            'referencia' => $referencia,
            'sugerencias' => $sugerencias,
            'categorias' => $categorias,
        ];
    }

    // ------------------------------------------------------------------
    // Paso 5: preparar el texto
    // ------------------------------------------------------------------

    /** Minúsculas, sin tildes, ñ como n y sin signos.  "¿Cuánto CUESTA la consulta?" -> "cuanto cuesta la consulta" */
    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(Texto::sinTildes($texto));
        $texto = strtr($texto, ['ñ' => 'n', 'Ñ' => 'n']);
        $texto = preg_replace('/[^a-z0-9]+/u', ' ', $texto);

        return trim(preg_replace('/\s+/', ' ', $texto));
    }

    /** Quita las palabras comunes. */
    private static function sinComunes(array $palabras): array
    {
        return array_values(array_diff($palabras, self::COMUNES));
    }

    /**
     * ¿Dos palabras son "la misma"? Iguales, o comparten el comienzo (al menos 4 letras) y a cada una
     * le sobran como máximo 3 letras: "cita"/"citas", "cancelo"/"cancelar", "olvide"/"olvido".
     * Así NO coinciden palabras distintas que empiezan igual: "estado"/"estacionamiento".
     */
    public static function coinciden(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }
        $menor = min(strlen($a), strlen($b));   // el texto ya está normalizado: solo letras a-z y números

        // Largo de la parte inicial que tienen en común
        $comun = 0;
        while ($comun < $menor && $a[$comun] === $b[$comun]) {
            $comun++;
        }

        return $comun >= 4 && strlen($a) - $comun <= 3 && strlen($b) - $comun <= 3;
    }

    // ------------------------------------------------------------------
    // Paso 6d: puntaje de cada pregunta frecuente
    // ------------------------------------------------------------------

    /** Preguntas activas con algún punto, de mayor a menor puntaje: [['pregunta' => PreguntaFrecuente, 'puntos' => int], ...] */
    private static function puntuarPreguntas(string $texto, array $palabras): Collection
    {
        return PreguntaFrecuente::listarActivas()
            ->map(fn (PreguntaFrecuente $p) => ['pregunta' => $p, 'puntos' => self::puntuar($texto, $palabras, $p)])
            ->filter(fn ($p) => $p['puntos'] > 0)
            ->sortByDesc('puntos')
            ->values();
    }

    /** Una palabra clave vale más que una palabra del texto de la pregunta. */
    private static function puntuar(string $texto, array $palabras, PreguntaFrecuente $pregunta): int
    {
        $puntos = 0;

        foreach ($pregunta->listaPalabrasClave() as $clave) {
            $clave = self::normalizar($clave);
            if ($clave === '') {
                continue;
            }
            if (str_contains($clave, ' ')) {
                // Palabra clave de varias palabras: debe aparecer completa ("no puedo ir")
                if (str_contains(" {$texto} ", " {$clave} ")) {
                    $puntos += self::PUNTOS_FRASE_CLAVE;
                }
            } elseif (collect($palabras)->contains(fn ($p) => self::coinciden($p, $clave))) {
                $puntos += self::PUNTOS_PALABRA_CLAVE;
            }
        }

        // Palabras del usuario (de 4 letras o más) que también están en el texto de la pregunta
        $dePregunta = self::sinComunes(explode(' ', self::normalizar($pregunta->pregunta)));
        foreach (array_unique($palabras) as $palabra) {
            if (mb_strlen($palabra) >= 4 && collect($dePregunta)->contains(fn ($q) => self::coinciden($palabra, $q))) {
                $puntos += self::PUNTOS_PALABRA_PREGUNTA;
            }
        }

        return $puntos;
    }

    // ------------------------------------------------------------------
    // Reglas b y c: especialidades
    // ------------------------------------------------------------------

    /** Especialidad nombrada en el texto: primero por su nombre completo, luego por una palabra de su nombre. */
    private static function buscarEspecialidad(string $texto, array $palabras, Collection $especialidades): ?Especialidad
    {
        foreach ($especialidades as $especialidad) {
            if (str_contains(" {$texto} ", ' ' . self::normalizar($especialidad->nombre) . ' ')) {
                return $especialidad;
            }
        }

        foreach ($especialidades as $especialidad) {
            $delNombre = array_filter(
                self::sinComunes(explode(' ', self::normalizar($especialidad->nombre))),
                fn ($p) => mb_strlen($p) >= 5
            );
            foreach ($delNombre as $parte) {
                if (collect($palabras)->contains(fn ($p) => self::coinciden($p, $parte))) {
                    return $especialidad;
                }
            }
        }

        return null;
    }
}
