<?php

namespace App\Modulos\pagos\models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Entidad MetodoPago (tabla "metodo_pago"): Efectivo y Pago en línea.
 * No se crean ni se eliminan desde el sistema; solo se habilitan o deshabilitan (CU20).
 * Siempre debe quedar al menos uno activo.
 */
class MetodoPago extends Model
{
    protected $table = 'metodo_pago';
    protected $primaryKey = 'id_metodo_pago';
    public $timestamps = false;

    protected $fillable = ['nombre', 'en_linea', 'activo'];

    protected $casts = [
        'en_linea' => 'boolean',
        'activo' => 'boolean',
    ];

    /** CU20 (1.3 listarMetodos): todos los métodos, Efectivo primero. */
    public static function listarMetodos(): Collection
    {
        return self::query()->orderBy('en_linea')->orderBy('nombre')->get();
    }

    /**
     * CU20 (5.2 contarActivos).
     * $bloquear = true bloquea los métodos hasta el fin de la transacción, para que dos
     * administradores no deshabiliten a la vez los dos últimos métodos activos.
     */
    public static function contarActivos(bool $bloquear = false): int
    {
        return self::query()
            ->when($bloquear, fn ($q) => $q->lockForUpdate())
            ->get()
            ->where('activo', true)
            ->count();
    }

    /** CU20 (5.3 actualizarEstado): habilita (true) o deshabilita (false). */
    public function actualizarEstado(bool $activo): void
    {
        $this->update(['activo' => $activo]);
    }

    /** CU21 (listarActivos): métodos que el recepcionista puede usar. */
    public static function listarActivos(): Collection
    {
        return self::query()->where('activo', true)->orderBy('en_linea')->orderBy('nombre')->get();
    }

    /** CU21 (listarActivosEnLinea): métodos que puede usar el paciente. */
    public static function listarActivosEnLinea(): Collection
    {
        return self::query()->where('activo', true)->where('en_linea', true)->orderBy('nombre')->get();
    }
}
