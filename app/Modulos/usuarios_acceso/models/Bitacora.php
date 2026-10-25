<?php

namespace App\Modulos\usuarios_acceso\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/** Entidad Bitacora (tabla "bitacora"). Solo se inserta; nunca se modifica ni se borra. */
class Bitacora extends Model
{
    protected $table = 'bitacora';
    protected $primaryKey = 'id_bitacora';
    public $timestamps = false;

    protected $fillable = ['id_usuario', 'accion', 'tabla_afectada', 'detalle', 'ip'];
    protected $casts = ['fecha_hora' => 'datetime'];

    /**
     * Registra una acción en la bitácora.
     * Si no se indica el usuario, se toma el que tiene la sesión iniciada.
     * La fecha y hora las pone PostgreSQL (DEFAULT CURRENT_TIMESTAMP).
     */
    public static function registrar(string $accion, ?string $tabla = null, ?string $detalle = null, ?int $idUsuario = null): void
    {
        self::create([
            'id_usuario' => $idUsuario ?? Auth::id(),
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'detalle' => $detalle,
            'ip' => request()->ip() ?? '0.0.0.0',
        ]);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    // ---------------------------------------------------------------
    // CU05 Consultar bitácora
    // ---------------------------------------------------------------

    /** Solo lectura: Laravel cancela cualquier intento de modificar o eliminar un registro. */
    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    /** Palabras que en el código de la acción se guardan sin tilde (ej. CAMBIAR_CONTRASENA). */
    private const PALABRAS_CON_TILDE = [
        'contrasena' => 'contraseña',
        'medico'     => 'médico',
        'medicos'    => 'médicos',
        'medica'     => 'médica',
        'sesion'     => 'sesión',
        'atencion'   => 'atención',
    ];

    /** Frases a las que les falta el "de" (ej. INICIO_SESION -> "Inicio de sesión"). */
    private const FRASES = [
        'inicio sesión'    => 'inicio de sesión',
        'cierre sesión'    => 'cierre de sesión',
        'bloqueo cuenta'   => 'bloqueo de cuenta',
        'horario atención' => 'horario de atención',
    ];

    /** "REGISTRAR_ESPECIALIDAD" -> "Registrar especialidad". Uso: $registro->accion_legible */
    public function getAccionLegibleAttribute(): string
    {
        return self::legible($this->accion);
    }

    /** Convierte el código de la acción en texto para mostrar. En la BD el código no cambia. */
    public static function legible(string $accion): string
    {
        $palabras = explode('_', mb_strtolower($accion));
        $palabras = array_map(fn ($p) => self::PALABRAS_CON_TILDE[$p] ?? $p, $palabras);
        $texto = strtr(implode(' ', $palabras), self::FRASES);

        return mb_strtoupper(mb_substr($texto, 0, 1)) . mb_substr($texto, 1);
    }

    /** Color de la etiqueta según el tipo de acción. Uso: $registro->color_accion */
    public function getColorAccionAttribute(): string
    {
        $a = $this->accion;
        return match (true) {
            str_contains($a, 'FALLIDO'), str_starts_with($a, 'BLOQUEO'),
            str_starts_with($a, 'DESACTIVAR'), str_starts_with($a, 'DESHABILITAR') => 'danger',
            str_starts_with($a, 'REGISTRAR')                                         => 'success',
            str_starts_with($a, 'MODIFICAR'), str_starts_with($a, 'ACTUALIZAR'),
            str_starts_with($a, 'CAMBIAR')                                           => 'primary',
            str_starts_with($a, 'ACTIVAR'), str_starts_with($a, 'REACTIVAR'),
            str_starts_with($a, 'HABILITAR'), str_starts_with($a, 'DESBLOQUEAR')     => 'info',
            default                                                                  => 'secondary',
        };
    }

    /** Lista del filtro "Acción": las acciones que existen en la bitácora. */
    public static function tiposDeAccion(): array
    {
        return self::query()->distinct()->orderBy('accion')->pluck('accion')->all();
    }

    /** Lista del filtro "Tabla afectada". */
    public static function tablasAfectadas(): array
    {
        return self::query()->whereNotNull('tabla_afectada')->distinct()->orderBy('tabla_afectada')->pluck('tabla_afectada')->all();
    }
}
