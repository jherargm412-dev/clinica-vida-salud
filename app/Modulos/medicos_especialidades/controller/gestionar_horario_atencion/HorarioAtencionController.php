<?php

namespace App\Modulos\medicos_especialidades\controller\gestionar_horario_atencion;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\medicos_especialidades\models\HorarioAtencion;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\medicos_especialidades\request\gestionar_horario_atencion\HorarioAtencionRequest;
use App\Modulos\usuarios_acceso\models\Bitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU11 Gestionar Horario de Atención */
class HorarioAtencionController extends Controller
{
    public function index(Request $request): View
    {
        $idMedico = $request->integer('medico') ?: null;
        $dia = in_array($request->query('dia'), array_keys(HorarioAtencion::DIAS), true)
            ? (int) $request->query('dia')
            : null;
        $estado = in_array($request->query('estado'), ['activo', 'inactivo'], true)
            ? $request->query('estado')
            : null;

        $medicos = Medico::listarActivos();

        $horarios = HorarioAtencion::query()
            ->with('medico.usuario')
            ->when($idMedico, fn ($q) => $q->where('id_medico', $idMedico))
            ->when($dia, fn ($q) => $q->where('dia_semana', $dia))
            ->when($estado, fn ($q) => $q->where('activo', $estado === 'activo'))
            ->orderBy('id_medico')
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();

        return view('medicos_especialidades.gestionar_horario_atencion.index', [
            'medicos' => $medicos,
            'horarios' => $horarios,
            'idMedico' => $idMedico,
            'dia' => $dia,
            'estado' => $estado,
        ]);
    }

    public function listarMedicosActivos(): View
    {
        return $this->index(request());
    }

    public function listarHorariosMedico(Medico $medico, Request $request): View
    {
        $dia = in_array($request->query('dia'), array_keys(HorarioAtencion::DIAS), true)
            ? (int) $request->query('dia')
            : null;
        $estado = in_array($request->query('estado'), ['activo', 'inactivo'], true)
            ? $request->query('estado')
            : null;

        $horarios = HorarioAtencion::listarPorMedico($medico->id_medico, ['dia' => $dia, 'estado' => $estado]);

        return view('medicos_especialidades.gestionar_horario_atencion.index', [
            'medicos' => Medico::listarActivos(),
            'horarios' => $horarios,
            'idMedico' => $medico->id_medico,
            'dia' => $dia,
            'estado' => $estado,
        ]);
    }

    public function mostrarFormularioNuevo(): View
    {
        return view('medicos_especialidades.gestionar_horario_atencion.formulario', [
            'horario' => new HorarioAtencion(),
            'medicos' => Medico::listarActivos(),
            'dias' => HorarioAtencion::DIAS,
        ]);
    }

    public function registrarHorario(HorarioAtencionRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        if (HorarioAtencion::existeSuperposicion(
            $datos['id_medico'],
            $datos['dia_semana'],
            $datos['hora_inicio'],
            $datos['hora_fin']
        )) {
            return back()
                ->withErrors(['hora_inicio' => 'El horario se superpone con otro horario del mismo médico'])
                ->withInput();
        }

        $horario = HorarioAtencion::create($datos);

        Bitacora::registrar(
            'REGISTRAR_HORARIO',
            'horario_atencion',
            'Horario: ' . $horario->medico->nombre_completo . ' - ' . HorarioAtencion::DIAS[$horario->dia_semana]
        );

        return redirect()->route('horarios.index', ['medico' => $horario->id_medico])
            ->with('ok', 'Horario registrado');
    }

    public function editar(HorarioAtencion $horario): View
    {
        $horario->load('medico.usuario');

        return view('medicos_especialidades.gestionar_horario_atencion.formulario', [
            'horario' => $horario,
            'medicos' => Medico::listarActivos(),
            'dias' => HorarioAtencion::DIAS,
        ]);
    }

    public function modificarHorario(HorarioAtencionRequest $request, HorarioAtencion $horario): RedirectResponse
    {
        $datos = $request->validated();

        if (Cita::existenCitasPendientes(
            $horario->id_medico,
            $horario->dia_semana,
            $datos['hora_inicio'],
            $datos['hora_fin']
        )) {
            return back()
                ->withErrors(['hora_inicio' => 'No se puede modificar un horario con citas pendientes'])
                ->withInput();
        }

        if (HorarioAtencion::existeSuperposicion(
            $datos['id_medico'],
            $datos['dia_semana'],
            $datos['hora_inicio'],
            $datos['hora_fin'],
            $horario->id_horario
        )) {
            return back()
                ->withErrors(['hora_inicio' => 'El horario se superpone con otro horario del mismo médico'])
                ->withInput();
        }

        $horario->update($datos);

        Bitacora::registrar(
            'MODIFICAR_HORARIO',
            'horario_atencion',
            'Horario: ' . $horario->medico->nombre_completo . ' - ' . HorarioAtencion::DIAS[$horario->dia_semana]
        );

        return redirect()->route('horarios.index', ['medico' => $horario->id_medico])
            ->with('ok', 'Horario actualizado');
    }

    public function desactivarHorario(HorarioAtencion $horario): RedirectResponse
    {
        if (! $horario->activo) {
            return redirect()->route('horarios.index', ['medico' => $horario->id_medico])
                ->with('error', 'El horario ya está inactivo');
        }

        if (Cita::existenCitasPendientes(
            $horario->id_medico,
            $horario->dia_semana,
            $horario->hora_inicio,
            $horario->hora_fin
        )) {
            return redirect()->route('horarios.index', ['medico' => $horario->id_medico])
                ->with('error', 'No se puede desactivar un horario con citas pendientes');
        }

        $horario->update(['activo' => false]);

        Bitacora::registrar(
            'DESACTIVAR_HORARIO',
            'horario_atencion',
            'Horario: ' . $horario->medico->nombre_completo . ' - ' . HorarioAtencion::DIAS[$horario->dia_semana]
        );

        return redirect()->route('horarios.index', ['medico' => $horario->id_medico])
            ->with('ok', 'Horario desactivado');
    }

    public function activarHorario(HorarioAtencion $horario): RedirectResponse
    {
        if ($horario->activo) {
            return redirect()->route('horarios.index', ['medico' => $horario->id_medico])
                ->with('error', 'El horario ya está activo');
        }

        if (HorarioAtencion::existeSuperposicion(
            $horario->id_medico,
            $horario->dia_semana,
            $horario->hora_inicio,
            $horario->hora_fin,
            $horario->id_horario
        )) {
            return redirect()->route('horarios.index', ['medico' => $horario->id_medico])
                ->with('error', 'No se puede activar un horario que se superpone con otro');
        }

        $horario->update(['activo' => true]);

        Bitacora::registrar(
            'ACTIVAR_HORARIO',
            'horario_atencion',
            'Horario: ' . $horario->medico->nombre_completo . ' - ' . HorarioAtencion::DIAS[$horario->dia_semana]
        );

        return redirect()->route('horarios.index', ['medico' => $horario->id_medico])
            ->with('ok', 'Horario activado');
    }
}
