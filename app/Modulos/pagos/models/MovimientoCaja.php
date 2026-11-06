<?php

namespace App\Modulos\pagos\models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entidad MovimientoCaja (tabla "movimiento_caja"): efectivo que entró a una caja.
 * Solo se crea automáticamente cuando se completa un pago con efectivo (CU21, paso 8).
 */
class MovimientoCaja extends Model
{
    protected $table = 'movimiento_caja';
    protected $primaryKey = 'id_movimiento_caja';
    public $timestamps = false;

    protected $fillable = ['id_sesion_caja', 'id_detalle_pago', 'tipo', 'monto'];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    public function sesionCaja(): BelongsTo
    {
        return $this->belongsTo(SesionCaja::class, 'id_sesion_caja', 'id_sesion_caja');
    }

    public function detallePago(): BelongsTo
    {
        return $this->belongsTo(DetallePago::class, 'id_detalle_pago', 'id_detalle_pago');
    }

    /** CU22 (3.2 listarPorSesion): ingresos de la caja con su comprobante, del más antiguo al más reciente. */
    public static function listarPorSesion(int $idSesionCaja): Collection
    {
        return self::query()
            ->with('detallePago.pago.cita')
            ->where('id_sesion_caja', $idSesionCaja)
            ->orderBy('fecha')
            ->orderBy('id_movimiento_caja')
            ->get();
    }

    /** CU22 (4.4 sumarPorSesion): total de ingresos de la caja, p. ej. "350.00". */
    public static function sumarPorSesion(int $idSesionCaja): string
    {
        return number_format((float) self::query()->where('id_sesion_caja', $idSesionCaja)->sum('monto'), 2, '.', '');
    }

    /** CU21 (paso 8 insertarIngreso): el efectivo de un detalle aprobado entra a la caja abierta. */
    public static function insertarIngreso(int $idSesionCaja, int $idDetallePago, string $monto): self
    {
        return self::create([
            'id_sesion_caja' => $idSesionCaja,
            'id_detalle_pago' => $idDetallePago,
            'tipo' => 'Ingreso',
            'monto' => $monto,
        ]);
    }
}
