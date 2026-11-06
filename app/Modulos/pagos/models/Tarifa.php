<?php

namespace App\Modulos\pagos\models;

use App\Modulos\medicos_especialidades\models\Especialidad;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad Tarifa (tabla "tarifa"): cuánto se cobra por la consulta de una especialidad.
 * Una sola tarifa activa por especialidad (índice único parcial en la BD).
 * Las tarifas no se eliminan: se desactivan y quedan como historial.
 */
class Tarifa extends Model
{
    protected $table = 'tarifa';
    protected $primaryKey = 'id_tarifa';
    public $timestamps = false;

    protected $fillable = ['id_especialidad', 'monto', 'vigente_desde', 'activo'];

    protected $casts = [
        'monto' => 'decimal:2',      // llega como texto "150.00" (exacto, sin errores de redondeo)
        'vigente_desde' => 'date',
        'activo' => 'boolean',
    ];

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad', 'id_especialidad');
    }

    /** CU20 (1.2 listarTarifas): primero las activas, luego por nombre de especialidad. */
    public static function listarTarifas(): Collection
    {
        return self::query()
            ->with('especialidad')
            ->join('especialidad', 'especialidad.id_especialidad', '=', 'tarifa.id_especialidad')
            ->select('tarifa.*')
            ->orderByDesc('tarifa.activo')
            ->orderBy('especialidad.nombre')
            ->orderByDesc('tarifa.vigente_desde')
            ->get();
    }

    /** CU20 (2.6): ¿la especialidad ya tiene una tarifa activa? */
    public static function existeActivaPorEspecialidad(int $idEspecialidad): bool
    {
        return self::query()
            ->where('id_especialidad', $idEspecialidad)
            ->where('activo', true)
            ->exists();
    }

    /** CU20 (2.7): registra la tarifa activa, vigente desde hoy. */
    public static function insertarTarifa(int $idEspecialidad, string $monto): self
    {
        return self::create([
            'id_especialidad' => $idEspecialidad,
            'monto' => $monto,
            'vigente_desde' => now()->toDateString(),
            'activo' => true,
        ]);
    }

    /** CU20 (3.2): cambia el monto y la vigencia pasa a ser desde hoy. */
    public function actualizarMonto(string $monto): void
    {
        $this->update([
            'monto' => $monto,
            'vigente_desde' => now()->toDateString(),
        ]);
    }

    /** CU20 (4.3): baja lógica; la tarifa queda como historial. */
    public function desactivar(): void
    {
        $this->update(['activo' => false]);
    }

    /**
     * CU21 (obtenerActivaPorEspecialidad) y CU13: la tarifa vigente de la especialidad.
     * $bloquear = true la bloquea hasta el fin de la transacción (para que nadie la desactive mientras tanto).
     */
    public static function obtenerActivaPorEspecialidad(int $idEspecialidad, bool $bloquear = false): ?self
    {
        return self::query()
            ->where('id_especialidad', $idEspecialidad)
            ->where('activo', true)
            ->when($bloquear, fn ($q) => $q->lockForUpdate())
            ->first();
    }
}
