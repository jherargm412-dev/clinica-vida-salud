<?php

namespace App\Modulos\pagos\controller\gestionar_caja;

use App\Http\Controllers\Controller;
use App\Modulos\pagos\models\MovimientoCaja;
use App\Modulos\pagos\models\Pago;
use App\Modulos\pagos\models\SesionCaja;
use App\Modulos\pagos\service\ReservaPago;
use App\Modulos\usuarios_acceso\models\Bitacora;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CU22 Gestionar Caja (Ctrl_Caja en los diagramas).
 * Cada usuario trabaja SOLO con su propia caja (la del usuario con sesión iniciada).
 */
class CajaController extends Controller
{
    private const MSJ_MONTO_INICIAL = 'Ingrese el efectivo con el que abre la caja';
    private const MSJ_MONTO_CONTADO = 'Ingrese el total de efectivo que contó en la caja';

    // Secuencia "mostrarCaja" (1.1 obtenerCajaActual):
    // sin caja abierta → formulario de apertura (paso 2); con caja abierta → resumen (2a, paso 5)
    public function mostrarCaja(Request $request): View
    {
        $sesion = SesionCaja::obtenerAbiertaPorUsuario($request->user()->id_usuario);

        if (! $sesion) {
            return view('pagos.gestionar_caja.apertura');
        }

        return view('pagos.gestionar_caja.caja', $this->obtenerResumenCaja($sesion));
    }

    // Secuencia "abrirCaja" (2.1 registrarApertura), pasos 3 y 4
    public function abrirCaja(Request $request): RedirectResponse
    {
        $montoInicial = $this->validarMonto($request, 'monto_inicial', self::MSJ_MONTO_INICIAL);   // 4a
        $idUsuario = $request->user()->id_usuario;

        // 2.2: si ya tiene una caja abierta (p. ej. la abrió en otra pestaña), se muestra esa (2a)
        if (SesionCaja::obtenerAbiertaPorUsuario($idUsuario)) {
            return redirect()->route('caja.index');
        }

        try {
            $sesion = SesionCaja::insertarSesion($idUsuario, $montoInicial);
        } catch (UniqueConstraintViolationException) {
            // Dos clics a la vez: la BD no permite dos cajas abiertas del mismo usuario
            return redirect()->route('caja.index');
        }

        Bitacora::registrar('ABRIR_CAJA', 'sesion_caja',
            'Caja N° ' . $sesion->id_sesion_caja . ' - Monto inicial: Bs ' . $sesion->monto_inicial);

        return redirect()->route('caja.index')->with('ok', 'Caja abierta');
    }

    // Pasos 6 y 7: el recepcionista ingresa lo que contó; el sistema calcula la diferencia y pide confirmación.
    // Todavía no se guarda nada (8a: si no confirma, vuelve al paso 5 sin cambios).
    public function prepararCierre(Request $request): View|RedirectResponse
    {
        $montoContado = $this->validarMonto($request, 'monto_contado', self::MSJ_MONTO_CONTADO);   // 7a

        $sesion = SesionCaja::obtenerAbiertaPorUsuario($request->user()->id_usuario);
        if (! $sesion) {
            return redirect()->route('caja.index')->with('error', 'No tiene una caja abierta');
        }

        $resumen = $this->obtenerResumenCaja($sesion);

        return view('pagos.gestionar_caja.confirmar_cierre', [
            'sesion' => $sesion,
            'montoEsperado' => $resumen['montoEsperado'],
            'montoContado' => $montoContado,
            'diferencia' => $this->calcularDiferencia($montoContado, $resumen['montoEsperado']),
        ]);
    }

