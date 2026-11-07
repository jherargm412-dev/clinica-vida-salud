<?php

namespace App\Modulos\reportes\controller\generar_reporte;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\pagos\models\DetallePago;
use App\Modulos\pagos\models\MetodoPago;
use App\Modulos\pagos\models\Pago;
use App\Modulos\pagos\models\SesionCaja;
use App\Modulos\reportes\service\ExportadorReporte;
use App\Modulos\reportes\service\Periodo;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * CU19 Generar reporte (Ctrl_Reportes en los diagramas).
 * Los reportes SOLO consultan: la única escritura es la bitácora al exportar ("Exportar reporte").
 *
 * Permisos:
 *  - reportes.generar        → Reporte de citas (solo filtros de fecha y estado) y Agenda diaria (alternativo 2a).
 *  - reportes.generar_todos  → los 6 reportes con todos sus filtros (Administrador).
 */
class ReporteController extends Controller
{
    /**
     * Reportes del CU19, en el orden en que se muestran.
     * todos: exige reportes.generar_todos · excel: se puede exportar a Excel (la agenda solo a PDF)
     * tabla: tabla principal que consulta (se anota en la bitácora al exportar).
     */
    public const REPORTES = [
        'citas'      => ['titulo' => 'Reporte de citas',      'grupo' => 'Citas',        'icono' => 'bi-calendar-check',
                         'todos' => false, 'excel' => true,  'tabla' => 'cita',
                         'descripcion' => 'Citas por período, médico, especialidad y estado'],
        'asistencia' => ['titulo' => 'Asistencia',            'grupo' => 'Citas',        'icono' => 'bi-person-check',
                         'todos' => true,  'excel' => true,  'tabla' => 'cita',
                         'descripcion' => 'Citas atendidas, canceladas e inasistencias por día'],
        'agenda'     => ['titulo' => 'Agenda diaria',         'grupo' => 'Citas',        'icono' => 'bi-calendar-day',
                         'todos' => false, 'excel' => false, 'tabla' => 'cita',
                         'descripcion' => 'Citas del día de un médico, lista para imprimir'],
        'pacientes'  => ['titulo' => 'Pacientes registrados', 'grupo' => 'Pacientes',    'icono' => 'bi-person-vcard',
                         'todos' => true,  'excel' => true,  'tabla' => 'paciente',
                         'descripcion' => 'Pacientes por estado y fecha de registro'],
        'ingresos'   => ['titulo' => 'Ingresos',              'grupo' => 'Pagos y caja', 'icono' => 'bi-cash-coin',
                         'todos' => true,  'excel' => true,  'tabla' => 'pago',
                         'descripcion' => 'Pagos cobrados por período, médico, especialidad y método'],
        'cierres'    => ['titulo' => 'Cierres de caja',       'grupo' => 'Pagos y caja', 'icono' => 'bi-safe',
                         'todos' => true,  'excel' => true,  'tabla' => 'sesion_caja',
                         'descripcion' => 'Cajas cerradas con su monto esperado, contado y diferencia'],
    ];

    /** Campos de filtro que puede traer el formulario (se reenvían al exportar). */
    public const CAMPOS_FILTRO = ['periodo', 'fecha', 'mes', 'fecha_inicio', 'fecha_fin',
        'id_medico', 'id_especialidad', 'estado', 'id_metodo_pago', 'id_usuario'];

    // Mensajes exactos del detallar
    public const MSJ_FECHAS = 'La fecha de inicio no puede ser mayor que la fecha de fin';      // 6a
    public const MSJ_SIN_RESULTADOS = 'No se encontraron resultados para los filtros seleccionados';   // 6b

    private const PERMISO_TODOS = 'reportes.generar_todos';

    // ---------------------------------------------------------------
    // Pasos 1 a 6: mostrar reportes, filtros y resultado
    // ---------------------------------------------------------------

    /**
     * UI_Reportes, como un asistente de dos pasos (igual que "Registrar cita"):
     *  - sin "tipo": lista de reportes disponibles (pasos 1-2 y 2a)
     *  - con "tipo": Paso 1 "Filtros" del reporte (paso 4 / 3a-3e)
     *  - con "generar=1": valida y, si hay datos, abre el Paso 2 "Resultado" con totales, gráfico y detalle (paso 6).
     *    Con errores (6a) o sin datos (6b) se queda en los filtros con el mensaje (vuelve al paso 5).
     */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $completo = $usuario->tienePermiso(self::PERMISO_TODOS);
        $tipo = $request->query('tipo');

