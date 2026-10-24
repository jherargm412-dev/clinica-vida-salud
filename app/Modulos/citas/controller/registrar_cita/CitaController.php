<?php

namespace App\Modulos\citas\controller\registrar_cita;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\citas\request\registrar_cita\RegistrarCitaRequest;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\HorarioAtencion;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\usuarios_acceso\models\Bitacora;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** CU13 Registrar Cita */
class CitaController extends Controller
{
    public function mostrarFormularioRegistro(Request $request): View
    {
        $datos = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'id_paciente' => ['nullable', 'integer', 'exists:paciente,id_paciente'],
            'id_especialidad' => ['nullable', 'integer', 'exists:especialidad,id_especialidad'],
            'id_medico' => ['nullable', 'integer', 'exists:medico,id_medico'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i'],
        ]);

        $usuario = $request->user();
        $esPaciente = $usuario->rol?->nombre === 'Paciente';
        $paciente = null;
        $mensajePaciente = null;
        $pacientes = null;

        if ($esPaciente) {
            $paciente = Paciente::obtenerPorUsuario($usuario->id_usuario);
            abort_unless($paciente && $paciente->activo && $paciente->usuario?->activo, 403, 'El paciente no está activo.');
        } else {
            $texto = trim($datos['buscar'] ?? '');
            if ($texto !== '') {
                $pacientes = Paciente::buscarPorTexto($texto)->paginate(10)->withQueryString();
            }

            if (! empty($datos['id_paciente'])) {
                $paciente = Paciente::obtenerPorId((int) $datos['id_paciente']);
                if (! $paciente || ! $paciente->activo || ! $paciente->usuario?->activo) {
                    $paciente = null;
                    $mensajePaciente = 'El paciente está inactivo y no puede recibir citas.';
                }
            }
        }

        $especialidad = ! empty($datos['id_especialidad'])
            ? Especialidad::obtenerPorId((int) $datos['id_especialidad'])
            : null;
        $medico = ! empty($datos['id_medico'])
            ? Medico::obtenerPorId((int) $datos['id_medico'])
            : null;
        $horarioSeleccionado = ! empty($datos['id_especialidad'])
            && ! empty($datos['id_medico'])
            && ! empty($datos['fecha'])
            && ! empty($datos['hora_inicio'])
            && ! empty($datos['hora_fin'])
                ? $datos
                : null;

