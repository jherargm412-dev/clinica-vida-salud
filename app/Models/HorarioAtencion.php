<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Entidad HorarioAtencion (tabla "horario_atencion"). dia_semana: 1 = lunes ... 7 = domingo. */
class HorarioAtencion extends Model
{
    protected $table = 'horario_atencion';
    protected $primaryKey = 'id_horario';
    public $timestamps = false;

    protected $fillable = [
        'id_medico', 'dia_semana', 'hora_inicio', 'hora_fin', 'duracion_cita', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'dia_semana' => 'integer',
        'duracion_cita' => 'integer',
    ];

    public const DIAS = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
    ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }
}
