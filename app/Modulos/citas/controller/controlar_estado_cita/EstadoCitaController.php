<?php

namespace App\Modulos\citas\controller\controlar_estado_cita;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\usuarios_acceso\models\Bitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CU16 Controlar Estado y Asistencia de Cita (EstadoCitaController en los diagramas). */
class EstadoCitaController extends Controller
{
    /** Muestra las citas Programadas y Confirmadas. */
    public function listarCitasGestionables(): View
    {
        return $this->mostrarLista(Cita::listarGestionables(), '', '');
    }

    /** Busca citas gestionables por código, CI, nombre/apellido o fecha. */
    public function buscarCitasGestionables(Request $request): View
    {
        $filtros = $request->validate([
            'texto' => ['nullable', 'string', 'max:100'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $texto = trim($filtros['texto'] ?? '');
        $fecha = $filtros['fecha'] ?? '';

        return $this->mostrarLista(
            Cita::buscarGestionables($texto ?: null, $fecha ?: null),
            $texto,
            $fecha
        );
    }

    /** Selecciona una cita que todavía admite una transición de estado. */
    public function obtenerCitaGestionable(int $idCita): View|RedirectResponse
    {
        $cita = Cita::obtenerDetallePorId($idCita);
        if ($cita === null || ! in_array($cita->estado, ['Programada', 'Confirmada'], true)) {
            return redirect()->route('citas.estado.index')
                ->with('error', 'La cita no existe o no admite cambios de estado.');
        }

        return view('citas.controlar_estado_cita.detalle', ['cita' => $cita]);
    }

    /** Cambia el estado solo si la transición sigue siendo válida y registra la acción. */
    public function cambiarEstadoCita(Request $request, int $idCita): RedirectResponse
    {
        $datos = $request->validate([
            'nuevo_estado' => ['required', 'string', Rule::in(['Confirmada', 'Atendida', 'Inasistencia'])],
        ]);
        $nuevoEstado = $datos['nuevo_estado'];
        $idUsuario = (int) $request->user()->id_usuario;

        $resultado = DB::transaction(function () use ($idCita, $idUsuario, $nuevoEstado): string {
            $cita = Cita::query()->whereKey($idCita)->lockForUpdate()->first();
            if ($cita === null) {
                return 'no_encontrada';
            }

            if (! Cita::cambiarEstado($idCita, $nuevoEstado)) {
                return 'transicion_invalida';
            }

            $accion = match ($nuevoEstado) {
                'Confirmada' => 'CONFIRMAR_CITA',
                'Atendida' => 'REGISTRAR_ATENCION',
                'Inasistencia' => 'REGISTRAR_INASISTENCIA',
            };

            Bitacora::registrarAccion(
                $idUsuario,
                $accion,
                'Cita',
                'Cita: '.$cita->codigo.' - Estado: '.$nuevoEstado
            );

            return 'actualizada';
        });

        if ($resultado === 'no_encontrada') {
            return redirect()->route('citas.estado.index')->with('error', 'No se encontró la cita.');
        }

        if ($resultado === 'transicion_invalida') {
            return redirect()->route('citas.estado.index')
                ->with('error', 'La cita no puede cambiar a ese estado.');
        }

        return redirect()->route('citas.estado.index')
            ->with('ok', 'Estado de la cita actualizado.');
    }

    private function mostrarLista($consulta, string $texto, string $fecha): View
    {
        $citas = $consulta->paginate(15)->withQueryString();
        $citasPagina = $citas->getCollection();
        $pacientes = Paciente::listarPorIds($citasPagina->pluck('id_paciente')->unique()->all())->keyBy('id_paciente');
        $medicos = Medico::listarPorIds($citasPagina->pluck('id_medico')->unique()->all())
            ->load('usuario')
            ->keyBy('id_medico');

        foreach ($citasPagina as $cita) {
            $cita->setRelation('paciente', $pacientes->get($cita->id_paciente));
            $cita->setRelation('medico', $medicos->get($cita->id_medico));
        }

        $citas->setCollection($citasPagina);

        return view('citas.controlar_estado_cita.index', [
            'citas' => $citas,
            'texto' => $texto,
            'fecha' => $fecha,
        ]);
    }
}
