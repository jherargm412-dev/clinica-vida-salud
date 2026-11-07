<?php

namespace App\Modulos\pacientes\models;

use App\Modulos\citas\models\Cita;
use App\Modulos\usuarios_acceso\models\Usuario;
use App\Modulos\compartido\Texto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

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

    /** CU13 (secuencia 1): busca pacientes activos por CI, nombres o apellidos. */
    public static function buscarPorTexto(string $texto): Builder
    {
        $buscado = '%' . Texto::sinTildes(trim($texto)) . '%';

        return self::query()
            ->with('usuario')
            ->where('activo', true)
            ->whereHas('usuario', fn ($usuario) => $usuario
                ->where('activo', true))
            ->where(function ($consulta) use ($buscado) {
                $consulta->where('ci', 'ILIKE', $buscado)
                    ->orWhereHas('usuario', fn ($usuario) => $usuario->whereRaw(
                        Texto::sqlSinTildes("nombres || ' ' || apellidos") . ' ILIKE ?',
                        [$buscado]
                    ));
            })
            ->orderBy('codigo');
    }

    /** CU13 (secuencia 3): obtiene la ficha asociada a la sesión del paciente. */
    public static function obtenerPorUsuario(int $idUsuario): ?self
    {
        return self::obtenerPorIdUsuario($idUsuario);
    }

    /** CU07: obtiene el paciente asociado a una cuenta de usuario. */
    public static function obtenerPorIdUsuario(int $idUsuario): ?self
    {
        return self::query()
            ->with('usuario')
            ->where('id_usuario', $idUsuario)
            ->first();
    }

    /** CU08: busca pacientes por CI, incluyendo pacientes inactivos. */
    public static function buscarPorCI(string $texto): Builder
    {
        return self::query()
            ->with('usuario')
            ->whereRaw('UPPER(TRIM(ci)) LIKE ?', ['%' . mb_strtoupper(trim($texto)) . '%'])
            ->orderBy('codigo');
    }

    /** CU08: obtiene pacientes seleccionados por su identificador. */
    public static function listarPorIds(array $idsPaciente): \Illuminate\Database\Eloquent\Collection
    {
        if ($idsPaciente === []) {
            return self::query()->whereRaw('1 = 0')->get();
        }

        return self::query()
            ->with('usuario')
            ->whereIn('id_paciente', $idsPaciente)
            ->orderBy('codigo')
            ->get();
    }

    /** CU08: obtiene pacientes asociados a las cuentas encontradas por nombre o apellido. */
    public static function listarPorIdsUsuario(array $idsUsuario): \Illuminate\Database\Eloquent\Collection
    {
        if ($idsUsuario === []) {
            return self::query()->whereRaw('1 = 0')->get();
        }

        return self::query()
            ->with('usuario')
            ->whereIn('id_usuario', $idsUsuario)
            ->orderBy('codigo')
            ->get();
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

    /**
     * CU19 (4.3 obtenerPacientes): pacientes ordenados por apellidos y nombres.
     * $estado: 'activo' | 'inactivo' | null (todos). Las fechas filtran por la fecha de registro
     * de su cuenta (usuario.fecha_registro); cada una es opcional.
     */
    public static function obtenerPacientes(?string $estado = null, ?string $fechaIni = null, ?string $fechaFin = null): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->join('usuario', 'usuario.id_usuario', '=', 'paciente.id_usuario')
            ->select('paciente.*')
            ->with('usuario')
            ->when($estado, fn ($q) => $q->where('paciente.activo', $estado === 'activo'))
            ->when($fechaIni, fn ($q) => $q->whereDate('usuario.fecha_registro', '>=', $fechaIni))
            ->when($fechaFin, fn ($q) => $q->whereDate('usuario.fecha_registro', '<=', $fechaFin))
            ->orderBy('usuario.apellidos')
            ->orderBy('usuario.nombres')
            ->get();
    }
}
