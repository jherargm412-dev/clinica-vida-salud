<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Entidad Medico (tabla "medico"). Nombres, apellidos y correo se toman de su Usuario. */
class Medico extends Model
{
    protected $table = 'medico';
    protected $primaryKey = 'id_medico';
    public $timestamps = false;

    protected $fillable = ['id_usuario', 'codigo', 'registro_profesional', 'telefono', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    /** Especialidades del médico (tabla puente medico_especialidad). */
    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidad::class, 'medico_especialidad', 'id_medico', 'id_especialidad');
    }

    /** Composición: los horarios pertenecen al médico. */
    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioAtencion::class, 'id_medico', 'id_medico');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_medico', 'id_medico');
    }
}
