<?php

namespace App\Modulos\pacientes\models;

use App\Modulos\citas\models\Cita;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Entidad Paciente (tabla "paciente"). Nombres, apellidos y correo se toman de su Usuario. */
class Paciente extends Model
{
    protected $table = 'paciente';
    protected $primaryKey = 'id_paciente';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario', 'codigo', 'ci', 'fecha_nacimiento', 'sexo',
        'telefono', 'direccion', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_nacimiento' => 'date',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_paciente', 'id_paciente');
    }

    /** Nombre completo del paciente (se toma de su cuenta de usuario). Uso: $paciente->nombre_completo */
    public function getNombreCompletoAttribute(): string
    {
        return $this->usuario?->nombre_completo ?? '';
    }

    /** CU06 (secuencia 3, existeCI): ¿ya hay otro paciente con ese CI? */
    public static function existeCI(string $ci, ?int $excepto = null): bool
    {
        return self::query()
            ->when($excepto, fn ($q) => $q->where('id_paciente', '<>', $excepto))
            ->whereRaw('UPPER(TRIM(ci)) = ?', [mb_strtoupper(trim($ci))])
            ->exists();
    }

    /** CU06 (secuencia 3, generarCodigoPaciente): siguiente código PAC-0001, PAC-0002, ... */
    public static function generarCodigoPaciente(): string
    {
        $ultimo = (int) self::query()
            ->selectRaw('COALESCE(MAX(CAST(SUBSTRING(codigo FROM 5) AS INTEGER)), 0) AS n')
            ->value('n');

        return sprintf('PAC-%04d', $ultimo + 1);
    }

    /** CU06 (secuencias 4 a 6, obtenerPorId): obtiene una ficha con su cuenta asociada. */
    public static function obtenerPorId(int $idPaciente): ?self
    {
        return self::with('usuario')->find($idPaciente);
    }

    /** CU06 (secuencia 4, actualizarPaciente): actualiza los datos propios de la ficha. */
    public static function actualizarPaciente(int $idPaciente, array $datos): void
    {
        self::query()->whereKey($idPaciente)->update([
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'sexo' => $datos['sexo'],
            'telefono' => $datos['telefono'],
            'direccion' => $datos['direccion'] ?? null,
        ]);
    }

    /** CU06 (secuencias 5 y 6, cambiarEstadoPaciente): conserva la ficha y su historial. */
    public static function cambiarEstadoPaciente(int $idPaciente, bool $activo): void
    {
        self::query()->whereKey($idPaciente)->update(['activo' => $activo]);
    }
}