    // Secuencia "cerrarCaja" (4.2 registrarCierre), pasos 8 y 9
    public function cerrarCaja(Request $request): RedirectResponse
    {
        $montoContado = $this->validarMonto($request, 'monto_contado', self::MSJ_MONTO_CONTADO);
        $idUsuario = $request->user()->id_usuario;

        // Un cobro abandonado cuya reserva venció (CU21, 7a) ya no es un "pago en curso"
        ReservaPago::liberarVencidas();

        $resultado = DB::transaction(function () use ($idUsuario, $montoContado): array {
            // Se bloquea la caja: un cobro en efectivo (CU21) no puede meter un ingreso mientras se cierra
            $sesion = SesionCaja::obtenerAbiertaPorUsuario($idUsuario, bloquear: true);
            if (! $sesion) {
                return ['error' => 'No tiene una caja abierta'];
            }

            // 4.3 / 9a: hay un pago con efectivo sin terminar
            if (Pago::existePagoEfectivoEnCurso($idUsuario)) {
                return ['error' => 'Hay un pago en efectivo en curso; complételo o anúlelo antes de cerrar la caja'];
            }

            // 4.4: el monto esperado se recalcula aquí (pudo entrar un ingreso desde que se mostró la confirmación)
            $totalIngresos = MovimientoCaja::sumarPorSesion($sesion->id_sesion_caja);
            $montoEsperado = $this->calcularMontoEsperado($sesion->monto_inicial, $totalIngresos);
            $diferencia = $this->calcularDiferencia($montoContado, $montoEsperado);

            $sesion->cerrarSesion($montoContado, $diferencia);   // 4.5

            Bitacora::registrar('CERRAR_CAJA', 'sesion_caja',
                'Caja N° ' . $sesion->id_sesion_caja . ' - Esperado: Bs ' . $montoEsperado
                . ' - Contado: Bs ' . $montoContado . ' - Diferencia: Bs ' . $diferencia);

            return ['id_sesion_caja' => $sesion->id_sesion_caja];
        });

        if (isset($resultado['error'])) {
            return redirect()->route('caja.index')->with('error', $resultado['error']);
        }

        return redirect()->route('caja.cierre', $resultado['id_sesion_caja'])->with('ok', 'Caja cerrada');
    }

    // Paso 9 (mostrarResumenCierre): sobrante, faltante o sin diferencia
    public function mostrarResumenCierre(Request $request, SesionCaja $sesionCaja): View|RedirectResponse
    {
        // Solo su propia caja, y solo si ya está cerrada
        abort_unless((int) $sesionCaja->id_usuario === (int) $request->user()->id_usuario, 404);
        if ($sesionCaja->estado !== 'Cerrada') {
            return redirect()->route('caja.index');
        }

        return view('pagos.gestionar_caja.resumen_cierre', $this->obtenerResumenCaja($sesionCaja));
    }

    // ---------------------------------------------------------------
    // Operaciones internas (privadas en el diagrama de clases de análisis)
    // ---------------------------------------------------------------

    /** 3.1 obtenerResumenCaja: sesión, ingresos, total de ingresos y monto esperado. */
    private function obtenerResumenCaja(SesionCaja $sesion): array
    {
        $ingresos = MovimientoCaja::listarPorSesion($sesion->id_sesion_caja);
        $totalIngresos = MovimientoCaja::sumarPorSesion($sesion->id_sesion_caja);

        return [
            'sesion' => $sesion,
            'ingresos' => $ingresos,
            'totalIngresos' => $totalIngresos,
            'montoEsperado' => $this->calcularMontoEsperado($sesion->monto_inicial, $totalIngresos),   // 5a: sin ingresos = monto inicial
        ];
    }

    /**
     * validarMonto: obligatorio, número, no negativo y con hasta 2 decimales. Acepta "150,50".
     * Si no es válido, Laravel vuelve atrás con el mensaje del detallar (4a / 7a).
     */
    private function validarMonto(Request $request, string $campo, string $mensaje): string
    {
        if (is_string($request->input($campo))) {
            $request->merge([$campo => str_replace(',', '.', trim($request->input($campo)))]);
        }

        $datos = $request->validate(
            [$campo => ['required', 'numeric', 'gte:0', 'decimal:0,2', 'max:99999999.99']],
            [
                $campo . '.required' => $mensaje,
                $campo . '.numeric' => $mensaje,
                $campo . '.gte' => $mensaje,
                $campo . '.decimal' => 'El monto puede tener como máximo 2 decimales',
                $campo . '.max' => 'El monto es demasiado alto',
            ]
        );

        return $this->deCentavos($this->aCentavos($datos[$campo]));
    }

    /** calcularMontoEsperado = monto inicial + ingresos */
    private function calcularMontoEsperado(string $montoInicial, string $totalIngresos): string
    {
        return $this->deCentavos($this->aCentavos($montoInicial) + $this->aCentavos($totalIngresos));
    }

    /** calcularDiferencia = monto contado − monto esperado (positivo: sobrante; negativo: faltante) */
    private function calcularDiferencia(string $montoContado, string $montoEsperado): string
    {
        return $this->deCentavos($this->aCentavos($montoContado) - $this->aCentavos($montoEsperado));
    }

    // Las cuentas se hacen en centavos (números enteros) para evitar errores de redondeo con decimales
    private function aCentavos(string $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }

    private function deCentavos(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
