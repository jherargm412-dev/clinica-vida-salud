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

    /** "REGISTRAR_ESPECIALIDAD" -> "Registrar especialidad". Uso: $registro->accion_legible */
    public function getAccionLegibleAttribute(): string
    {
        return self::legible($this->accion);
    }

    public static function legible(string $accion): string
    {
        return ucfirst(mb_strtolower(str_replace('_', ' ', $accion)));
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
