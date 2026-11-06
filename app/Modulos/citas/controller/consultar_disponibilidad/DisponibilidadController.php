<?php

namespace App\Modulos\citas\controller\consultar_disponibilidad;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\citas\service\Disponibilidad;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\pagos\service\ReservaPago;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * CU12 Consultar Disponibilidad.
 * La pantalla muestra, en un solo lugar:
 *   1. las especialidades activas (botones),
 *   2. los médicos de la especialidad elegida con sus próximos 7 días (cuántos horarios libres hay cada día),
 *   3. las horas libres del médico y día elegidos, separadas en mañana y tarde.
 * También la usa CU14 Reprogramar cita (con "id_cita"): el médico y la especialidad quedan fijos.
 */
class DisponibilidadController extends Controller
{
    /** Parámetros que acepta la pantalla (todos llegan por la URL). */
    private const REGLAS = [
        'id_cita' => ['nullable', 'integer', 'exists:cita,id_cita'],
        'id_especialidad' => ['nullable', 'integer', 'exists:especialidad,id_especialidad'],
        'id_medico' => ['nullable', 'integer', 'exists:medico,id_medico'],
        'id_paciente' => ['nullable', 'integer', 'exists:paciente,id_paciente'],
        'desde' => ['nullable', 'date_format:Y-m-d'],   // primer día de la semana mostrada
    ];

    /** 1. Mostrar especialidades (secuencia 1: listarEspecialidadesActivas). */
    public function listarEspecialidadesActivas(Request $request): View|RedirectResponse
    {
        $datos = $request->validate(self::REGLAS);
        $idPaciente = $this->idPacienteContexto($request);

        // Reprogramación (CU14): directamente la semana del médico de la cita
        $reprogramacion = $this->reprogramacion($request, $datos);
        if ($reprogramacion instanceof RedirectResponse) {
            return $reprogramacion;
        }
        if ($reprogramacion) {
            return $this->vistaDisponibilidad($request, $idPaciente, ...$reprogramacion);
        }

        return $this->vistaDisponibilidad($request, $idPaciente);
    }

    /** 2. Seleccionar especialidad (secuencia 2: listarMedicosPorEspecialidad): médicos con su semana. */
    public function listarMedicosPorEspecialidad(Request $request): View|RedirectResponse
    {
        $datos = $request->validate(array_merge(self::REGLAS, [
            'id_especialidad' => ['required', 'integer', 'exists:especialidad,id_especialidad'],
        ]));
        $idPaciente = $this->idPacienteContexto($request);

        $reprogramacion = $this->reprogramacion($request, $datos);
        if ($reprogramacion instanceof RedirectResponse) {
            return $reprogramacion;
        }
        if ($reprogramacion) {
            return $this->vistaDisponibilidad($request, $idPaciente, ...$reprogramacion);
        }

        $especialidad = Especialidad::query()
            ->where('id_especialidad', $datos['id_especialidad'])
            ->where('activo', true)
            ->firstOrFail();

        return $this->vistaDisponibilidad(
            $request,
            $idPaciente,
            especialidad: $especialidad,
            medicos: $this->medicosDisponibles($especialidad->id_especialidad)
        );
    }

