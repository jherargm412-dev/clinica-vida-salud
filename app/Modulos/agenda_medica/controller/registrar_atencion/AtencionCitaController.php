<?php

namespace App\Modulos\agenda_medica\controller\registrar_atencion;

use App\Http\Controllers\Controller;
use App\Modulos\agenda_medica\models\AtencionMedica;
use App\Modulos\citas\models\Cita;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\pacientes\models\PerfilMedico;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** CU18 Registrar Atención de Cita. */
class AtencionCitaController extends Controller
{
    private const ESTADOS_ATENDIBLES = ['Programada', 'Confirmada', 'Atendida'];

    /** Lista las citas del médico autenticado que aún no tienen atención clínica. */
    public function listarCitasAtendibles(Request $request): View
    {
        $medico = $this->obtenerMedicoAutenticado($request->user());
        $consulta = Cita::listarPorMedicoYEstados($medico->id_medico, self::ESTADOS_ATENDIBLES);
        $idsConAtencion = AtencionMedica::listarIdsPorCitas(
            (clone $consulta)->pluck('id_cita')->all()
        );
        $citas = $consulta
            ->whereNotIn('id_cita', $idsConAtencion)
            ->with(['paciente.usuario', 'especialidad'])
            ->paginate(20)
            ->withQueryString();

        return view('agenda_medica.registrar_atencion.index', [
            'citas' => $citas,
            'medico' => $medico,
        ]);
    }

    /** Presenta los datos de una cita asignada al médico y el perfil del paciente. */
    public function obtenerDatosCita(Request $request, int $idCita): View|RedirectResponse
    {
        $medico = $this->obtenerMedicoAutenticado($request->user());
        $cita = Cita::obtenerDetallePorId($idCita);

        if ($cita === null || ! Cita::validarPuedeRegistrarAtencion($idCita, $medico->id_medico)) {
            return redirect()->route('atencion.index')
                ->with('error', 'La cita no permite registrar atención.');
        }

        if (AtencionMedica::existePorIdCita($idCita)) {
            return redirect()->route('atencion.index')
                ->with('error', 'La atención de esta cita ya fue registrada.');
        }

        $paciente = Paciente::obtenerPorId($cita->id_paciente);
        if ($paciente === null) {
            return redirect()->route('atencion.index')
                ->with('error', 'No se encontró el paciente de la cita.');
        }

        $usuarioPaciente = Usuario::obtenerPorId($paciente->id_usuario);
        $paciente->setRelation('usuario', $usuarioPaciente);
        $perfilMedico = PerfilMedico::obtenerPorIdPaciente($paciente->id_paciente);

        return view('agenda_medica.registrar_atencion.formulario', [
            'cita' => $cita,
            'medico' => $medico,
            'paciente' => $paciente,
            'perfilMedico' => $perfilMedico,
        ]);
    }

    /** Guarda la atención una sola vez y actualiza la cita dentro de una transacción. */
    public function guardarAtencion(Request $request, int $idCita): RedirectResponse
    {
        $datos = $request->validate([
            'diagnostico' => ['required', 'string', 'max:500'],
            'notas_clinicas' => ['nullable', 'string', 'max:2000'],
        ], [
            'diagnostico.required' => 'Ingrese el diagnóstico.',
            'diagnostico.max' => 'El diagnóstico no puede superar los 500 caracteres.',
            'notas_clinicas.max' => 'Las notas clínicas no pueden superar los 2000 caracteres.',
        ]);

        $usuario = $request->user();
        $medico = $this->obtenerMedicoAutenticado($usuario);
        $resultado = DB::transaction(function () use ($idCita, $medico, $usuario, $datos): string {
            $cita = Cita::query()->whereKey($idCita)->lockForUpdate()->first();

            if ($cita === null || ! Cita::validarPuedeRegistrarAtencion($idCita, $medico->id_medico)) {
                return 'no_elegible';
            }

            if (AtencionMedica::existePorIdCita($idCita)) {
                return 'existente';
            }

            if (
                in_array($cita->estado, ['Programada', 'Confirmada'], true)
                && ! Cita::marcarAtendida($idCita)
            ) {
                return 'no_elegible';
            }

            AtencionMedica::guardar(
                $idCita,
                $medico->id_medico,
                trim($datos['diagnostico']),
                isset($datos['notas_clinicas']) ? trim($datos['notas_clinicas']) : null
            );

            Bitacora::registrarAccion(
                (int) $usuario->id_usuario,
                'REGISTRAR_ATENCION',
                'AtencionMedica',
                'Atención registrada para la cita '.$cita->codigo
            );

            return 'registrada';
        });

        if ($resultado === 'no_elegible') {
            return redirect()->route('atencion.index')
                ->with('error', 'La cita no permite registrar atención.');
        }

        if ($resultado === 'existente') {
            return redirect()->route('atencion.index')
                ->with('error', 'La atención de esta cita ya fue registrada.');
        }

        return redirect()->route('atencion.index')
            ->with('ok', 'La atención fue registrada correctamente.');
    }

    private function obtenerMedicoAutenticado(Usuario $usuario): Medico
    {
        abort_unless($usuario->rol?->nombre === 'Medico', 403);

        $medico = Medico::obtenerPorIdUsuario((int) $usuario->id_usuario);
        abort_unless($medico && $medico->activo, 403);

        return $medico;
    }
}
