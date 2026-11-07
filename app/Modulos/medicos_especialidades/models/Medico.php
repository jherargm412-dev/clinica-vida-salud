<?php

namespace App\Modulos\medicos_especialidades\models;

use App\Modulos\citas\models\Cita;
use App\Modulos\usuarios_acceso\models\Usuario;
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

    /** CU17: médicos activos con cuenta activa que pueden consultarse en la agenda. */
    public static function listarParaAgenda(): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->where('activo', true)
            ->whereHas('usuario', fn ($consulta) => $consulta->where('activo', true))
            ->orderBy('codigo')
            ->get();
    }

    /** CU17: identifica el perfil médico asociado a una cuenta. */
    public static function obtenerPorIdUsuario(int $idUsuario): ?self
    {
        return self::query()
            ->where('id_usuario', $idUsuario)
            ->first();
    }

    /** CU13 (secuencias 5.3 y 5.5): médico por id y validación de especialidad asignada. */
    public static function obtenerPorId(int $idMedico): ?self
    {
        return self::with('usuario')->find($idMedico);
    }

    /** CU08: obtiene médicos asociados a las citas de un historial. */
    public static function listarPorIds(array $idsMedico): \Illuminate\Database\Eloquent\Collection
    {
        if ($idsMedico === []) {
            return self::query()->whereRaw('1 = 0')->get();
        }

        return self::query()->whereIn('id_medico', $idsMedico)->get();
    }

    public function tieneEspecialidad(int $idEspecialidad): bool
    {
        return $this->especialidades()
            ->where('especialidad.id_especialidad', $idEspecialidad)
            ->exists();
    }

    /** Nombre completo del médico (se toma de su cuenta de usuario). Uso: $medico->nombre_completo */
    public function getNombreCompletoAttribute(): string
    {
        return $this->usuario?->nombre_completo ?? '';
    }

    /** CU11 (secuencia 1): médicos activos para asignar o visualizar horarios. */
    public static function listarActivos()
    {
        return self::query()
            ->where('activo', true)
            ->with(['usuario', 'especialidades'])
            ->orderBy('codigo')
            ->get();
    }

    /**
     * CU19 (listarMedicos): TODOS los médicos (también los inactivos, porque los reportes son historial),
     * ordenados por apellidos y nombres.
     */
    public static function listarMedicos(): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->join('usuario', 'usuario.id_usuario', '=', 'medico.id_usuario')
            ->select('medico.*')
            ->with('usuario')
            ->orderBy('usuario.apellidos')
            ->orderBy('usuario.nombres')
            ->get();
    }

    /** CU12 (secuencia 2): médicos activos que atienden una especialidad activa. */
    public static function listarActivosPorEspecialidad(int $idEspecialidad): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->where('activo', true)
            ->whereHas('especialidades', fn ($q) => $q
                ->where('especialidad.id_especialidad', $idEspecialidad)
                ->where('especialidad.activo', true))
            ->with('usuario')
            ->orderBy('codigo')
            ->get();
    }

    /**
     * CU10 (secuencia 2.7 existeRegistro / 3.x existeRegistroEnOtro):
     * ¿Ya existe OTRO médico con ese número de registro profesional? (sin distinguir mayúsculas ni espacios)
     */
    public static function existeRegistro(string $registro, ?int $excepto = null): bool
    {
        return self::query()
            ->when($excepto, fn ($q) => $q->where('id_medico', '<>', $excepto))
            ->whereRaw('UPPER(TRIM(registro_profesional)) = ?', [mb_strtoupper(trim($registro))])
            ->exists();
    }

    /** CU10 (secuencia 2.8 generarCodigo): siguiente código MED-0001, MED-0002, ... */
    public static function generarCodigo(): string
    {
        $ultimo = (int) self::query()
            ->selectRaw('COALESCE(MAX(CAST(SUBSTRING(codigo FROM 5) AS INTEGER)), 0) AS n')
            ->value('n');

        return sprintf('MED-%04d', $ultimo + 1);
    }
}
