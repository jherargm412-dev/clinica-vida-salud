<?php

namespace App\Modulos\usuarios_acceso\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Entidad Rol (tabla "rol"): Administrador, Recepcionista, Medico, Paciente. */
class Rol extends Model
{
    protected $table = 'rol';
    protected $primaryKey = 'id_rol';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    /** Permisos asignados al rol (tabla puente rol_permiso). */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'id_rol', 'id_permiso');
    }

    /**
     * CU01 (secuencia "listar roles activos"): roles que se pueden asignar a una cuenta.
     * Se excluye Paciente: esas cuentas se crean en el CU06 junto con la ficha del paciente.
     * $incluir: id de un rol que debe aparecer igual (el rol actual del usuario que se edita).
     */
    public static function paraAsignar(?int $incluir = null)
    {
        return self::query()
            ->where(fn ($q) => $q->where('activo', true)->where('nombre', '<>', 'Paciente'))
            ->when($incluir, fn ($q) => $q->orWhere('id_rol', $incluir))
            ->orderBy('id_rol')
            ->get();
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_rol', 'id_rol');
    }

    // ---------------------------------------------------------------
    // CU03 Gestionar roles y permisos
    // ---------------------------------------------------------------

    /** Roles del sistema: el código los busca por nombre, por eso NO se pueden renombrar. */
    public const SISTEMA = ['Administrador', 'Recepcionista', 'Medico', 'Paciente'];

    public function esDelSistema(): bool
    {
        return in_array($this->getOriginal('nombre') ?? $this->nombre, self::SISTEMA, true);
    }

    public function esAdministrador(): bool
    {
        return ($this->getOriginal('nombre') ?? $this->nombre) === 'Administrador';
    }

    /** Secuencia "verificar nombre único": sin distinguir mayúsculas, tildes ni espacios. $excepto = id a ignorar. */
    public static function existeNombre(string $nombre, ?int $excepto = null): bool
    {
        $normalizar = fn (string $t) => Str::lower(Str::ascii(trim($t)));
        $buscado = $normalizar($nombre);

        return self::query()
            ->when($excepto, fn ($q) => $q->where('id_rol', '<>', $excepto))
            ->get()
            ->contains(fn ($r) => $normalizar($r->nombre) === $buscado);
    }

    /** Secuencia "contar usuarios activos por rol". */
    public function contarUsuariosActivos(): int
    {
        return $this->usuarios()->where('activo', true)->count();
    }

    /** CU06 (secuencia 3, obtenerPorNombre): busca un rol por su nombre. Ej.: Rol::obtenerPorNombre('Paciente') */
    public static function obtenerPorNombre(string $nombre): ?self
    {
        return self::where('nombre', $nombre)->first();
    }
}