        $datosVista = [
            'reportes' => $this->reportesDisponibles($usuario),
            'tipo' => null,
            'completo' => $completo,
        ];

        if ($tipo === null) {
            return view('reportes.generar_reporte.index', $datosVista);
        }

        $this->autorizar($usuario, $tipo);

        $datosVista['tipo'] = $tipo;
        $datosVista['info'] = self::REPORTES[$tipo];
        $datosVista['valores'] = $this->valoresFiltro($request, $tipo);
        $datosVista['reporte'] = null;
        $errores = null;

        if ($request->boolean('generar')) {
            $validador = $this->validarFiltros($request, $tipo, $completo);

            if ($validador->fails()) {
                $errores = $validador;   // 6a y demás errores: mensajes en cada campo
            } else {
                $datosVista['reporte'] = $this->generar($tipo, $validador->validated(), $completo);

                if (! $datosVista['reporte']['vacio']) {
                    // Paso 2 "Resultado". Al exportar se reenvían SOLO los filtros validados de este reporte;
                    // "Atrás" vuelve a los filtros con esos mismos valores.
                    $datosVista['filtrosExportar'] = $validador->validated();
                    $datosVista['urlFiltros'] = route('reportes.index', ['tipo' => $tipo] + array_filter(
                        $datosVista['filtrosExportar'], fn ($v) => $v !== null && $v !== ''));

                    return view('reportes.generar_reporte.resultado', $datosVista);
                }
            }
        }

        // Paso 1 "Filtros" (también con 6a o 6b)
        $datosVista['listas'] = $this->obtenerFiltros($tipo, $completo);
        $vista = view('reportes.generar_reporte.index', $datosVista);

