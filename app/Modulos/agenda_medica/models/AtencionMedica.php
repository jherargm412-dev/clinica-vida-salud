<?php

namespace App\Modulos\agenda_medica\models;

use App\Modulos\citas\models\Cita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Entidad AtencionMedica (tabla "atencion_medica"). */
class AtencionMedica extends Model
{
    protected $table = 'atencion_medica';

    protected $primaryKey = 'id_atencion';

    public $timestamps = false;

    protected $fillable = [
        'id_cita',
        'id_medico',
        'diagnostico',
        'notas_clinicas',
    ];

    protected $casts = [
        'fecha_hora_registro' => 'datetime',
    ];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    /** CU18: IDs de las citas que ya tienen atención clínica registrada. */
    public static function listarIdsPorCitas(array $idsCita): array
    {
        if ($idsCita === []) {
            return [];
        }

        return self::query()
            ->whereIn('id_cita', $idsCita)
            ->pluck('id_cita')
            ->all();
    }

    /** CU18: verifica si existe atención registrada para una cita. */
    public static function existePorIdCita(int $idCita): bool
    {
        return self::query()->where('id_cita', $idCita)->exists();
    }

    /** CU18: registra el diagnóstico y las notas clínicas de una cita. */
    public static function guardar(
        int $idCita,
        int $idMedico,
        string $diagnostico,
        ?string $notasClinicas
    ): self {
        return self::query()->create([
            'id_cita' => $idCita,
            'id_medico' => $idMedico,
            'diagnostico' => $diagnostico,
            'notas_clinicas' => $notasClinicas,
        ]);
    }
}
