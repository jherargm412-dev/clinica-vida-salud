<?php

namespace App\Modulos\pacientes\controller\consultar_historial_citas;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\pagos\service\ReservaPago;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CU08 Consultar Historial de Citas (HistorialCitasController en los diagramas). */
class HistorialCitasController extends Controller
{
    private const ERROR_PACIENTE_NO_ASOCIADO = 'Su cuenta no está asociada a un paciente, contacte a la clínica';

    /** Muestra el buscador y la lista de pacientes a recepción, o el historial propio al paciente. */
    public function mostrarBuscadorPacientes(Request $request): View
    {
        $usuario = Auth::user();
        $estado = $this->validarEstado($request);

        if (Paciente::obtenerPorIdUsuario($usuario->id_usuario)) {
            return $this->consultarMisCitas($usuario->id_usuario, $estado);
        }

        // Paso 3: además del buscador, se muestra la lista de pacientes (10 por página)
        return view('pacientes.consultar_historial_citas.buscar_paciente', [
            'pacientes' => Paciente::listarPaginados(10),
            'texto' => '',
            'busquedaRealizada' => false,
        ]);
    }

    /** Busca pacientes por CI, nombre o apellido, incluyendo fichas inactivas. */
    public function buscarPacientes(Request $request): View
    {
        $this->verificarNoPaciente();
        $datos = $request->validate(
            ['texto' => ['required', 'string', 'max:100']],
            [
                'texto.required' => 'Ingrese el CI, nombre o apellido del paciente',
                'texto.max' => 'La búsqueda no puede superar los 100 caracteres',
            ]
        );
        $texto = trim($datos['texto']);

        if ($texto === '') {
            return back()
                ->withErrors(['texto' => 'Ingrese el CI, nombre o apellido del paciente'])
                ->withInput();
        }

        $porCI = Paciente::buscarPorCI($texto)->get()->keyBy('id_paciente');
        $usuarios = Usuario::buscarPorNombreApellido($texto);
        $porNombre = Paciente::listarPorIdsUsuario($usuarios->pluck('id_usuario')->all())->keyBy('id_paciente');
        $idsPaciente = $porCI->keys()->merge($porNombre->keys())->unique()->all();

        $pacientes = Paciente::listarPorIds($idsPaciente);

        return view('pacientes.consultar_historial_citas.buscar_paciente', [
            'pacientes' => $pacientes,
            'texto' => $texto,
            'busquedaRealizada' => true,
        ]);
    }

    /** Selecciona un paciente y muestra su historial. */
    public function seleccionarPaciente(int $idPaciente, Request $request): View
    {
        $this->verificarNoPaciente();

        return $this->consultarHistorialPaciente($idPaciente, $this->validarEstado($request));
    }

    /** Permite al paciente abrir directamente sus propias citas. */
    public function mostrarMisCitas(Request $request): View
    {
        $usuario = Auth::user();
        $paciente = Paciente::obtenerPorIdUsuario($usuario->id_usuario);
        abort_unless($paciente, 403);

        return $this->mostrarHistorial($paciente, $this->validarEstado($request), true);
    }

    /** Consulta el historial del paciente seleccionado por recepción. */
    public function consultarHistorialPaciente(int $idPaciente, ?string $estado = null): View
    {
        $paciente = Paciente::obtenerPorId($idPaciente);
        abort_if($paciente === null, 404);

        return $this->mostrarHistorial($paciente, $estado, false);
    }

    /** Resuelve y consulta únicamente el historial del usuario autenticado. */
    public function consultarMisCitas(int $idUsuario, ?string $estado = null): View
    {
        $paciente = Paciente::obtenerPorIdUsuario($idUsuario);

        if ($paciente === null) {
            return view('pacientes.consultar_historial_citas.historial', [
                'paciente' => null,
                'citas' => collect(),
                'estado' => $estado,
                'esPaciente' => true,
                'error' => self::ERROR_PACIENTE_NO_ASOCIADO,
            ]);
        }

        return $this->mostrarHistorial($paciente, $estado, true);
    }

    private function mostrarHistorial(Paciente $paciente, ?string $estado, bool $esPaciente): View
    {
        ReservaPago::liberarVencidas();

        $citas = Cita::listarPorPaciente($paciente->id_paciente, $estado)
            ->paginate(15)
            ->withQueryString();

        $citasPagina = $citas->getCollection();
        $medicos = Medico::listarPorIds($citasPagina->pluck('id_medico')->unique()->all())->keyBy('id_medico');
        $especialidades = Especialidad::listarPorIds($citasPagina->pluck('id_especialidad')->unique()->all())
            ->keyBy('id_especialidad');
        $idsUsuarioMedico = $medicos->pluck('id_usuario')->all();
        $usuarios = Usuario::listarPorIds(array_unique([$paciente->id_usuario, ...$idsUsuarioMedico]))
            ->keyBy('id_usuario');

        foreach ($medicos as $medico) {
            $medico->setRelation('usuario', $usuarios->get($medico->id_usuario));
        }

        foreach ($citasPagina as $cita) {
            $cita->setRelation('medico', $medicos->get($cita->id_medico));
            $cita->setRelation('especialidad', $especialidades->get($cita->id_especialidad));
        }

        $citas->setCollection($citasPagina);
        $paciente->setRelation('usuario', $usuarios->get($paciente->id_usuario));

        return view('pacientes.consultar_historial_citas.historial', [
            'paciente' => $paciente,
            'citas' => $citas,
            'estado' => $estado,
            'esPaciente' => $esPaciente,
            'error' => null,
        ]);
    }

    private function validarEstado(Request $request): ?string
    {
        $datos = $request->validate([
            'estado' => ['nullable', 'string', Rule::in(Cita::ESTADOS)],
        ]);

        return $datos['estado'] ?? null;
    }

    private function verificarNoPaciente(): void
    {
        $usuario = Auth::user();
        abort_if(Paciente::obtenerPorIdUsuario($usuario->id_usuario), 403);
    }
}