        return view('citas.registrar_cita.formulario', [
            'esPaciente' => $esPaciente,
            'paciente' => $paciente,
            'pacientes' => $pacientes,
            'buscar' => $datos['buscar'] ?? '',
            'mensajePaciente' => $mensajePaciente,
            'especialidad' => $especialidad,
            'medico' => $medico,
            'horarioSeleccionado' => $horarioSeleccionado,
            'urlDisponibilidad' => route('disponibilidad.index', $paciente
                ? ['id_paciente' => $paciente->id_paciente]
                : []),
        ]);
    }

    public function registrarCita(RegistrarCitaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $usuario = $request->user();
        $esPaciente = $usuario->rol?->nombre === 'Paciente';

        if ($esPaciente) {
            $pacienteSesion = Paciente::obtenerPorUsuario($usuario->id_usuario);
            if (! $pacienteSesion || ! $pacienteSesion->activo || ! $pacienteSesion->usuario?->activo) {
                abort(403, 'El paciente no está activo.');
            }
            $idPaciente = $pacienteSesion->id_paciente;
        } else {
            if (empty($datos['id_paciente'])) {
                return back()->withErrors(['id_paciente' => 'Seleccione un paciente activo.'])->withInput();
            }
            $idPaciente = (int) $datos['id_paciente'];
        }

        $fecha = $datos['fecha'];
        $horaInicio = $datos['hora_inicio'].':00';
        $horaFin = $datos['hora_fin'].':00';
        $parametrosDisponibilidad = [
            'id_especialidad' => (int) $datos['id_especialidad'],
            'id_medico' => (int) $datos['id_medico'],
            'fecha' => $fecha,
            'id_paciente' => $idPaciente,
        ];

        $resultado = DB::transaction(function () use (
            $datos,
            $idPaciente,
            $fecha,
            $horaInicio,
            $horaFin
        ): array {
            $paciente = Paciente::query()
                ->whereKey($idPaciente)
                ->lockForUpdate()
                ->with('usuario')
                ->first();

            if (! $paciente || ! $paciente->activo || ! $paciente->usuario?->activo) {
                return ['error' => 'El paciente está inactivo y no puede recibir citas.', 'tipo' => 'paciente'];
            }

            $medico = Medico::query()
                ->whereKey($datos['id_medico'])
                ->lockForUpdate()
                ->with('usuario')
                ->first();
            $especialidad = Especialidad::query()
                ->whereKey($datos['id_especialidad'])
                ->lockForUpdate()
                ->first();

            if (
                ! $medico || ! $medico->activo || ! $medico->usuario?->activo
                || ! $especialidad || ! $especialidad->activo
                || ! $medico->tieneEspecialidad($especialidad->id_especialidad)
            ) {
                return ['error' => 'El médico seleccionado no está disponible.', 'tipo' => 'disponibilidad'];
            }

            $inicioCita = Carbon::parse($fecha.' '.$horaInicio);
            if ($inicioCita->lessThanOrEqualTo(now())) {
                return ['error' => 'El horario seleccionado no es válido.', 'tipo' => 'disponibilidad'];
            }

            $horario = HorarioAtencion::obtenerHorarioActivoParaBloque(
                $medico->id_medico,
                $fecha,
                $horaInicio,
                $horaFin
            );
            if (! $horario) {
                return ['error' => 'El horario seleccionado no es válido.', 'tipo' => 'disponibilidad'];
            }

            if (Cita::existeCitaSuperpuesta(
                $medico->id_medico,
                $fecha,
                $horaInicio,
                $horaFin
            )) {
                return ['error' => 'El horario seleccionado ya no está disponible.', 'tipo' => 'disponibilidad'];
            }

            if (Cita::existeCitaSuperpuestaPaciente(
                $paciente->id_paciente,
                $fecha,
                $horaInicio,
                $horaFin
            )) {
                return ['error' => 'El paciente ya tiene una cita en ese horario.', 'tipo' => 'disponibilidad'];
            }

            $cita = Cita::insertarCita([
                'id_paciente' => $paciente->id_paciente,
                'id_medico' => $medico->id_medico,
                'id_especialidad' => $especialidad->id_especialidad,
                'codigo' => Cita::generarCodigoCita(),
                'fecha' => $fecha,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'motivo' => $datos['motivo'] ?? null,
                'estado' => 'Programada',
            ]);

            Bitacora::registrar(
                'REGISTRAR_CITA',
                'cita',
                'Cita: '.$cita->codigo.' - Paciente: '.$paciente->codigo
            );

            return ['id_cita' => $cita->id_cita];
        });

        if (isset($resultado['error'])) {
            if ($resultado['tipo'] === 'disponibilidad') {
                return redirect()->route('disponibilidad.horarios', $parametrosDisponibilidad)
                    ->with('error', $resultado['error']);
            }

            return back()->withErrors(['id_paciente' => $resultado['error']])->withInput();
        }

        return redirect()->route('citas.confirmacion', $resultado['id_cita'])
            ->with('ok', 'Cita registrada');
    }

    public function mostrarConfirmacion(Cita $cita, Request $request): View
    {
        $cita->load(['paciente.usuario', 'medico.usuario', 'especialidad']);

        if ($request->user()->rol?->nombre === 'Paciente') {
            abort_unless(
                (int) $cita->id_paciente === (int) $request->user()->paciente?->id_paciente,
                403,
                'No puede consultar la cita de otro paciente.'
            );
        }

        return view('citas.registrar_cita.confirmacion', ['cita' => $cita]);
    }
}
