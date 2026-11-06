<?php

namespace App\Modulos\pacientes\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Entidad PerfilMedico (tabla "perfil_medico"). */
class PerfilMedico extends Model
{
    protected $table = 'perfil_medico';

    protected $primaryKey = 'id_perfil_medico';

    public $timestamps = false;

    protected $fillable = [
        'tipo_sangre',
        'alergias',
        'enfermedades_cronicas',
        'medicamentos',
        'nombre_contacto_emergencia',
        'parentesco_contacto_emergencia',
        'telefono_contacto_emergencia',
        'fecha_actualizacion',
    ];

    protected $casts = [
        'fecha_actualizacion' => 'datetime',
    ];

    private const TIPOS_SANGRE = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    private const CAMPOS_TEXTO = [
        'alergias',
        'enfermedades_cronicas',
        'medicamentos',
        'nombre_contacto_emergencia',
        'parentesco_contacto_emergencia',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    /** CU07: obtiene el perfil médico del paciente, si ya fue registrado. */
    public static function obtenerPorIdPaciente(int $idPaciente): ?self
    {
        return self::query()->where('id_paciente', $idPaciente)->first();
    }

    /** CU07: valida las reglas propias del perfil médico. */
    public static function validarDatos(array $datos): bool
    {
        $tipoSangre = $datos['tipo_sangre'] ?? null;
        if ($tipoSangre !== null && (! is_string($tipoSangre) || ! in_array($tipoSangre, self::TIPOS_SANGRE, true))) {
            return false;
        }

        foreach (self::CAMPOS_TEXTO as $campo) {
            if (
                isset($datos[$campo])
                && (! is_string($datos[$campo]) || mb_strlen($datos[$campo]) > 500)
            ) {
                return false;
            }
        }

        $telefono = $datos['telefono_contacto_emergencia'] ?? null;
        if ($telefono !== null && ! is_string($telefono)) {
            return false;
        }

        $contacto = array_map(
            static fn ($campo) => trim((string) ($datos[$campo] ?? '')),
            ['nombre_contacto_emergencia', 'parentesco_contacto_emergencia', 'telefono_contacto_emergencia']
        );
        $camposContactoCompletos = count(array_filter($contacto, static fn ($valor) => $valor !== ''));

        if ($camposContactoCompletos !== 0 && $camposContactoCompletos !== 3) {
            return false;
        }

        return $telefono === null
            || (mb_strlen($telefono) <= 20 && preg_match('/^[0-9+\-\s]{7,20}$/', $telefono) === 1);
    }

    /** CU07: crea el perfil por primera vez o actualiza el existente. */
    public static function guardarOActualizar(int $idPaciente, array $datos): self
    {
        $perfil = self::query()->firstOrNew(['id_paciente' => $idPaciente]);
        $perfil->id_paciente = $idPaciente;
        $perfil->fill([
            'tipo_sangre' => $datos['tipo_sangre'] ?? null,
            'alergias' => $datos['alergias'] ?? null,
            'enfermedades_cronicas' => $datos['enfermedades_cronicas'] ?? null,
            'medicamentos' => $datos['medicamentos'] ?? null,
            'nombre_contacto_emergencia' => $datos['nombre_contacto_emergencia'] ?? null,
            'parentesco_contacto_emergencia' => $datos['parentesco_contacto_emergencia'] ?? null,
            'telefono_contacto_emergencia' => $datos['telefono_contacto_emergencia'] ?? null,
            'fecha_actualizacion' => now(),
        ]);
        $perfil->save();

        return $perfil;
    }
}
