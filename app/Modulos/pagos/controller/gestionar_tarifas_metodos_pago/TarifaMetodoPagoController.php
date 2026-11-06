<?php

namespace App\Modulos\pagos\controller\gestionar_tarifas_metodos_pago;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\pagos\models\MetodoPago;
use App\Modulos\pagos\models\Tarifa;
use App\Modulos\pagos\request\gestionar_tarifas_metodos_pago\TarifaRequest;
use App\Modulos\pagos\service\ReservaPago;
use App\Modulos\usuarios_acceso\models\Bitacora;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** CU20 Gestionar Tarifas y Métodos de Pago (Ctrl_TarifasMetodosPago en los diagramas) */
class TarifaMetodoPagoController extends Controller
{
    private const MSJ_TODAS_CON_TARIFA = 'Todas las especialidades activas ya tienen una tarifa; modifique la existente';
    private const MSJ_YA_TIENE_TARIFA = 'La especialidad ya tiene una tarifa activa; modifíquela';

    // Comunicación 1 (obtenerTarifasYMetodos): las dos listas en una sola pantalla
    public function index(): View
    {
        return view('pagos.gestionar_tarifas_metodos_pago.index', [
            'tarifas' => Tarifa::listarTarifas(),
            'metodos' => MetodoPago::listarMetodos(),
        ]);
    }

    // Comunicación 2.1 a 2.3 (obtenerEspecialidadesSinTarifa): formulario "Nueva tarifa"
    public function crear(): View|RedirectResponse
    {
        $especialidades = Especialidad::listarActivasSinTarifa();

        // 4a: no queda ninguna especialidad sin tarifa
        if ($especialidades->isEmpty()) {
            return redirect()->route('tarifas.index')->with('error', self::MSJ_TODAS_CON_TARIFA);
        }

        return view('pagos.gestionar_tarifas_metodos_pago.formulario', [
            'tarifa' => new Tarifa(),
            'especialidades' => $especialidades,
        ]);
    }

    // Comunicación 2.5 a 2.9 (registrarTarifa). El monto ya lo validó TarifaRequest (6a).
    public function guardar(TarifaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $idEspecialidad = (int) $datos['id_especialidad'];

        // 6b: la especialidad ya tiene una tarifa activa
        if (Tarifa::existeActivaPorEspecialidad($idEspecialidad)) {
            return back()->withErrors(['id_especialidad' => self::MSJ_YA_TIENE_TARIFA])->withInput();
        }

        try {
            $tarifa = Tarifa::insertarTarifa($idEspecialidad, $datos['monto']);
        } catch (UniqueConstraintViolationException) {
            // Otro administrador registró la tarifa al mismo tiempo: la BD lo impide (índice único parcial)
            return back()->withErrors(['id_especialidad' => self::MSJ_YA_TIENE_TARIFA])->withInput();
        }

        $tarifa->load('especialidad');
        Bitacora::registrar('REGISTRAR_TARIFA', 'tarifa',
            'Especialidad: ' . $tarifa->especialidad->nombre . ' - Monto: Bs ' . $tarifa->monto);

        return redirect()->route('tarifas.index')->with('ok', 'Tarifa registrada');
    }

    // Modificar tarifa: formulario con el monto actual (Laravel busca la tarifa por el {tarifa} de la URL)
    public function editar(Tarifa $tarifa): View|RedirectResponse
    {
        if (! $tarifa->activo) {
            return redirect()->route('tarifas.index')->with('error', 'Solo se pueden modificar tarifas activas');
        }

        $tarifa->load('especialidad');

        return view('pagos.gestionar_tarifas_metodos_pago.formulario', [
            'tarifa' => $tarifa,
            'especialidades' => collect(),
        ]);
    }

