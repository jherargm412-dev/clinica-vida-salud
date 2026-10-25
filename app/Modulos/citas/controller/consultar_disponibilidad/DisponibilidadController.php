<?php

namespace App\Modulos\citas\controller\consultar_disponibilidad;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\HorarioAtencion;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** CU12 Consultar Disponibilidad */
class DisponibilidadController extends Controller
{
    public function listarEspecialidadesActivas(Request $request): View|RedirectResponse
    {
        $datos = $request->validate([
            'id_cita' => ['nullable', 'integer', 'exists:cita,id_cita'],
            'id_especialidad' => ['nullable', 'integer', 'exists:especialidad,id_especialidad'],
            'id_medico' => ['nullable', 'integer', 'exists:medico,id_medico'],
        ]);
        $this->validarPacienteContexto($request);
        $cita = $this->citaParaReprogramar(
            $request,
            isset($datos['id_cita']) ? (int) $datos['id_cita'] : null
        );

        if ($cita) {
            $especialidad = Especialidad::obtenerPorId($cita->id_especialidad);
            $medico = Medico::obtenerPorId($cita->id_medico);
            abort_unless($especialidad && $medico, 404);
            if (! $medico->activo || ! $medico->usuario?->activo) {
                return redirect()->route('citas.reprogramar.index')
                    ->with('error', 'El médico no está activo y no puede recibir citas.');
            }

            return $this->vistaDisponibilidad(
                especialidad: $especialidad,
                medicos: Medico::query()->with('usuario')->whereKey($medico->id_medico)->get(),
                medico: $medico,
                idPaciente: $this->idPacienteContexto($request),
                idCitaExcluir: $cita->id_cita
            );
        }

        return $this->vistaDisponibilidad(idPaciente: $this->idPacienteContexto($request));
    }

    public function listarMedicosPorEspecialidad(Request $request): View|RedirectResponse
    {
        $datos = $request->validate([
            'id_especialidad' => ['required', 'integer', 'exists:especialidad,id_especialidad'],
            'id_medico' => ['nullable', 'integer', 'exists:medico,id_medico'],
            'id_paciente' => ['nullable', 'integer', 'exists:paciente,id_paciente'],
            'id_cita' => ['nullable', 'integer', 'exists:cita,id_cita'],
        ]);
        $idPaciente = $this->idPacienteContexto($request);
        $cita = $this->citaParaReprogramar(
            $request,
            isset($datos['id_cita']) ? (int) $datos['id_cita'] : null
        );

        if ($cita) {
            abort_unless(
                (int) $datos['id_especialidad'] === (int) $cita->id_especialidad
                    && (! isset($datos['id_medico']) || (int) $datos['id_medico'] === (int) $cita->id_medico),
                422
            );
            $especialidad = Especialidad::obtenerPorId($cita->id_especialidad);
            $medico = Medico::obtenerPorId($cita->id_medico);
            abort_unless($especialidad && $medico, 404);
            if (! $medico->activo || ! $medico->usuario?->activo) {
                return redirect()->route('citas.reprogramar.index')
                    ->with('error', 'El médico no está activo y no puede recibir citas.');
            }

            return $this->vistaDisponibilidad(
                especialidad: $especialidad,
                medicos: Medico::query()->with('usuario')->whereKey($medico->id_medico)->get(),
                medico: $medico,
                idPaciente: $idPaciente,
                idCitaExcluir: $cita->id_cita
            );
        }

        $especialidad = Especialidad::query()
            ->where('id_especialidad', $datos['id_especialidad'])
            ->where('activo', true)
            ->firstOrFail();

        return $this->vistaDisponibilidad(
            especialidad: $especialidad,
            medicos: Medico::listarActivosPorEspecialidad($especialidad->id_especialidad),
            idPaciente: $idPaciente
        );
    }

