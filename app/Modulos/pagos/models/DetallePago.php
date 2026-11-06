<?php

namespace App\Modulos\pagos\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Entidad DetallePago (tabla "detalle_pago"): cada parte de un pago con un método (efectivo o en línea).
 * Estados: Pendiente → Aprobado, Rechazado (pago en línea no aprobado) o Anulado.
 */
class DetallePago extends Model
{
    protected $table = 'detalle_pago';
    protected $primaryKey = 'id_detalle_pago';
    public $timestamps = false;

    protected $fillable = ['id_pago', 'id_metodo_pago', 'monto', 'referencia_externa', 'id_sesion_pasarela', 'estado'];

    protected $casts = ['monto' => 'decimal:2'];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pago', 'id_pago');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'id_metodo_pago', 'id_metodo_pago');
    }

    // DetallePago 1 — 0..1 MovimientoCaja (solo el efectivo genera movimiento)
    public function movimientoCaja(): HasOne
    {
        return $this->hasOne(MovimientoCaja::class, 'id_detalle_pago', 'id_detalle_pago');
    }

    /** ¿Es la parte en efectivo? (el método no es en línea) */
    public function esEfectivo(): bool
    {
        return ! $this->metodoPago->en_linea;
    }

    /** CU21 (pasos 3 y 4 insertarDetalle): nueva parte del pago, en estado "Pendiente". */
    public static function insertarDetalle(int $idPago, int $idMetodoPago, string $monto): self
    {
        return self::create([
            'id_pago' => $idPago,
            'id_metodo_pago' => $idMetodoPago,
            'monto' => $monto,
            'estado' => 'Pendiente',
        ]);
    }

    /** Detalle en línea "Pendiente" del pago (el que se está cobrando en Stripe), o null. */
    public static function enLineaPendiente(int $idPago): ?self
    {
        return self::query()
            ->where('id_pago', $idPago)
            ->where('estado', 'Pendiente')
            ->whereHas('metodoPago', fn ($m) => $m->where('en_linea', true))
            ->first();
    }

    /** CU21 (paso 7 aprobarDetalles): todos los detalles pendientes del pago pasan a "Aprobado". */
    public static function aprobarDetalles(int $idPago): void
    {
        self::query()->where('id_pago', $idPago)->where('estado', 'Pendiente')->update(['estado' => 'Aprobado']);
    }

    /** CU21 (5a / 6a): el pago en línea no fue aprobado. */
    public function marcarRechazado(): void
    {
        $this->update(['estado' => 'Rechazado']);
    }
}
