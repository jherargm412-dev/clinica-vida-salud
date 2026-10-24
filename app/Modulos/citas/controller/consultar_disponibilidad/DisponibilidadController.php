<?php

namespace App\Modulos\citas\controller\consultar_disponibilidad;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\HorarioAtencion;
use App\Modulos\medicos_especialidades\models\Medico;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** CU12 Consultar Disponibilidad */
class DisponibilidadController extends Controller
{
    public function listarEspecialidadesActivas(): View
    {
        return $this->vistaDisponibilidad();
    }

    public function listarMedicosPorEspecialidad(Request $request): View
    {
        $datos = $request->validate([
            'id_especialidad' => ['required', 'integer', 'exists:especialidad,id_especialidad'],
        ]);

        $especialidad = Especialidad::query()
            ->where('id_especialidad', $datos['id_especialidad'])
            ->where('activo', true)
            ->firstOrFail();

        return $this->vistaDisponibilidad(
            especialidad: $especialidad,
            medicos: Medico::listarActivosPorEspecialidad($especialidad->id_especialidad)
        );
    }

    public function consultarHorariosDisponibles(Request $request): View
    {
        $datos = $request->validate([
            'id_especialidad' => ['required', 'integer', 'exists:especialidad,id_especialidad'],
            'id_medico' => ['required', 'integer', 'exists:medico,id_medico'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $especialidad = Especialidad::query()
            ->where('id_especialidad', $datos['id_especialidad'])
            ->where('activo', true)
            ->firstOrFail();
        $medicos = Medico::listarActivosPorEspecialidad($especialidad->id_especialidad);
        $medico = $medicos->firstWhere('id_medico', (int) $datos['id_medico']);

        if (! $medico) {
            throw ValidationException::withMessages([
                'id_medico' => 'Seleccione un médico activo de la especialidad elegida.',
            ]);
        }

        $fecha = Carbon::parse($datos['fecha']);
        $horarios = HorarioAtencion::listarHorariosActivosPorDia(
            $medico->id_medico,
            $fecha->dayOfWeekIso
        );
        $citasOcupadas = Cita::listarHorasOcupadas($medico->id_medico, $fecha->toDateString());
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
            horariosDisponibles: $horariosDisponibles
        );
    }

    private function vistaDisponibilidad(
        ?Especialidad $especialidad = null,
        ?\Illuminate\Database\Eloquent\Collection $medicos = null,
        ?Medico $medico = null,
        ?string $fecha = null,
        ?\Illuminate\Support\Collection $horariosDisponibles = null
    ): View {
        return view('citas.consultar_disponibilidad.index', [
            'especialidades' => Especialidad::listarActivas(),
            'especialidadSeleccionada' => $especialidad,
            'medicos' => $medicos,
            'medicoSeleccionado' => $medico,
            'fecha' => $fecha,
            'horariosDisponibles' => $horariosDisponibles,
            'diaConsultado' => $fecha ? Carbon::parse($fecha)->dayOfWeekIso : null,
        ]);
    }
}