    public function consultarHorariosDisponibles(Request $request): View|RedirectResponse
    {
        $datos = $request->validate([
            'id_especialidad' => ['required', 'integer', 'exists:especialidad,id_especialidad'],
            'id_medico' => ['required', 'integer', 'exists:medico,id_medico'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'id_paciente' => ['nullable', 'integer', 'exists:paciente,id_paciente'],
            'id_cita' => ['nullable', 'integer', 'exists:cita,id_cita'],
        ]);
        $idPaciente = $this->idPacienteContexto($request);
        $citaExcluir = $this->citaParaReprogramar(
            $request,
            isset($datos['id_cita']) ? (int) $datos['id_cita'] : null
        );

        if ($citaExcluir) {
            abort_unless(
                (int) $datos['id_especialidad'] === (int) $citaExcluir->id_especialidad
                    && (int) $datos['id_medico'] === (int) $citaExcluir->id_medico,
                422,
                'No puede cambiar el médico ni la especialidad al reprogramar.'
            );
            $especialidad = Especialidad::obtenerPorId($citaExcluir->id_especialidad);
            $medico = Medico::obtenerPorId($citaExcluir->id_medico);
            abort_unless($especialidad && $medico, 404);
            if (! $medico->activo || ! $medico->usuario?->activo) {
                return redirect()->route('citas.reprogramar.index')
                    ->with('error', 'El médico no está activo y no puede recibir citas.');
            }
            $medicos = Medico::query()->with('usuario')->whereKey($medico->id_medico)->get();
        } else {
            $especialidad = Especialidad::query()
                ->where('id_especialidad', $datos['id_especialidad'])
                ->where('activo', true)
                ->firstOrFail();
            $medicos = Medico::listarActivosPorEspecialidad($especialidad->id_especialidad);
            $medico = $medicos->firstWhere('id_medico', (int) $datos['id_medico']);
        }

        if (! $especialidad || ! $medico || ! $medico->activo) {
            if ($citaExcluir) {
                return redirect()->route('citas.reprogramar.index')
                    ->with('error', 'El médico no está activo y no puede recibir citas.');
            }

            throw ValidationException::withMessages([
                'id_medico' => 'Seleccione un médico activo de la especialidad elegida.',
            ]);
        }
        if (! $citaExcluir && ! $especialidad->activo) {
            throw ValidationException::withMessages([
                'id_especialidad' => 'Seleccione una especialidad activa.',
            ]);
        }

        $fecha = Carbon::parse($datos['fecha']);
        $horarios = HorarioAtencion::listarHorariosActivosPorDia(
            $medico->id_medico,
            $fecha->dayOfWeekIso
        );
        $citasOcupadas = Cita::listarHorasOcupadas(
            $medico->id_medico,
            $fecha->toDateString(),
            $citaExcluir?->id_cita
        );
        $horariosDisponibles = $horarios->map(function (HorarioAtencion $horario) use ($fecha, $citasOcupadas): array {
            $bloques = [];
            $inicioHorario = Carbon::parse($horario->hora_inicio);
            $finHorario = Carbon::parse($horario->hora_fin);

            while ($inicioHorario->copy()->addMinutes($horario->duracion_cita)->lessThanOrEqualTo($finHorario)) {
                $finBloque = $inicioHorario->copy()->addMinutes($horario->duracion_cita);
                $horaInicio = $inicioHorario->format('H:i:s');
                $horaFin = $finBloque->format('H:i:s');
                $esHoraPasada = $fecha->isToday() && $inicioHorario->lessThanOrEqualTo(now());
                $estaOcupado = $citasOcupadas->contains(fn (Cita $cita): bool =>
                    $cita->hora_inicio < $horaFin && $cita->hora_fin > $horaInicio
                );

                if (! $esHoraPasada && ! $estaOcupado) {
                    $bloques[] = [
                        'hora_inicio' => $inicioHorario->format('H:i'),
                        'hora_fin' => $finBloque->format('H:i'),
                    ];
                }

                $inicioHorario->addMinutes($horario->duracion_cita);
            }

            return ['horario' => $horario, 'bloques' => $bloques];
        });

        return $this->vistaDisponibilidad(
            especialidad: $especialidad,
            medicos: $medicos,
            medico: $medico,
            fecha: $fecha->toDateString(),
            horariosDisponibles: $horariosDisponibles,
            idPaciente: $idPaciente,
            idCitaExcluir: $citaExcluir?->id_cita
        );
    }

    private function citaParaReprogramar(Request $request, ?int $idCita): ?Cita
    {
        if (! $idCita) {
            return null;
        }

        // El acceso se decide solo por permiso (no por el nombre del rol),
        // así cualquier rol con "citas.reprogramar" puede usarlo (ej. Administrador).
        abort_unless($request->user()->tienePermiso('citas.reprogramar'), 403);

        $cita = Cita::obtenerDetallePorId($idCita);
        abort_unless($cita, 404);
        abort_unless(
            in_array($cita->estado, ['Programada', 'Confirmada'], true)
                && Carbon::parse($cita->fecha->toDateString() . ' ' . $cita->hora_inicio)->isFuture(),
            422,
            'Esta cita ya no se puede reprogramar.'
        );

        return $cita;
    }

    private function validarPacienteContexto(Request $request): void
    {
        $request->validate([
            'id_paciente' => ['nullable', 'integer', 'exists:paciente,id_paciente'],
        ]);
    }

    private function idPacienteContexto(Request $request): ?int
    {
        if ($request->user()->rol?->nombre === 'Paciente') {
            $paciente = Paciente::obtenerPorUsuario($request->user()->id_usuario);
            abort_unless($paciente && $paciente->activo && $paciente->usuario?->activo, 403, 'El paciente no está activo.');

            return $paciente->id_paciente;
        }

        $idPaciente = $request->query('id_paciente');
        if (! $idPaciente) {
            return null;
        }

        $paciente = Paciente::obtenerPorId((int) $idPaciente);
        if (! $paciente || ! $paciente->activo || ! $paciente->usuario?->activo) {
            abort(404, 'El paciente seleccionado no existe o está inactivo.');
        }

        return $paciente->id_paciente;
    }

    private function vistaDisponibilidad(
        ?Especialidad $especialidad = null,
        ?\Illuminate\Database\Eloquent\Collection $medicos = null,
        ?Medico $medico = null,
        ?string $fecha = null,
        ?\Illuminate\Support\Collection $horariosDisponibles = null,
        ?int $idPaciente = null,
        ?int $idCitaExcluir = null
    ): View {
        return view('citas.consultar_disponibilidad.index', [
            'especialidades' => Especialidad::listarActivas(),
            'especialidadSeleccionada' => $especialidad,
            'medicos' => $medicos,
            'medicoSeleccionado' => $medico,
            'fecha' => $fecha,
            'horariosDisponibles' => $horariosDisponibles,
            'diaConsultado' => $fecha ? Carbon::parse($fecha)->dayOfWeekIso : null,
            'idPaciente' => $idPaciente,
            'idCitaExcluir' => $idCitaExcluir,
        ]);
    }
}