    // Comunicación 3.1 a 3.4 (actualizarTarifa). Los pagos ya hechos guardan su propio monto, no cambian.
    public function actualizar(TarifaRequest $request, Tarifa $tarifa): RedirectResponse
    {
        if (! $tarifa->activo) {
            return redirect()->route('tarifas.index')->with('error', 'Solo se pueden modificar tarifas activas');
        }

        $montoAnterior = $tarifa->monto;
        $tarifa->actualizarMonto($request->validated()['monto']);
        $tarifa->refresh()->load('especialidad');

        Bitacora::registrar('MODIFICAR_TARIFA', 'tarifa',
            'Especialidad: ' . $tarifa->especialidad->nombre
            . ' - Monto anterior: Bs ' . $montoAnterior . ' - Monto nuevo: Bs ' . $tarifa->monto);

        return redirect()->route('tarifas.index')->with('ok', 'Tarifa actualizada');
    }

    // Comunicación 4.1 a 4.5 (desactivarTarifa). La confirmación la pide la vista (data-confirmar).
    public function desactivar(Tarifa $tarifa): RedirectResponse
    {
        // Las citas cuya reserva de 10 minutos venció ya no cuentan como "pendientes de pago" (CU21, 7a)
        ReservaPago::liberarVencidas();

        $resultado = DB::transaction(function () use ($tarifa): array {
            // Se bloquea la tarifa: CU13 también la bloquea al crear una cita "Pendiente de pago",
            // así no se cruza una cita nueva con la desactivación.
            $tarifa = Tarifa::query()->whereKey($tarifa->id_tarifa)->lockForUpdate()->first();

            if (! $tarifa || ! $tarifa->activo) {
                return ['error' => 'La tarifa ya está inactiva'];
            }

            // 4.2: no se desactiva mientras haya citas esperando su pago con esta tarifa
            if (Cita::existenPendientesDePago($tarifa->id_especialidad)) {
                return ['error' => 'Hay citas pendientes de pago de esta especialidad; intente en unos minutos'];
            }

            $tarifa->desactivar();
            $tarifa->load('especialidad');

            Bitacora::registrar('DESACTIVAR_TARIFA', 'tarifa',
                'Especialidad: ' . $tarifa->especialidad->nombre . ' - Monto: Bs ' . $tarifa->monto);

            return ['ok' => true];
        });

        if (isset($resultado['error'])) {
            return redirect()->route('tarifas.index')->with('error', $resultado['error']);
        }

        return redirect()->route('tarifas.index')
            ->with('ok', 'Tarifa desactivada. La especialidad no podrá recibir nuevas citas hasta que tenga una tarifa activa');
    }

    // Comunicación 5.1 a 5.5 (cambiarEstadoMetodo): habilitar o deshabilitar Efectivo / Pago en línea
    public function cambiarEstadoMetodo(Request $request, MetodoPago $metodoPago): RedirectResponse
    {
        $datos = $request->validate(['activo' => ['required', 'boolean']]);
        $activo = (bool) $datos['activo'];

        $resultado = DB::transaction(function () use ($metodoPago, $activo): array {
            // 5.2: se bloquean todos los métodos y se cuentan los activos
            $activos = MetodoPago::contarActivos(bloquear: true);
            $metodo = MetodoPago::query()->whereKey($metodoPago->id_metodo_pago)->first();

            // Si se intenta deshabilitar el único método activo
            if (! $activo && $metodo->activo && $activos <= 1) {
                return ['error' => 'Debe quedar al menos un método de pago activo'];
            }

            $metodo->actualizarEstado($activo);

            Bitacora::registrar('CAMBIAR_METODO_PAGO', 'metodo_pago',
                'Método: ' . $metodo->nombre . ' - Estado: ' . ($activo ? 'Habilitado' : 'Deshabilitado'));

            return ['ok' => true];
        });

        if (isset($resultado['error'])) {
            return redirect()->route('tarifas.index')->with('error', $resultado['error']);
        }

        return redirect()->route('tarifas.index')->with('ok', 'Método de pago actualizado');
    }
}
