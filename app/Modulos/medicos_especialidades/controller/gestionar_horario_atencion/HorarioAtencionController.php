<?php

namespace App\Modulos\medicos_especialidades\controller\gestionar_horario_atencion;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\compartido\Texto;
use App\Modulos\medicos_especialidades\models\HorarioAtencion;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\medicos_especialidades\request\gestionar_horario_atencion\HorarioAtencionRequest;
use App\Modulos\usuarios_acceso\models\Bitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** CU11 Gestionar Horario de Atención */
class HorarioAtencionController extends Controller
{
    // Secuencia 1 y 2: lista de médicos activos con el resumen de su semana (una fila por médico)
    public function index(Request $request): View
    {
        $texto = trim((string) $request->query('buscar', ''));
        // Filtro "Horario": 'con' | 'sin' | null (todos)
        $filtro = in_array($request->query('horario'), ['con', 'sin'], true) ? $request->query('horario') : null;

        $buscado = Texto::sinTildes(mb_strtolower($texto));
        $medicos = Medico::listarActivos()
            ->load(['horarios' => fn ($q) => $q->where('activo', true)->orderBy('dia_semana')->orderBy('hora_inicio')])
            ->filter(fn (Medico $m) => $texto === ''
                || str_contains(Texto::sinTildes(mb_strtolower($m->nombre_completo)), $buscado)
                || str_contains(mb_strtolower($m->codigo), $buscado))
            ->filter(fn (Medico $m) => $filtro === null || ($filtro === 'con') === $m->horarios->isNotEmpty())
            ->values();

        return view('medicos_especialidades.gestionar_horario_atencion.index', [
            'medicos' => $medicos,
            'texto' => $texto,
            'filtro' => $filtro,
        ]);
    }

    public function listarMedicosActivos(): View
    {
        return $this->index(request());
    }

    // Secuencia 2 (consultar los horarios de un médico): ahora se ven en su semana
    public function listarHorariosMedico(Medico $medico): RedirectResponse
    {
        return redirect()->route('horarios.semana', $medico);
    }

    // "Asignar horario": grilla de la semana del médico (los horarios activos, por día)
    public function editarSemana(Medico $medico): View|RedirectResponse
    {
        if (! $medico->activo) {
            return redirect()->route('horarios.index')->with('error', 'El médico no está activo');
        }

        $medico->load('usuario', 'especialidades');
        $activos = HorarioAtencion::listarPorMedico($medico->id_medico, ['estado' => 'activo']);

        // Tramos por día: [1 => [['hora_inicio' => '08:00', 'hora_fin' => '12:00', 'duracion_cita' => 30]], ...]
        $tramos = [];
        foreach (HorarioAtencion::DIAS as $dia => $nombre) {
            $tramos[$dia] = $activos->where('dia_semana', $dia)->map(fn ($h) => [
                'hora_inicio' => HorarioAtencion::horaCorta($h->hora_inicio),
                'hora_fin' => HorarioAtencion::horaCorta($h->hora_fin),
                'duracion_cita' => $h->duracion_cita,
            ])->values()->all();
        }

        return view('medicos_especialidades.gestionar_horario_atencion.semana', [
            'medico' => $medico,
            'dias' => HorarioAtencion::DIAS,
            'tramos' => $tramos,
        ]);
    }

    /**
     * Guarda la semana completa. Por cada día compara lo guardado con lo del formulario y aplica
     * las mismas operaciones y reglas de CU11:
     *   tramo igual → sin cambios (o modificar si cambió la duración) · tramo cambiado → modificar
     *   tramo quitado o día desmarcado → desactivar · tramo nuevo → activar uno inactivo igual o registrar
     * Modificar y desactivar no se permiten con citas pendientes; los tramos de un día no se pueden superponer.
     * Todo o nada: si un día tiene un error, no se guarda ningún cambio.
     */
    public function guardarSemana(Request $request, Medico $medico): RedirectResponse
    {
        if (! $medico->activo) {
            return redirect()->route('horarios.index')->with('error', 'El médico no está activo');
        }

        $atiende = (array) $request->input('atiende', []);
        $enviados = (array) $request->input('tramos', []);
        $errores = [];
        $deseados = [];   // [dia => [['hora_inicio', 'hora_fin', 'duracion_cita'], ...]]

        // 1. Validar cada tramo (mismas reglas y mensajes que HorarioAtencionRequest)
        foreach (HorarioAtencion::DIAS as $dia => $nombre) {
            $deseados[$dia] = [];
            if (empty($atiende[$dia])) {
                continue;   // el día no se atiende: sus tramos activos se desactivan
            }
            foreach ((array) ($enviados[$dia] ?? []) as $tramo) {
                $inicio = trim((string) ($tramo['hora_inicio'] ?? ''));
                $finTramo = trim((string) ($tramo['hora_fin'] ?? ''));
                $duracion = trim((string) ($tramo['duracion_cita'] ?? ''));

                $error = match (true) {
                    $inicio === '' || $finTramo === '' || $duracion === '' => 'Complete los campos obligatorios',
                    ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $inicio) => 'La hora de inicio no es válida',
                    ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $finTramo) => 'La hora de fin no es válida',
                    $finTramo <= $inicio => 'La hora de fin debe ser posterior a la hora de inicio',
                    ! ctype_digit($duracion) => 'La duración de la cita debe ser un número entero',
                    (int) $duracion < 15 => 'La duración mínima de la cita es 15 minutos',
                    (int) $duracion > 180 => 'La duración máxima de la cita es 180 minutos',
                    default => null,
                };
                if ($error) {
                    $errores["dias.$dia"] = $error;
                    continue 2;
                }
                $deseados[$dia][] = ['hora_inicio' => $inicio, 'hora_fin' => $finTramo, 'duracion_cita' => (int) $duracion];
            }

