<?php

namespace App\Modulos\usuarios_acceso\models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad SuscripcionPush (tabla "suscripcion_push").
 * RF30 (CU04 Configurar perfil): cada fila es un navegador o dispositivo donde el paciente
 * activó las notificaciones push. Los datos (endpoint y claves) los entrega el navegador
 * al suscribirse; con ellos el servidor le envía avisos cifrados aunque la página esté cerrada.
 */
class SuscripcionPush extends Model
{
    protected $table = 'suscripcion_push';
    protected $primaryKey = 'id_suscripcion';
    public $timestamps = false;

    protected $fillable = ['id_usuario', 'endpoint', 'clave_p256dh', 'clave_auth', 'dispositivo', 'activo', 'fecha_registro'];

    // Las claves no se envían nunca a las vistas
    protected $hidden = ['clave_p256dh', 'clave_auth'];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_registro' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Activa (o vuelve a activar) la suscripción de este navegador para el usuario.
     * El endpoint es único: si el mismo navegador ya estaba registrado (incluso con otra cuenta),
     * se actualiza en lugar de duplicarse.
     */
    public static function activar(int $idUsuario, string $endpoint, string $p256dh, string $auth, ?string $dispositivo): self
    {
        return self::updateOrCreate(
            ['endpoint' => $endpoint],
            [
                'id_usuario' => $idUsuario,
                'clave_p256dh' => $p256dh,
                'clave_auth' => $auth,
                'dispositivo' => $dispositivo,
                'activo' => true,
                'fecha_registro' => now(),
            ]
        );
    }

    /** Suscripciones activas del usuario (sus dispositivos con notificaciones activadas). */
    public static function activasDe(int $idUsuario): Collection
    {
        return self::where('id_usuario', $idUsuario)
            ->where('activo', true)
            ->orderByDesc('fecha_registro')
            ->get();
    }

    /** El paciente la desactivó, o el servicio push avisó que ya no existe. */
    public function desactivar(): void
    {
        $this->update(['activo' => false]);
    }

    /** RF31: el servicio push respondió que el navegador borró la suscripción (404/410). */
    public static function desactivarPorEndpoint(string $endpoint): void
    {
        self::where('endpoint', $endpoint)->update(['activo' => false]);
    }
}
