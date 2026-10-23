<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Entidad Especialidad (tabla "especialidad"). */
class Especialidad extends Model
{
    protected $table = 'especialidad';
    protected $primaryKey = 'id_especialidad';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    /** ¿Ya existe una especialidad con ese nombre? (sin distinguir mayúsculas ni espacios) */
    public static function existeNombre(string $nombre, ?int $excepto = null): bool
    {
        return self::whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(trim($nombre))])
            ->when($excepto, fn ($q) => $q->where('id_especialidad', '<>', $excepto))
            ->exists();
    }

    public function medicos(): BelongsToMany
    {
        return $this->belongsToMany(Medico::class, 'medico_especialidad', 'id_especialidad', 'id_medico');
    }
}
