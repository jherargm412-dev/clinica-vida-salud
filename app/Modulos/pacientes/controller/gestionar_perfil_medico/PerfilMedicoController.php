<?php

namespace App\Modulos\pacientes\controller\gestionar_perfil_medico;

use App\Http\Controllers\Controller;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\pacientes\models\PerfilMedico;
use App\Modulos\pacientes\request\gestionar_perfil_medico\PerfilMedicoRequest;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** CU07 Gestionar Perfil Médico (PerfilMedicoController en los diagramas). */
class PerfilMedicoController extends Controller
{
    private const ERROR_PACIENTE_NO_ASOCIADO = 'Su cuenta no está asociada a un paciente, contacte a la clínica';

    /** Secuencia 1: carga únicamente el perfil del paciente de la sesión. */
    public function mostrarPerfilMedico(): View
    {
        $usuario = Usuario::obtenerPorId((int) Auth::id());
        $paciente = $usuario ? Paciente::obtenerPorIdUsuario($usuario->id_usuario) : null;

        if (! $paciente) {
            return view('pacientes.gestionar_perfil_medico.index', [
                'paciente' => null,
                'perfil' => null,
                'error' => self::ERROR_PACIENTE_NO_ASOCIADO,
            ]);
        }

        $perfil = PerfilMedico::obtenerPorIdPaciente($paciente->id_paciente) ?? new PerfilMedico;

        return view('pacientes.gestionar_perfil_medico.index', [
            'paciente' => $paciente,
            'perfil' => $perfil,
            'error' => null,
        ]);
    }

    /** Secuencia 2: valida, guarda y audita la edición del perfil propio. */
    public function actualizarPerfilMedico(PerfilMedicoRequest $request): RedirectResponse
    {
        $usuario = Usuario::obtenerPorId((int) Auth::id());
        $paciente = $usuario ? Paciente::obtenerPorIdUsuario($usuario->id_usuario) : null;

        if (! $paciente) {
            return redirect()->route('perfil_medico.mostrar')
                ->with('error', self::ERROR_PACIENTE_NO_ASOCIADO);
        }

        $datos = $request->validated();
        if (! PerfilMedico::validarDatos($datos)) {
            return back()
                ->withErrors(['perfil_medico' => 'Los datos del perfil médico no son válidos'])
                ->withInput();
        }

        DB::transaction(function () use ($paciente, $usuario, $datos) {
            PerfilMedico::guardarOActualizar($paciente->id_paciente, $datos);
            Bitacora::registrarAccion(
                $usuario->id_usuario,
                'ACTUALIZAR',
                'PerfilMedico',
                'Actualización de perfil médico'
            );
        });

        return redirect()->route('perfil_medico.mostrar')->with('ok', 'Perfil médico actualizado');
    }
}
