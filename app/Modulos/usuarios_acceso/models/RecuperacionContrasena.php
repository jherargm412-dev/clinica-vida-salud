<?php

namespace App\Modulos\usuarios_acceso\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Entidad RecuperacionContrasena (tabla "recuperacion_contrasena").
 * CU02 Iniciar Sesión, flujo alternativo "Recuperar contraseña".
 *
 * Cada fila es un enlace de un solo uso que se envía por correo.
 * Seguridad: en la base se guarda solo el HASH del token. El token real viaja
 * únicamente en el enlace del correo; así, aunque alguien lea la tabla, no puede usar los enlaces.
 */
class RecuperacionContrasena extends Model
{
    protected $table = 'recuperacion_contrasena';
    protected $primaryKey = 'id_recuperacion';
    public $timestamps = false;

    /** Minutos que dura el enlace desde que se solicita. */
    public const MINUTOS_VIGENCIA = 30;

    /** Si el usuario pidió un enlace hace menos de estos minutos, no se envía otro (evita llenar su correo). */
    public const MINUTOS_ESPERA = 1;

    protected $fillable = ['id_usuario', 'token', 'fecha_solicitud', 'fecha_expiracion', 'usado'];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_expiracion' => 'datetime',
        'usado' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /** El hash que se guarda en la base a partir del token real (SHA-256 = 64 caracteres). */
    private static function hashDe(string $token): string
    {
        return hash('sha256', $token);
    }

    /** ¿El usuario ya pidió un enlace hace muy poco? */
    public static function solicitadaRecientemente(Usuario $usuario): bool
    {
        return self::where('id_usuario', $usuario->id_usuario)
            ->where('fecha_solicitud', '>', now()->subMinutes(self::MINUTOS_ESPERA))
            ->exists();
    }

    /**
     * Crea un enlace nuevo para el usuario y devuelve el TOKEN REAL (el que va en el correo).
     * Los enlaces anteriores que no se usaron quedan anulados: solo sirve el último.
     */
    public static function generarPara(Usuario $usuario): string
    {
        self::anularPendientes($usuario);

        $token = Str::random(64);   // 64 caracteres al azar (generador seguro)

        // Las dos fechas se calculan en PHP para usar la misma zona horaria al comparar
        self::create([
            'id_usuario' => $usuario->id_usuario,
            'token' => self::hashDe($token),
            'fecha_solicitud' => now(),
            'fecha_expiracion' => now()->addMinutes(self::MINUTOS_VIGENCIA),
            'usado' => false,
        ]);

        return $token;
    }

    /**
     * Busca el enlace a partir del token del correo.
     * Solo lo devuelve si no se usó, no venció y la cuenta sigue activa; si no, devuelve null.
     */
    public static function buscarVigente(string $token): ?self
    {
        $recuperacion = self::with('usuario')
            ->where('token', self::hashDe($token))
            ->where('usado', false)
            ->where('fecha_expiracion', '>', now())
            ->first();

        if (! $recuperacion || ! $recuperacion->usuario || ! $recuperacion->usuario->activo) {
            return null;
        }

        return $recuperacion;
    }

    /** Marca como usados todos los enlaces pendientes del usuario. */
    public static function anularPendientes(Usuario $usuario): void
    {
        self::where('id_usuario', $usuario->id_usuario)
            ->where('usado', false)
            ->update(['usado' => true]);
    }
}
