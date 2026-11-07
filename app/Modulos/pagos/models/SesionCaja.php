<?php

namespace App\Modulos\pagos\models;

use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Entidad SesionCaja (tabla "sesion_caja"): la caja de un recepcionista desde que la abre hasta que la cierra.
 * Un recepcionista tiene como máximo una caja "Abierta" (índice único parcial en la BD).
 */
class SesionCaja extends Model
{
    protected $table = 'sesion_caja';
    protected $primaryKey = 'id_sesion_caja';
    public $timestamps = false;

    protected $fillable = ['id_usuario', 'monto_inicial', 'cierre', 'monto_contado', 'diferencia', 'estado'];

    protected $casts = [
        'apertura' => 'datetime',
        'cierre' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'monto_contado' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    // Composición: SesionCaja 1 ◆— 0..* MovimientoCaja
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'id_sesion_caja', 'id_sesion_caja');
    }

    /**
     * CU22 (1.2 / 2.2) y CU21: la caja abierta del usuario, o null si no tiene.
     * $bloquear = true la bloquea hasta el fin de la transacción: así un cierre y un cobro
     * en efectivo no se cruzan (o el ingreso entra antes del cierre, o el cobro ve la caja cerrada).
     */
    public static function obtenerAbiertaPorUsuario(int $idUsuario, bool $bloquear = false): ?self
    {
        return self::query()
            ->where('id_usuario', $idUsuario)
            ->where('estado', 'Abierta')
            ->when($bloquear, fn ($q) => $q->lockForUpdate())
            ->first();
    }

    /** CU22 (2.3): abre la caja; la fecha y hora de apertura las pone PostgreSQL. */
    public static function insertarSesion(int $idUsuario, string $montoInicial): self
    {
        $sesion = self::create([
            'id_usuario' => $idUsuario,
            'monto_inicial' => $montoInicial,
            'estado' => 'Abierta',
        ]);

        return $sesion->refresh();   // para leer la "apertura" que puso la BD
    }

    /** CU22 (4.5): guarda el cierre; desde ahora la caja ya no recibe ingresos. */
    public function cerrarSesion(string $montoContado, string $diferencia): void
    {
        $this->update([
            'cierre' => now(),
            'monto_contado' => $montoContado,
            'diferencia' => $diferencia,
            'estado' => 'Cerrada',
        ]);
    }

    /**
     * CU19 (6.7 obtenerSesionesCerradas): cajas "Cerrada" abiertas entre dos fechas (inclusive),
     * de un usuario o de todos (null). Trae también la suma de sus ingresos en
     * "movimientos_sum_monto" (con ella se calcula el monto esperado, igual que en CU22).
     */
    public static function obtenerSesionesCerradas(string $fechaIni, string $fechaFin, ?int $idUsuario = null): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->with('usuario')
            ->withSum('movimientos', 'monto')
            ->where('estado', 'Cerrada')
            ->whereDate('apertura', '>=', $fechaIni)
            ->whereDate('apertura', '<=', $fechaFin)
            ->when($idUsuario, fn ($q) => $q->where('id_usuario', $idUsuario))
            ->orderBy('apertura')
            ->get();
    }

    /** CU19: monto esperado = monto inicial + ingresos en efectivo (en centavos, como CU22). */
    public function montoEsperadoCentavos(): int
    {
        return Pago::aCentavos($this->monto_inicial) + Pago::aCentavos($this->movimientos_sum_monto ?? 0);
    }
}
