<?php

namespace App\Modulos\asistencia_usuario\models;

use App\Modulos\compartido\Texto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Entidad PreguntaFrecuente (tabla "pregunta_frecuente").
 * CU23 la gestiona el administrador; CU24 (asistente virtual) responde con las activas.
 */
class PreguntaFrecuente extends Model
{
    protected $table = 'pregunta_frecuente';
    protected $primaryKey = 'id_pregunta_frecuente';
    public $timestamps = false;
    protected $fillable = ['categoria', 'pregunta', 'respuesta', 'palabras_clave', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fecha_registro' => 'datetime'];

    /** Las 5 categorías fijas (la base de datos solo acepta estas: ck_pregunta_frecuente_categoria). */
    public const CATEGORIAS = ['Citas', 'Pagos', 'Notificaciones', 'Mi cuenta', 'La clínica'];

    /** Ícono de Bootstrap Icons de cada categoría (botones del chat y lista de CU23). */
    public const ICONOS = [
        'Citas' => 'bi-calendar-check',
        'Pagos' => 'bi-credit-card',
        'Notificaciones' => 'bi-bell',
        'Mi cuenta' => 'bi-person',
        'La clínica' => 'bi-hospital',
    ];

    /**
     * CU23 (Listar y Consultar): preguntas con los filtros de la pantalla.
     * $texto busca en la pregunta y en las palabras clave, sin distinguir mayúsculas ni tildes.
     */
    public static function listar(?string $texto, ?string $categoria, ?string $estado): Collection
    {
        return self::query()
            ->when($texto, function (Builder $q) use ($texto) {
                $buscado = '%' . Texto::sinTildes(trim($texto)) . '%';
                $q->where(fn ($w) => $w
                    ->whereRaw(Texto::sqlSinTildes('pregunta') . ' ILIKE ?', [$buscado])
                    ->orWhereRaw(Texto::sqlSinTildes('palabras_clave') . ' ILIKE ?', [$buscado]));
            })
            ->when($categoria, fn ($q) => $q->where('categoria', $categoria))
            ->when($estado, fn ($q) => $q->where('activo', $estado === 'activo'))
            ->orderBy('categoria')
            ->orderBy('pregunta')
            ->get();
    }

    /** CU24: todas las preguntas activas (las que el asistente puede usar). */
    public static function listarActivas(): Collection
    {
        return self::query()->where('activo', true)->orderBy('pregunta')->get();
    }

    /** CU24 (flujo 3a): preguntas activas de una categoría. */
    public static function listarActivasPorCategoria(string $categoria): Collection
    {
        return self::query()
            ->where('activo', true)
            ->where('categoria', $categoria)
            ->orderBy('pregunta')
            ->get();
    }

    /**
     * Texto de comparación de una pregunta: minúsculas, sin tildes, sin signos ¿?¡! y sin espacios de más.
     * "  ¿Cómo  saco una cita? " -> "como saco una cita"
     */
    public static function normalizarPregunta(string $pregunta): string
    {
        $texto = mb_strtolower(Texto::sinTildes(trim($pregunta)));
        $texto = preg_replace('/[¿?¡!.,;:]+/u', ' ', $texto);

        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    /**
     * CU23 (flujo 6c): ¿ya existe otra pregunta con el mismo texto (sin distinguir mayúsculas ni tildes)?
     * $excepto: id a ignorar (en Modificar, para no compararse consigo misma).
     */
    public static function existePregunta(string $pregunta, ?int $excepto = null): bool
    {
        $buscada = self::normalizarPregunta($pregunta);

        return self::query()
            ->when($excepto, fn ($q) => $q->where('id_pregunta_frecuente', '<>', $excepto))
            ->get(['pregunta'])
            ->contains(fn (self $p) => self::normalizarPregunta($p->pregunta) === $buscada);
    }

    /**
     * CU23 (paso 7): ordena las palabras clave: minúsculas, sin espacios de más y sin repetidas.
     * " Pagar,  PAGO , pagar,, " -> "pagar, pago".  Devuelve "" si no queda ninguna (flujo 6d).
     */
    public static function normalizarPalabrasClave(string $palabras): string
    {
        $lista = array_map(
            fn ($p) => trim(preg_replace('/\s+/u', ' ', mb_strtolower($p))),
            explode(',', $palabras)
        );
        // Solo cuentan las que tienen al menos una letra o un número
        $lista = array_filter($lista, fn ($p) => preg_match('/[\p{L}\p{N}]/u', $p));

        return implode(', ', array_values(array_unique($lista)));
    }

    /** Las palabras clave como lista: "pagar, pago" -> ['pagar', 'pago'] */
    public function listaPalabrasClave(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->palabras_clave))));
    }
}