    /** 3. Consultar disponibilidad (secuencia 3: consultarHorariosDisponibles): horas libres del médico ese día. */
    public function consultarHorariosDisponibles(Request $request): View|RedirectResponse
    {
        $datos = $request->validate(array_merge(self::REGLAS, [
            'id_especialidad' => ['required', 'integer', 'exists:especialidad,id_especialidad'],
            'id_medico' => ['required', 'integer', 'exists:medico,id_medico'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
        ]));
        $idPaciente = $this->idPacienteContexto($request);
        $fecha = Carbon::parse($datos['fecha'])->startOfDay();

        $reprogramacion = $this->reprogramacion($request, $datos);
        if ($reprogramacion instanceof RedirectResponse) {
            return $reprogramacion;
        }
        if ($reprogramacion) {
            return $this->vistaDisponibilidad($request, $idPaciente, ...$reprogramacion, fecha: $fecha);
        }

        $especialidad = Especialidad::query()
            ->where('id_especialidad', $datos['id_especialidad'])
            ->where('activo', true)
            ->first();
        if (! $especialidad) {
            throw ValidationException::withMessages(['id_especialidad' => 'Seleccione una especialidad activa.']);
        }

        $medicos = $this->medicosDisponibles($especialidad->id_especialidad);
        $medico = $medicos->firstWhere('id_medico', (int) $datos['id_medico']);
        if (! $medico) {
            throw ValidationException::withMessages(['id_medico' => 'Seleccione un médico activo de la especialidad elegida.']);
        }

        return $this->vistaDisponibilidad($request, $idPaciente, $especialidad, $medicos, $medico, $fecha);
    }

    /** Médicos activos (y con cuenta activa) de la especialidad. */
    private function medicosDisponibles(int $idEspecialidad): Collection
    {
        return Medico::listarActivosPorEspecialidad($idEspecialidad)
            ->filter(fn (Medico $medico) => (bool) $medico->usuario?->activo)
            ->values();
    }

    /**
     * Si llega "id_cita" (CU14 Reprogramar), devuelve la especialidad, el médico y la cita a reprogramar
     * (el médico y la especialidad no se pueden cambiar). Null si no es una reprogramación.
     */
    private function reprogramacion(Request $request, array $datos): array|RedirectResponse|null
    {
        $cita = $this->citaParaReprogramar($request, isset($datos['id_cita']) ? (int) $datos['id_cita'] : null);
        if (! $cita) {
            return null;
        }

        abort_unless(
            (! isset($datos['id_especialidad']) || (int) $datos['id_especialidad'] === (int) $cita->id_especialidad)
                && (! isset($datos['id_medico']) || (int) $datos['id_medico'] === (int) $cita->id_medico),
            422,
            'No puede cambiar el médico ni la especialidad al reprogramar.'
        );

        $especialidad = Especialidad::obtenerPorId($cita->id_especialidad);
        $medico = Medico::obtenerPorId($cita->id_medico);
        abort_unless($especialidad && $medico, 404);
        if (! $medico->activo || ! $medico->usuario?->activo) {
            return redirect()->route('citas.reprogramar.index')
                ->with('error', 'El médico no está activo y no puede recibir citas.');
        }

        return [
            'especialidad' => $especialidad,
            'medicos' => Medico::query()->with('usuario')->whereKey($medico->id_medico)->get(),
            'medico' => $medico,
            'cita' => $cita,
        ];
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
        Request $request,
        ?int $idPaciente,
        ?Especialidad $especialidad = null,
        ?Collection $medicos = null,
        ?Medico $medico = null,
        ?Carbon $fecha = null,
        ?Cita $cita = null
    ): View {
        // Semana mostrada: 7 días desde "desde" (hoy como mínimo); el día elegido siempre queda visible
        $hoy = today();
        $desde = $request->filled('desde') ? Carbon::parse($request->query('desde')) : ($fecha?->copy() ?? $hoy->copy());
        if ($desde->lt($hoy)) {
            $desde = $hoy->copy();
        }
        if ($fecha && ($fecha->lt($desde) || $fecha->gt($desde->copy()->addDays(6)))) {
            $desde = $fecha->copy();
        }

        // Cuántos horarios libres tiene cada médico cada día de la semana
        $semanas = [];
        if ($medicos !== null) {
            // CU21 (7a): las citas que no se pagaron a tiempo liberan su horario antes de contarlo
            ReservaPago::liberarVencidas();
            foreach ($medicos as $opcion) {
                $semanas[$opcion->id_medico] = Disponibilidad::resumenDias($opcion->id_medico, $desde, 7, $cita?->id_cita);
            }
        }

        // Horas del día elegido (libres y ocupadas), separadas en mañana y tarde
        $turnos = ($medico && $fecha)
            ? Disponibilidad::porTurno(Disponibilidad::horasDelDia($medico->id_medico, $fecha, $cita?->id_cita))
            : null;

        // Datos que se repiten en todos los enlaces de la pantalla
        $esPaciente = $request->user()->rol?->nombre === 'Paciente';
        $conservar = array_filter([
            'id_paciente' => $esPaciente ? null : $idPaciente,
            'id_cita' => $cita?->id_cita,
        ], fn ($valor) => $valor !== null);

        return view('citas.consultar_disponibilidad.index', [
            'especialidades' => $cita ? collect() : Especialidad::listarActivas(),
            'especialidadSeleccionada' => $especialidad,
            'medicos' => $medicos,
            'medicoSeleccionado' => $medico,
            'fecha' => $fecha,
            'desde' => $desde,
            'semanas' => $semanas,
            'turnos' => $turnos,
            'cita' => $cita,
            'idPaciente' => $esPaciente ? null : $idPaciente,
            'conservar' => $conservar,
            'puedeRegistrar' => $request->user()->tienePermiso('citas.registrar'),
        ]);
    }
}