            if (empty($deseados[$dia])) {
                $errores["dias.$dia"] = 'Agregue al menos un tramo o desmarque el día';
                continue;
            }

            // Superposición entre los tramos del mismo día (secuencia 3.2 existeSuperposicion)
            usort($deseados[$dia], fn ($a, $b) => strcmp($a['hora_inicio'], $b['hora_inicio']));
            for ($i = 1; $i < count($deseados[$dia]); $i++) {
                if ($deseados[$dia][$i - 1]['hora_fin'] > $deseados[$dia][$i]['hora_inicio']) {
                    $errores["dias.$dia"] = 'El horario se superpone con otro horario del mismo médico';
                    continue 2;
                }
            }
        }

        if ($errores) {
            return back()->withErrors($errores)->withInput();
        }

        $resultado = DB::transaction(function () use ($medico, $deseados): array {
            // Se bloquean los horarios del médico mientras se calcula y se guarda
            $todos = HorarioAtencion::query()->where('id_medico', $medico->id_medico)->lockForUpdate()->get();

            // 2. Plan de cambios por día (todavía sin guardar nada)
            $plan = [];
            $errores = [];
            foreach (HorarioAtencion::DIAS as $dia => $nombre) {
                $actuales = $todos->where('dia_semana', $dia)->where('activo', true)->sortBy('hora_inicio')->values()->all();
                $pendientes = $deseados[$dia];

                // a) Tramo con las mismas horas: se conserva (si solo cambió la duración, se modifica)
                foreach ($actuales as $k => $actual) {
                    foreach ($pendientes as $j => $deseado) {
                        if (HorarioAtencion::horaCorta($actual->hora_inicio) === $deseado['hora_inicio']
                            && HorarioAtencion::horaCorta($actual->hora_fin) === $deseado['hora_fin']) {
                            if ((int) $actual->duracion_cita !== $deseado['duracion_cita']) {
                                $plan[] = ['modificar', $actual, $deseado];
                            }
                            unset($actuales[$k], $pendientes[$j]);
                            break;
                        }
                    }
                }
                $actuales = array_values($actuales);
                $pendientes = array_values($pendientes);

                // b) Tramos que cambiaron de hora: se emparejan en orden y se modifican
                $cantidad = min(count($actuales), count($pendientes));
                for ($i = 0; $i < $cantidad; $i++) {
                    $plan[] = ['modificar', $actuales[$i], $pendientes[$i]];
                }
                // c) Tramos que ya no están: se desactivan
                foreach (array_slice($actuales, $cantidad) as $actual) {
                    $plan[] = ['desactivar', $actual, null];
                }
                // d) Tramos nuevos: se reactiva uno inactivo con las mismas horas o se registra
                foreach (array_slice($pendientes, $cantidad) as $deseado) {
                    $inactivo = $todos->first(fn ($h) => ! $h->activo && $h->dia_semana === $dia
                        && HorarioAtencion::horaCorta($h->hora_inicio) === $deseado['hora_inicio']
                        && HorarioAtencion::horaCorta($h->hora_fin) === $deseado['hora_fin']);
                    $plan[] = $inactivo ? ['activar', $inactivo, $deseado] : ['registrar', null, $deseado + ['dia_semana' => $dia]];
                }

                // Modificar y desactivar no se permiten con citas pendientes en el horario ACTUAL (secuencia 4.3 y 5.3)
                foreach ($plan as [$accion, $horario]) {
                    if ($horario && $horario->dia_semana === $dia && in_array($accion, ['modificar', 'desactivar'], true)
                        && Cita::existenCitasPendientes($medico->id_medico, $dia, $horario->hora_inicio, $horario->hora_fin)) {
                        $errores["dias.$dia"] = $accion === 'modificar'
                            ? 'No se puede modificar un horario con citas pendientes'
                            : 'No se puede desactivar un horario con citas pendientes';
                    }
                }
            }

            if ($errores) {
                return ['errores' => $errores];
            }

            // 3. Aplicar los cambios y registrarlos en la bitácora (mismas acciones que CU11)
            $nombreMedico = $medico->nombre_completo;
            foreach ($plan as [$accion, $horario, $datos]) {
                match ($accion) {
                    'registrar' => $horario = HorarioAtencion::create($datos + ['id_medico' => $medico->id_medico, 'activo' => true]),
                    'modificar' => $horario->update($datos),
                    'activar' => $horario->update($datos + ['activo' => true]),
                    'desactivar' => $horario->update(['activo' => false]),
                };
                Bitacora::registrar(
                    strtoupper($accion) . '_HORARIO',
                    'horario_atencion',
                    'Horario: ' . $nombreMedico . ' - ' . HorarioAtencion::DIAS[$horario->dia_semana] . ' '
                        . HorarioAtencion::horaCorta($horario->hora_inicio) . '-' . HorarioAtencion::horaCorta($horario->hora_fin)
                );
            }

            return ['cambios' => count($plan)];
        });

        if (isset($resultado['errores'])) {
            return back()->withErrors($resultado['errores'])->withInput();
        }

        return redirect()->route('horarios.index')
            ->with('ok', $resultado['cambios'] > 0 ? 'Horario de atención guardado' : 'No hubo cambios en el horario');
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

        // Un horario con citas pendientes no se puede modificar (detallar CU11).
        // Se revisa el horario ACTUAL (lo que está guardado), no el nuevo rango:
        // si no, al mover las horas las citas ya registradas quedarían fuera del horario.
        if (Cita::existenCitasPendientes(
            $horario->id_medico,
            $horario->dia_semana,
            $horario->hora_inicio,
            $horario->hora_fin
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
