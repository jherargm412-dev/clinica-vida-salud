<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Entidad Cita (tabla "cita"). */
class Cita extends Model
{
    protected $table = 'cita';
    protected $primaryKey = 'id_cita';
    public $timestamps = false;

    public const ESTADOS = ['Programada', 'Confirmada', 'Atendida', 'Cancelada', 'Inasistencia'];

    protected $fillable = [
        'id_paciente', 'id_medico', 'id_especialidad', 'id_usuario_cancelacion',
        'codigo', 'fecha', 'hora_inicio', 'hora_fin', 'motivo', 'estado',
        'motivo_cancelacion', 'fecha_cancelacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_creacion' => 'datetime',
        'fecha_cancelacion' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad');
    }

    public function usuarioCancelacion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_cancelacion', 'id_usuario');
    }
}
