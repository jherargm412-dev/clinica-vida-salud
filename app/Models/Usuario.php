<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Entidad Usuario (tabla "usuario").
 * Representa a cualquier persona que inicia sesión: administrador,
 * recepcionista, médico o paciente. Los datos personales (nombres,
 * apellidos y correo) se guardan solo aquí.
 */
class Usuario extends Authenticatable
{
    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;           // la tabla no tiene created_at / updated_at

    protected $fillable = [
        'id_rol', 'nombre_usuario', 'correo', 'contrasena',
        'nombres', 'apellidos', 'activo', 'bloqueado', 'intentos_fallidos',
    ];

    // Nunca se envía la contraseña a las vistas
    protected $hidden = ['contrasena'];

    protected $casts = [
        'activo' => 'boolean',
        'bloqueado' => 'boolean',
        'intentos_fallidos' => 'integer',
        'fecha_registro' => 'datetime',
    ];

    /** Permisos del rol, cargados una sola vez por petición. */
    private ?array $clavesPermiso = null;

    // ---------------------------------------------------------------
    // Ajustes para que Laravel use nuestra tabla en el inicio de sesión
    // ---------------------------------------------------------------

    /** La contraseña está en la columna "contrasena" (hash de password_hash). */
    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena';
    }

    /** No usamos "recordarme", así que no hay columna remember_token. */
    public function getRememberTokenName(): string
    {
        return '';
    }

    // ---------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function medico(): HasOne
    {
        return $this->hasOne(Medico::class, 'id_usuario', 'id_usuario');
    }

    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class, 'id_usuario', 'id_usuario');
    }

    // ---------------------------------------------------------------
    // Métodos de negocio
    // ---------------------------------------------------------------

    /** Busca un usuario por su nombre de usuario o por su correo. */
    public static function buscarPorCredencial(string $credencial): ?self
    {
        $credencial = trim($credencial);

        return self::where('nombre_usuario', $credencial)
            ->orWhereRaw('LOWER(correo) = ?', [mb_strtolower($credencial)])
            ->first();
    }

    /** Devuelve las claves de permiso del rol del usuario, p. ej. "usuarios.gestionar". */
    public function clavesPermiso(): array
    {
        if ($this->clavesPermiso === null) {
            $this->clavesPermiso = $this->rol && $this->rol->activo
                ? $this->rol->permisos()->pluck('clave')->all()
                : [];
        }

        return $this->clavesPermiso;
    }

    /** ¿El rol del usuario tiene este permiso? */
    public function tienePermiso(string $clave): bool
    {
        return in_array($clave, $this->clavesPermiso(), true);
    }

    /** Módulos del menú (config/modulos.php) que el usuario puede ver. */
    public function modulosPermitidos(): array
    {
        return array_values(array_filter(
            config('modulos', []),
            fn (array $modulo) => count(array_intersect($modulo['permisos'], $this->clavesPermiso())) > 0
        ));
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }
}
