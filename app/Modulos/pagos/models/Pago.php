<?php

namespace App\Modulos\pagos\models;

use App\Modulos\citas\models\Cita;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Entidad Pago (tabla "pago"): el cobro de una cita, por el monto total de la tarifa.
 * Estados: Pendiente → Pagado (con número de comprobante) o Anulado.
 * Guarda su propio monto_total: si la tarifa cambia después, el pago no cambia.
 */
class Pago extends Model
{
    protected $table = 'pago';
    protected $primaryKey = 'id_pago';
    public $timestamps = false;

    protected $fillable = ['id_cita', 'id_usuario', 'numero_comprobante', 'monto_total', 'fecha', 'estado'];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    // Composición: Pago 1 ◆— 1..* DetallePago
    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePago::class, 'id_pago', 'id_pago');
    }

    /**
     * CU22 (4.3 existePagoEfectivoEnCurso): ¿el usuario tiene un cobro sin terminar que incluye efectivo?
     * Es un pago "Pendiente" con un detalle de efectivo "Pendiente" (el dinero aún no entró a la caja).
     */
    public static function existePagoEfectivoEnCurso(int $idUsuario): bool
    {
        return self::query()
            ->where('id_usuario', $idUsuario)
            ->where('estado', 'Pendiente')
            ->whereHas('detalles', fn ($detalle) => $detalle
                ->where('estado', 'Pendiente')
                ->whereHas('metodoPago', fn ($metodo) => $metodo->where('en_linea', false)))
            ->exists();
    }

    // ---------------------------------------------------------------
    // CU21 Pagar cita
    // ---------------------------------------------------------------

    /** El pago de la cita (o null). $bloquear = true lo bloquea hasta el fin de la transacción. */
    public static function obtenerPorCita(int $idCita, bool $bloquear = false): ?self
    {
        return self::query()
            ->where('id_cita', $idCita)
            ->when($bloquear, fn ($q) => $q->lockForUpdate())
            ->first();
    }

    /** CU21 (paso 2 insertarPago): pago "Pendiente" por el monto total de la tarifa. */
    public static function insertarPago(int $idCita, int $idUsuario, string $montoTotal): self
    {
        return self::create([
            'id_cita' => $idCita,
            'id_usuario' => $idUsuario,
            'monto_total' => $montoTotal,
            'estado' => 'Pendiente',
        ]);
    }

    /**
     * CU21 (RF25 consultar y reimprimir el comprobante): pagos completados, del más reciente al más antiguo.
     * $idPaciente: solo los de ese paciente. $texto: busca por N° de comprobante, código de cita, CI o nombre del paciente.
     */
    public static function listarPagados(?int $idPaciente = null, ?string $texto = null): \Illuminate\Database\Eloquent\Builder
    {
        return self::query()
            ->with(['cita.paciente.usuario', 'cita.medico.usuario', 'cita.especialidad'])
            ->where('estado', 'Pagado')
            ->when($idPaciente, fn ($q) => $q->whereHas('cita', fn ($c) => $c->where('id_paciente', $idPaciente)))
            ->when($texto, function ($q) use ($texto) {
                $patron = '%' . $texto . '%';
                $q->where(fn ($w) => $w
                    ->where('numero_comprobante', 'ilike', $patron)
                    ->orWhereHas('cita', fn ($c) => $c
                        ->where('codigo', 'ilike', $patron)
                        ->orWhereHas('paciente', fn ($p) => $p
                            ->where('ci', 'ilike', $patron)
                            ->orWhereHas('usuario', fn ($u) => $u->whereRaw("(nombres || ' ' || apellidos) ILIKE ?", [$patron])))));
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id_pago');
    }

    /** Detalles que cuentan para el total: los que siguen "Pendiente" o ya están "Aprobado". */
    public function detallesVigentes()
    {
        return $this->detalles()->whereIn('estado', ['Pendiente', 'Aprobado'])->with('metodoPago')->orderBy('id_detalle_pago')->get();
    }

    /** CU21 (calcularSaldo): total − suma de los detalles vigentes, en centavos. */
    public function saldoCentavos(): int
    {
        $pagado = $this->detallesVigentes()->sum(fn ($d) => self::aCentavos($d->monto));

        return self::aCentavos($this->monto_total) - $pagado;
    }

    /**
     * CU21 (paso 7 generarNumeroComprobante): siguiente CP-0001, CP-0002, ...
     * pg_advisory_xact_lock hace que dos cobros simultáneos esperen su turno (hasta el fin de la transacción),
     * así nunca generan el mismo número.
     */
    public static function generarNumeroComprobante(): string
    {
        DB::select('SELECT pg_advisory_xact_lock(21021)');   // 21021: número fijo que identifica este bloqueo

        $ultimo = (int) DB::scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING(numero_comprobante FROM 4) AS INTEGER)), 0)
               FROM pago WHERE numero_comprobante IS NOT NULL"
        );

        return sprintf('CP-%04d', $ultimo + 1);
    }

    /** CU21 (paso 7 marcarPagado): el pago queda completo con su comprobante. */
    public function marcarPagado(string $numeroComprobante): void
    {
        $this->update([
            'estado' => 'Pagado',
            'numero_comprobante' => $numeroComprobante,
            'fecha' => now(),
        ]);
    }

    /** CU21 (7a y Cancelar): el pago y sus detalles pendientes quedan "Anulado". */
    public function anular(): void
    {
        $this->detalles()->where('estado', 'Pendiente')->update(['estado' => 'Anulado']);
        $this->update(['estado' => 'Anulado']);
    }

    /** Los montos se manejan en centavos (enteros) para evitar errores de redondeo. */
    public static function aCentavos(string|float|int $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }

    public static function deCentavos(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
