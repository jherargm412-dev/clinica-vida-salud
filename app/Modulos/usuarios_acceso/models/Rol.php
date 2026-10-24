<?php

namespace App\Modulos\usuarios_acceso\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