        return $errores ? $vista->withErrors($errores) : $vista;
    }

    // ---------------------------------------------------------------
    // Pasos 7 y 8: exportar
    // ---------------------------------------------------------------

    /**
     * exportarReporte(reporte, formato): vuelve a generar el reporte con los mismos filtros,
     * arma el archivo, registra "Exportar reporte" en la bitácora y lo descarga.
     */
    public function exportar(Request $request): Response|RedirectResponse
    {
        $usuario = $request->user();
        $completo = $usuario->tienePermiso(self::PERMISO_TODOS);
        $tipo = (string) $request->input('tipo');

        $this->autorizar($usuario, $tipo);

        // La agenda diaria solo se exporta a PDF
        $formatos = self::REPORTES[$tipo]['excel'] ? ['pdf', 'excel'] : ['pdf'];
        $formato = $request->input('formato');
        abort_unless(in_array($formato, $formatos, true), 404);

        // Si los filtros no son válidos o no hay datos (6a / 6b), se vuelve a la pantalla:
        // allí se muestra el mensaje y la exportación queda deshabilitada.
        $volver = redirect()->route('reportes.index',
            ['tipo' => $tipo, 'generar' => 1] + array_filter($request->only(self::CAMPOS_FILTRO), fn ($v) => $v !== null && $v !== ''));

        $validador = $this->validarFiltros($request, $tipo, $completo);
        if ($validador->fails()) {
            return $volver;
        }

        $reporte = $this->generar($tipo, $validador->validated(), $completo);
        if ($reporte['vacio']) {
            return $volver;
        }

        // generarArchivo(reporte, formato)
        if ($formato === 'pdf') {
            $contenido = ExportadorReporte::pdf($reporte, $usuario);
            $respuesta = response($contenido, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . ExportadorReporte::nombreArchivo($reporte, 'pdf') . '"',
            ]);
        } else {
            $ruta = ExportadorReporte::excel($reporte, $usuario);
            $respuesta = response()->download($ruta, ExportadorReporte::nombreArchivo($reporte, 'xlsx'), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend();
        }

        // registrar(usuario, "Exportar reporte", detalle, ip)
        Bitacora::registrar('EXPORTAR_REPORTE', self::REPORTES[$tipo]['tabla'], $this->detalleBitacora($reporte, $formato));

        return $respuesta;
    }

    // ---------------------------------------------------------------
    // Permisos y filtros
    // ---------------------------------------------------------------

    /** Paso 2 / 2a: reportes que el usuario puede generar, agrupados ['Citas' => ['citas' => [...], ...], ...]. */
    private function reportesDisponibles(Usuario $usuario): array
    {
        $grupos = [];
        foreach (self::REPORTES as $clave => $reporte) {
            if (! $reporte['todos'] || $usuario->tienePermiso(self::PERMISO_TODOS)) {
                $grupos[$reporte['grupo']][$clave] = $reporte;
            }
        }

        return $grupos;
    }

    /** Validación en el servidor (no basta con ocultar el botón): reporte existente y permitido para el rol. */
    private function autorizar(Usuario $usuario, string $tipo): void
    {
        abort_unless(isset(self::REPORTES[$tipo]), 404);

        if (self::REPORTES[$tipo]['todos'] && ! $usuario->tienePermiso(self::PERMISO_TODOS)) {
            abort(403, 'No tiene permiso para generar este reporte.');
        }
    }

    /**
     * Listas para los selectores de filtro (obtenerFiltrosCitas, obtenerMedicos, obtenerFiltrosIngresos,
     * obtenerRecepcionistas). Solo se cargan las que usa el reporte elegido.
     */
    private function obtenerFiltros(string $tipo, bool $completo): array
    {
        return match ($tipo) {
            'citas' => [
                'estados' => Cita::ESTADOS,
                // 2a: el recepcionista no filtra por médico ni especialidad
                'medicos' => $completo ? Medico::listarMedicos() : collect(),
                'especialidades' => $completo ? Especialidad::listarEspecialidades() : collect(),
            ],
            'agenda' => ['medicos' => Medico::listarMedicos()],
            'ingresos' => [
                'medicos' => Medico::listarMedicos(),
                'especialidades' => Especialidad::listarEspecialidades(),
                'metodos' => MetodoPago::listarMetodos(),
            ],
            'cierres' => ['recepcionistas' => Usuario::listarRecepcionistas()],
            default => [],
        };
    }

    /** Valores que se muestran en los filtros: lo que envió el usuario o, al abrir, valores por defecto. */
    private function valoresFiltro(Request $request, string $tipo): array
    {
        $valores = $request->only(self::CAMPOS_FILTRO);

        if ($tipo === 'pacientes') {
            return $valores;   // sin período: el rango de fechas de registro es opcional
        }

        if ($tipo === 'agenda') {
            return $valores + ['periodo' => 'diario', 'fecha' => now()->toDateString()];
        }

        return $valores + [
            'periodo' => 'mensual',
            'mes' => now()->format('Y-m'),
            'fecha' => now()->toDateString(),
        ];
    }

    /** Reglas de cada reporte + validarRangoFechas (6a). */
    private function validarFiltros(Request $request, string $tipo, bool $completo): \Illuminate\Validation\Validator
    {
        $periodo = [
            'periodo' => ['required', Rule::in(array_keys(Periodo::TIPOS))],
            'fecha' => ['nullable', 'required_if:periodo,diario,semanal', 'date_format:Y-m-d'],
            'mes' => ['nullable', 'required_if:periodo,mensual', 'date_format:Y-m'],
            'fecha_inicio' => ['nullable', 'required_if:periodo,rango', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'required_if:periodo,rango', 'date_format:Y-m-d'],
        ];
        $medico = ['id_medico' => ['nullable', 'integer', 'exists:medico,id_medico']];
        $especialidad = ['id_especialidad' => ['nullable', 'integer', 'exists:especialidad,id_especialidad']];

        $reglas = match ($tipo) {
            'citas' => $periodo + ['estado' => ['nullable', Rule::in(Cita::ESTADOS)]]
                + ($completo ? $medico + $especialidad : []),
            'asistencia' => $periodo,
            'agenda' => [
                'periodo' => ['nullable', Rule::in(['diario', 'semanal'])],   // sin período = diario
                'fecha' => ['required', 'date_format:Y-m-d'],
                'id_medico' => ['required', 'integer', 'exists:medico,id_medico'],
            ],
            'pacientes' => [
                'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
                'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
                'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
            ],
            'ingresos' => $periodo + $medico + $especialidad
                + ['id_metodo_pago' => ['nullable', 'integer', 'exists:metodo_pago,id_metodo_pago']],
            'cierres' => $periodo + ['id_usuario' => ['nullable', 'integer', 'exists:usuario,id_usuario']],
        };

        $mensajes = [
            'periodo.required' => 'Seleccione el período',
            'fecha.required' => 'Seleccione la fecha',
            'fecha.required_if' => 'Seleccione la fecha',
            'mes.required_if' => 'Seleccione el mes',
            'fecha_inicio.required_if' => 'Ingrese la fecha de inicio',
            'fecha_fin.required_if' => 'Ingrese la fecha de fin',
            'id_medico.required' => 'Seleccione el médico',
            '*.date_format' => 'La fecha no es válida',
            '*.integer' => 'La opción elegida no es válida',
            '*.in' => 'La opción elegida no es válida',
            '*.exists' => 'La opción elegida no existe',
        ];

        // Solo se validan los campos de este reporte; los demás se ignoran
        // (así el recepcionista no puede colar un filtro de médico o especialidad).
        $validador = Validator::make($request->only(array_keys($reglas)), $reglas, $mensajes);

        // validarRangoFechas(fechaIni, fechaFin) - alternativo 6a
        $validador->after(function ($v) use ($request, $tipo) {
            if ($tipo !== 'pacientes' && $request->input('periodo') !== 'rango') {
                return;   // diario, semanal y mensual siempre dan un rango correcto
            }
            $inicio = $request->input('fecha_inicio');
            $fin = $request->input('fecha_fin');
            $esFecha = fn ($f) => is_string($f) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f);
            if ($esFecha($inicio) && $esFecha($fin) && $inicio > $fin) {
                $v->errors()->add('fecha_inicio', self::MSJ_FECHAS);
            }
        });

        return $validador;
    }

    // ---------------------------------------------------------------
    // Generar cada reporte (paso 6 y alternativos 3a-3e)
    //
    // totales: lista de ['etiqueta', 'valor' (número), 'tipo' => numero|moneda, 'estado' (opcional)].
    //          Los que tienen "estado" se muestran en pantalla como etiquetas de color, no como tarjetas.
    // grafico: null o ['tipo' => barras|barras_apiladas|area, 'titulo', 'etiquetas', 'series', 'moneda'].
    //          Solo se dibuja en pantalla (el PDF no ejecuta JavaScript).
    // ---------------------------------------------------------------

    private function generar(string $tipo, array $datos, bool $completo): array
    {
        $reporte = match ($tipo) {
            'citas' => $this->generarReporteCitas($datos, $completo),
            'asistencia' => $this->generarReporteAsistencia($datos),
            'agenda' => $this->generarAgendaDiaria($datos['fecha'], (int) $datos['id_medico'], $datos['periodo'] ?? 'diario'),
            'pacientes' => $this->generarReportePacientes($datos['estado'] ?? null, $datos['fecha_inicio'] ?? null, $datos['fecha_fin'] ?? null),
            'ingresos' => $this->generarReporteIngresos($datos),
            'cierres' => $this->generarReporteCierres($datos),
        };

        return $reporte + [
            'tipo' => $tipo,
            'titulo' => self::REPORTES[$tipo]['titulo'],
            'pie' => null,
            'grafico' => null,
            'orientacion' => 'portrait',
            'vacio' => count($reporte['filas']) === 0,   // 6b
        ];
    }

    /** Color de Bootstrap del badge de un estado (citas y pacientes). */
    public static function colorEstado(?string $estado): string
    {
        return Cita::COLOR_ESTADO[$estado] ?? match ($estado) {
            'Activo' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Valores de un gráfico a lo largo del período: un punto por día, o por mes si el período pasa de 62 días.
     * $porFecha: ['Y-m-d' => [serie => valor]]. Los días sin datos quedan en 0 (el eje de tiempo no salta días).
     */
    private function serieTemporal(Carbon $inicio, Carbon $fin, array $porFecha, array $nombresSeries): array
    {
        $porMes = $inicio->diffInDays($fin, true) > 62;
        $conAnio = $inicio->year !== $fin->year;
        $grupos = [];

        for ($dia = $inicio->copy(); $dia->lte($fin); $dia->addDay()) {
            $clave = $porMes ? $dia->format('Y-m') : $dia->toDateString();
            $grupos[$clave] ??= [
                'etiqueta' => $porMes ? Periodo::etiquetaMes($dia) : Periodo::etiquetaCorta($dia, $conAnio),
                'valores' => array_fill_keys($nombresSeries, 0),
            ];
            foreach ($porFecha[$dia->toDateString()] ?? [] as $serie => $valor) {
                $grupos[$clave]['valores'][$serie] += $valor;
            }
        }

        return [
            'etiquetas' => array_column($grupos, 'etiqueta'),
            'series' => array_map(fn ($serie) => [
                'nombre' => $serie,
                'valores' => array_map(fn ($g) => round($g['valores'][$serie], 2), array_values($grupos)),
            ], $nombresSeries),
        ];
    }

    /** Paso 6: total de citas, total por estado y detalle (fecha, hora, paciente, médico, especialidad y estado). */
    private function generarReporteCitas(array $datos, bool $completo): array
    {
        [$inicio, $fin] = Periodo::resolver($datos);
        $idMedico = $completo ? ($datos['id_medico'] ?? null) : null;
        $idEspecialidad = $completo ? ($datos['id_especialidad'] ?? null) : null;
        $estado = $datos['estado'] ?? null;

        $citas = Cita::obtenerCitas($inicio->toDateString(), $fin->toDateString(),
            $idMedico ? (int) $idMedico : null, $idEspecialidad ? (int) $idEspecialidad : null, $estado);

        $filtros = ['Período' => Periodo::describir($datos)];
        if ($completo) {
            $filtros['Médico'] = $idMedico ? Medico::with('usuario')->find($idMedico)?->nombre_completo : 'Todos';
            $filtros['Especialidad'] = $idEspecialidad ? Especialidad::find($idEspecialidad)?->nombre : 'Todas';
        }
        $filtros['Estado'] = $estado ?? 'Todos';

        // Gráfico: un solo día → citas por estado; varios días → citas por día
        if ($inicio->eq($fin)) {
            $porEstado = $citas->countBy('estado');
            $estados = $estado ? [$estado] : Cita::ESTADOS;
            $grafico = ['tipo' => 'barras', 'titulo' => 'Citas por estado', 'etiquetas' => $estados,
                'series' => [['nombre' => 'Citas', 'valores' => array_map(fn ($e) => $porEstado[$e] ?? 0, $estados)]]];
        } else {
            $porFecha = $citas->groupBy(fn ($c) => $c->fecha->toDateString())->map(fn ($g) => ['Citas' => $g->count()])->all();
            $grafico = ['tipo' => 'barras', 'titulo' => 'Citas por día'] + $this->serieTemporal($inicio, $fin, $porFecha, ['Citas']);
        }

        return [
            'filtros' => $filtros,
            'totales' => $this->calcularTotales($citas, $estado ? [$estado] : Cita::ESTADOS, 'Total de citas'),
            'grafico' => $grafico,
            'columnas' => [
                ['titulo' => 'Fecha', 'tipo' => 'texto'],
                ['titulo' => 'Hora', 'tipo' => 'texto'],
                ['titulo' => 'Paciente', 'tipo' => 'texto'],
                ['titulo' => 'Médico', 'tipo' => 'texto'],
                ['titulo' => 'Especialidad', 'tipo' => 'texto'],
                ['titulo' => 'Estado', 'tipo' => 'estado'],
            ],
            'filas' => $citas->map(fn (Cita $c) => [
                $c->fecha->format('d/m/Y'),
                substr($c->hora_inicio, 0, 5),
                $c->paciente?->nombre_completo,
                $c->medico?->nombre_completo,
                $c->especialidad?->nombre,
                $c->estado,
            ])->all(),
        ];
    }

    /** calcularTotales(citas): total de citas y cuántas hay en cada estado (los estados van como etiquetas de color). */
    private function calcularTotales($citas, array $estados, string $etiquetaTotal): array
    {
        $totales = [['etiqueta' => $etiquetaTotal, 'valor' => $citas->count(), 'tipo' => 'numero']];
        $porEstado = $citas->countBy('estado');

        foreach ($estados as $e) {
            $totales[] = ['etiqueta' => $e, 'valor' => $porEstado[$e] ?? 0, 'tipo' => 'numero', 'estado' => $e];
        }

        return $totales;
    }

    /** 3a: por día, citas atendidas, canceladas e inasistencias, con sus totales. */
    private function generarReporteAsistencia(array $datos): array
    {
        [$inicio, $fin] = Periodo::resolver($datos);
        $dias = Cita::contarCitasPorEstado($inicio->toDateString(), $fin->toDateString());

        $atendidas = (int) $dias->sum('atendidas');
        $canceladas = (int) $dias->sum('canceladas');
        $inasistencias = (int) $dias->sum('inasistencias');
        $total = $atendidas + $canceladas + $inasistencias;
        $conAnio = $inicio->year !== $fin->year;

        $porFecha = [];
        foreach ($dias as $d) {
            $porFecha[Carbon::parse($d->fecha)->toDateString()] = [
                'Atendidas' => (int) $d->atendidas, 'Canceladas' => (int) $d->canceladas, 'Inasistencias' => (int) $d->inasistencias,
            ];
        }

        return [
            'filtros' => ['Período' => Periodo::describir($datos)],
            'totales' => [
                ['etiqueta' => 'Total', 'valor' => $total, 'tipo' => 'numero'],
                ['etiqueta' => 'Atendidas', 'valor' => $atendidas, 'tipo' => 'numero'],
                ['etiqueta' => 'Canceladas', 'valor' => $canceladas, 'tipo' => 'numero'],
                ['etiqueta' => 'Inasistencias', 'valor' => $inasistencias, 'tipo' => 'numero'],
            ],
            'grafico' => ['tipo' => 'barras_apiladas', 'titulo' => 'Asistencia por día']
                + $this->serieTemporal($inicio, $fin, $porFecha, ['Atendidas', 'Canceladas', 'Inasistencias']),
            'columnas' => [
                ['titulo' => 'Fecha', 'tipo' => 'texto'],
                ['titulo' => 'Atendidas', 'tipo' => 'numero'],
                ['titulo' => 'Canceladas', 'tipo' => 'numero'],
                ['titulo' => 'Inasistencias', 'tipo' => 'numero'],
                ['titulo' => 'Total', 'tipo' => 'numero'],
            ],
            'filas' => $dias->map(fn ($d) => [
                Periodo::etiquetaCorta(Carbon::parse($d->fecha), $conAnio),
                (int) $d->atendidas,
                (int) $d->canceladas,
                (int) $d->inasistencias,
                (int) $d->atendidas + (int) $d->canceladas + (int) $d->inasistencias,
            ])->all(),
            'pie' => ['Total', $atendidas, $canceladas, $inasistencias, $total],
        ];
    }

    /**
     * 3b: citas de un médico ordenadas por hora (hora, paciente, CI, especialidad y estado).
     * Diario = ese día. Semanal = de lunes a domingo de esa fecha, ordenadas por día y hora (con la columna Fecha).
     */
    private function generarAgendaDiaria(string $fecha, int $idMedico, string $periodo = 'diario'): array
    {
        $semanal = $periodo === 'semanal';
        [$inicio, $fin] = Periodo::resolver(['periodo' => $semanal ? 'semanal' : 'diario', 'fecha' => $fecha]);
        $citas = Cita::obtenerAgenda($inicio->toDateString(), $fin->toDateString(), $idMedico);
        $medico = Medico::with('usuario')->find($idMedico);
        // Solo los estados que aparecen (las canceladas no se muestran)
        $estados = array_values(array_filter(Cita::ESTADOS, fn ($e) => $citas->contains('estado', $e)));
        $conAnio = $inicio->year !== $fin->year;

        $columnas = [
            ['titulo' => 'Hora', 'tipo' => 'texto'],
            ['titulo' => 'Paciente', 'tipo' => 'texto'],
            ['titulo' => 'CI', 'tipo' => 'texto'],
            ['titulo' => 'Especialidad', 'tipo' => 'texto'],
            ['titulo' => 'Estado', 'tipo' => 'estado'],
        ];
        if ($semanal) {
            array_unshift($columnas, ['titulo' => 'Día', 'tipo' => 'texto']);
        }

        return [
            'titulo' => $semanal ? 'Agenda semanal' : 'Agenda diaria',
            'filtros' => [
                ($semanal ? 'Semana' : 'Fecha') => $semanal
                    ? 'Del ' . Periodo::diaConNombre($inicio->toDateString()) . ' al ' . Periodo::diaConNombre($fin->toDateString())
                    : Periodo::diaConNombre($fecha),
                'Médico' => $medico?->nombre_completo . ' (' . $medico?->codigo . ')',
            ],
            'totales' => $this->calcularTotales($citas, $estados, $semanal ? 'Citas de la semana' : 'Citas del día'),
            'columnas' => $columnas,
            'filas' => $citas->map(fn (Cita $c) => array_merge(
                $semanal ? [Periodo::etiquetaCorta($c->fecha, $conAnio)] : [],
                [
                    substr($c->hora_inicio, 0, 5) . ' – ' . substr($c->hora_fin, 0, 5),
                    $c->paciente?->nombre_completo,
                    $c->paciente?->ci,
                    $c->especialidad?->nombre,
                    $c->estado,
                ]
            ))->all(),
        ];
    }

    /** 3c: total de pacientes, activos e inactivos, y listado (nombre, CI, teléfono, fecha de registro y estado). */
    private function generarReportePacientes(?string $estado, ?string $fechaIni, ?string $fechaFin): array
    {
        $pacientes = Paciente::obtenerPacientes($estado, $fechaIni, $fechaFin);
        $activos = $pacientes->where('activo', true)->count();

        $registro = match (true) {
            $fechaIni && $fechaFin => 'Del ' . date('d/m/Y', strtotime($fechaIni)) . ' al ' . date('d/m/Y', strtotime($fechaFin)),
            (bool) $fechaIni => 'Desde el ' . date('d/m/Y', strtotime($fechaIni)),
            (bool) $fechaFin => 'Hasta el ' . date('d/m/Y', strtotime($fechaFin)),
            default => 'Todas',
        };

        return [
            'filtros' => [
                'Estado' => match ($estado) { 'activo' => 'Activos', 'inactivo' => 'Inactivos', default => 'Todos' },
                'Fecha de registro' => $registro,
            ],
            'totales' => [
                ['etiqueta' => 'Total de pacientes', 'valor' => $pacientes->count(), 'tipo' => 'numero'],
                ['etiqueta' => 'Activos', 'valor' => $activos, 'tipo' => 'numero'],
                ['etiqueta' => 'Inactivos', 'valor' => $pacientes->count() - $activos, 'tipo' => 'numero'],
            ],
            'columnas' => [
                ['titulo' => 'Nombre', 'tipo' => 'texto'],
                ['titulo' => 'CI', 'tipo' => 'texto'],
                ['titulo' => 'Teléfono', 'tipo' => 'texto'],
                ['titulo' => 'Fecha de registro', 'tipo' => 'texto'],
                ['titulo' => 'Estado', 'tipo' => 'estado'],
            ],
            'filas' => $pacientes->map(fn (Paciente $p) => [
                $p->usuario?->apellidos . ', ' . $p->usuario?->nombres,
                $p->ci,
                $p->telefono,
                $p->usuario?->fecha_registro?->format('d/m/Y'),
                $p->activo ? 'Activo' : 'Inactivo',
            ])->all(),
        ];
    }

    /**
     * 3d: total cobrado, total por método, número de pagos y detalle.
     * Una fila por cada parte aprobada del pago (un pago combinado sale en dos filas: efectivo y en línea).
     * "Quién cobró": el usuario que registró el cobro, o "En línea" si la parte se pagó con un método en línea.
     */
    private function generarReporteIngresos(array $datos): array
    {
        [$inicio, $fin] = Periodo::resolver($datos);
        $idMedico = isset($datos['id_medico']) ? (int) $datos['id_medico'] : null;
        $idEspecialidad = isset($datos['id_especialidad']) ? (int) $datos['id_especialidad'] : null;
        $idMetodo = isset($datos['id_metodo_pago']) ? (int) $datos['id_metodo_pago'] : null;
        $ini = $inicio->toDateString();
        $fi = $fin->toDateString();

        $pagos = Pago::obtenerPagos($ini, $fi, $idMedico, $idEspecialidad, $idMetodo);
        $porMetodo = DetallePago::obtenerTotalesPorMetodo($ini, $fi, $idMedico, $idEspecialidad, $idMetodo);

        $filas = [];
        $porFecha = [];
        $totalCentavos = 0;
        foreach ($pagos as $pago) {
            foreach ($pago->detalles as $detalle) {
                $totalCentavos += Pago::aCentavos($detalle->monto);
                $dia = $pago->fecha->toDateString();
                $porFecha[$dia]['Total cobrado'] = ($porFecha[$dia]['Total cobrado'] ?? 0) + (float) $detalle->monto;
                $filas[] = [
                    $pago->fecha->format('d/m/Y H:i'),
                    $pago->cita?->paciente?->nombre_completo,
                    $pago->cita?->especialidad?->nombre,
                    $detalle->metodoPago?->nombre,
                    (float) $detalle->monto,
                    $detalle->metodoPago?->en_linea ? 'En línea' : $pago->usuario?->nombre_completo,
                ];
            }
        }
        $total = (float) Pago::deCentavos($totalCentavos);

        $totales = [
            ['etiqueta' => 'Total cobrado', 'valor' => $total, 'tipo' => 'moneda'],
            ['etiqueta' => 'Número de pagos', 'valor' => $pagos->count(), 'tipo' => 'numero'],
        ];
        foreach ($porMetodo as $m) {
            $totales[] = ['etiqueta' => $m->metodo, 'valor' => (float) $m->total, 'tipo' => 'moneda'];
        }

        // Gráfico: un solo día → total por método; varios días → total cobrado por día
        $grafico = $inicio->eq($fin)
            ? ['tipo' => 'barras', 'titulo' => 'Total cobrado por método', 'moneda' => true,
               'etiquetas' => $porMetodo->pluck('metodo')->all(),
               'series' => [['nombre' => 'Total cobrado', 'valores' => $porMetodo->map(fn ($m) => (float) $m->total)->all()]]]
            : ['tipo' => 'area', 'titulo' => 'Total cobrado por día', 'moneda' => true]
                + $this->serieTemporal($inicio, $fin, $porFecha, ['Total cobrado']);

        return [
            'filtros' => [
                'Período' => Periodo::describir($datos),
                'Médico' => $idMedico ? Medico::with('usuario')->find($idMedico)?->nombre_completo : 'Todos',
                'Especialidad' => $idEspecialidad ? Especialidad::find($idEspecialidad)?->nombre : 'Todas',
                'Método de pago' => $idMetodo ? MetodoPago::find($idMetodo)?->nombre : 'Todos',
            ],
            'totales' => $totales,
            'grafico' => $grafico,
            'columnas' => [
                ['titulo' => 'Fecha', 'tipo' => 'texto'],
                ['titulo' => 'Paciente', 'tipo' => 'texto'],
                ['titulo' => 'Especialidad', 'tipo' => 'texto'],
                ['titulo' => 'Método', 'tipo' => 'texto'],
                ['titulo' => 'Monto', 'tipo' => 'moneda'],
                ['titulo' => 'Cobró', 'tipo' => 'texto'],
            ],
            'filas' => $filas,
            'pie' => ['Total', null, null, null, $total, null],
        ];
    }

    /**
     * 3e: cada caja cerrada (fecha, recepcionista, monto de apertura, esperado, contado y diferencia), con totales.
     * Monto esperado = monto inicial + ingresos en efectivo de la caja (igual que CU22).
     */
    private function generarReporteCierres(array $datos): array
    {
        [$inicio, $fin] = Periodo::resolver($datos);
        $idUsuario = isset($datos['id_usuario']) ? (int) $datos['id_usuario'] : null;
        $sesiones = SesionCaja::obtenerSesionesCerradas($inicio->toDateString(), $fin->toDateString(), $idUsuario);

        // Sumas en centavos para evitar errores de redondeo
        $suma = ['inicial' => 0, 'esperado' => 0, 'contado' => 0, 'diferencia' => 0];
        $filas = [];
        foreach ($sesiones as $sesion) {
            $esperado = $sesion->montoEsperadoCentavos();
            $suma['inicial'] += Pago::aCentavos($sesion->monto_inicial);
            $suma['esperado'] += $esperado;
            $suma['contado'] += Pago::aCentavos($sesion->monto_contado);
            $suma['diferencia'] += Pago::aCentavos($sesion->diferencia);

            $filas[] = [
                $sesion->apertura->format('d/m/Y'),
                $sesion->usuario?->nombre_completo,
                (float) $sesion->monto_inicial,
                (float) Pago::deCentavos($esperado),
                (float) $sesion->monto_contado,
                (float) $sesion->diferencia,
            ];
        }
        $bs = fn (int $centavos) => (float) Pago::deCentavos($centavos);

        return [
            'filtros' => [
                'Período' => Periodo::describir($datos),
                'Recepcionista' => $idUsuario ? Usuario::find($idUsuario)?->nombre_completo : 'Todos',
            ],
            'totales' => [
                ['etiqueta' => 'Cajas cerradas', 'valor' => $sesiones->count(), 'tipo' => 'numero'],
                ['etiqueta' => 'Total esperado', 'valor' => $bs($suma['esperado']), 'tipo' => 'moneda'],
                ['etiqueta' => 'Total contado', 'valor' => $bs($suma['contado']), 'tipo' => 'moneda'],
                ['etiqueta' => 'Diferencia total', 'valor' => $bs($suma['diferencia']), 'tipo' => 'moneda'],
            ],
            'columnas' => [
                ['titulo' => 'Fecha', 'tipo' => 'texto'],
                ['titulo' => 'Recepcionista', 'tipo' => 'texto'],
                ['titulo' => 'Apertura', 'tipo' => 'moneda'],
                ['titulo' => 'Esperado', 'tipo' => 'moneda'],
                ['titulo' => 'Contado', 'tipo' => 'moneda'],
                ['titulo' => 'Diferencia', 'tipo' => 'moneda'],
            ],
            'filas' => $filas,
            'pie' => ['Total', null, $bs($suma['inicial']), $bs($suma['esperado']), $bs($suma['contado']), $bs($suma['diferencia'])],
        ];
    }

    /** Detalle para la bitácora: "Reporte de citas (PDF) · Período: ... · Estado: Todos". */
    private function detalleBitacora(array $reporte, string $formato): string
    {
        $partes = [$reporte['titulo'] . ' (' . ($formato === 'pdf' ? 'PDF' : 'Excel') . ')'];
        foreach ($reporte['filtros'] as $filtro => $valor) {
            $partes[] = $filtro . ': ' . $valor;
        }

        return implode(' · ', $partes);
    }
}
