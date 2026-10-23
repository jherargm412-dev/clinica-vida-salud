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
}
