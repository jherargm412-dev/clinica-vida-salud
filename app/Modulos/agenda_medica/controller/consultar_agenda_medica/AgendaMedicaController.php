<?php

namespace App\Modulos\agenda_medica\controller\consultar_agenda_medica;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CU17 Consultar Agenda Médica. */
class AgendaMedicaController extends Controller
{
    /** Muestra los médicos disponibles para administradores y recepcionistas. */
    public function listarMedicosParaAgenda(): View|RedirectResponse
    {
        $usuario = request()->user();

        if ($usuario->rol?->nombre === 'Medico') {
            return redirect()->route('agenda.mi');
        }

        $this->verificarPersonalAdministrativo($usuario);
        $medicos = Medico::listarParaAgenda();
        $usuarios = Usuario::listarPorIds($medicos->pluck('id_usuario')->all())->keyBy('id_usuario');

        foreach ($medicos as $medico) {
            $medico->setRelation('usuario', $usuarios->get($medico->id_usuario));
        }

        return view('agenda_medica.medicos', ['medicos' => $medicos]);
    }

    /** Consulta la agenda de un médico seleccionado por personal administrativo. */
    public function consultarAgenda(Request $request, int $idMedico): View
    {
        $this->verificarPersonalAdministrativo($request->user());
        $medico = Medico::listarPorIds([$idMedico])->first();

        abort_unless($medico && $medico->activo, 404);
        $usuarios = Usuario::listarPorIds([$medico->id_usuario])->keyBy('id_usuario');
        $medico->setRelation('usuario', $usuarios->get($medico->id_usuario));
        abort_unless($medico->usuario?->activo, 404);

        [$fecha, $estado] = $this->obtenerFiltros($request);

        return $this->mostrarAgenda($medico, $fecha, $estado, $request->user());
    }

    /** Consulta únicamente la agenda del médico autenticado. */
    public function consultarMiAgenda(Request $request): View
    {
        abort_unless($request->user()->rol?->nombre === 'Medico', 403);

        $medico = Medico::obtenerPorIdUsuario((int) $request->user()->id_usuario);
        abort_unless($medico && $medico->activo, 403);

        $usuarios = Usuario::listarPorIds([$medico->id_usuario])->keyBy('id_usuario');
        $medico->setRelation('usuario', $usuarios->get($medico->id_usuario));
        abort_unless($medico->usuario?->activo, 403);

        [$fecha, $estado] = $this->obtenerFiltros($request);

        return $this->mostrarAgenda($medico, $fecha, $estado, $request->user(), true);
    }

    private function mostrarAgenda(
        Medico $medico,
        ?string $fecha,
        ?string $estado,
        Usuario $usuario,
        bool $esMiAgenda = false
    ): View {
        $permisoVerMotivo = $usuario->tienePermiso('citas.registrar_atencion');
        $citas = Cita::listarPorMedicoFechaEstado($medico->id_medico, $fecha, $estado)
            ->paginate(20)
            ->withQueryString();
        $citasPagina = $citas->getCollection();

        $pacientes = Paciente::listarPorIds($citasPagina->pluck('id_paciente')->unique()->all())
            ->keyBy('id_paciente');
        $medicos = Medico::listarPorIds($citasPagina->pluck('id_medico')->unique()->all())
            ->keyBy('id_medico');
        $especialidades = Especialidad::listarPorIds($citasPagina->pluck('id_especialidad')->unique()->all())
            ->keyBy('id_especialidad');

        $idsUsuario = $pacientes->pluck('id_usuario')
            ->merge($medicos->pluck('id_usuario'))
            ->unique()
            ->all();
        $usuarios = Usuario::listarPorIds($idsUsuario)->keyBy('id_usuario');

        foreach ($pacientes as $paciente) {
            $paciente->setRelation('usuario', $usuarios->get($paciente->id_usuario));
        }
        foreach ($medicos as $medicoCita) {
            $medicoCita->setRelation('usuario', $usuarios->get($medicoCita->id_usuario));
        }
        foreach ($citasPagina as $cita) {
            $cita->setRelation('paciente', $pacientes->get($cita->id_paciente));
            $cita->setRelation('medico', $medicos->get($cita->id_medico));
            $cita->setRelation('especialidad', $especialidades->get($cita->id_especialidad));
            if (! $permisoVerMotivo) {
                $cita->setAttribute('motivo', null);
            }
        }

        $citas->setCollection($citasPagina);

        return view('agenda_medica.agenda', [
            'citas' => $citas,
            'medico' => $medico,
            'fecha' => $fecha,
            'estado' => $estado,
            'estados' => Cita::ESTADOS,
            'permisoVerMotivo' => $permisoVerMotivo,
            'esMiAgenda' => $esMiAgenda,
        ]);
    }

    /** @return array{0: ?string, 1: ?string} */
    private function obtenerFiltros(Request $request): array
    {
        $filtros = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'estado' => ['nullable', 'string', Rule::in(Cita::ESTADOS)],
        ]);

        $fecha = array_key_exists('fecha', $filtros)
            ? ($filtros['fecha'] ?: null)
            : now()->toDateString();

        return [$fecha, $filtros['estado'] ?? null];
    }

    private function verificarPersonalAdministrativo(Usuario $usuario): void
    {
        abort_unless(in_array($usuario->rol?->nombre, ['Administrador', 'Recepcionista'], true), 403);
    }
}
