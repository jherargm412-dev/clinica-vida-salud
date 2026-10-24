<?php

namespace App\Modulos\usuarios_acceso\controller\consultar_bitacora;

use App\Http\Controllers\Controller;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * CU05 Consultar Bitácora (Ctrl_Bitacora en los diagramas). Solo lectura.
 * Vista: resources/views/usuarios_acceso/consultar_bitacora/index.blade.php (UI_Bitacora; el detalle es un modal)
 */
class BitacoraController extends Controller
{
    private const POR_PAGINA = 20;

    // Secuencia: obtener registros (más recientes primero) / buscar registros con filtros
    public function index(Request $request): View
    {
        $filtros = $request->only(['desde', 'hasta', 'usuario', 'accion', 'tabla']);

        // Primera vez que se abre la bitácora (sin ningún filtro en la URL): Desde y Hasta = hoy.
        // "Limpiar filtros" envía las fechas vacías a propósito, así se ven todos los registros.
        if (! $request->has('desde') && ! $request->has('hasta')) {
            $filtros['desde'] = $filtros['hasta'] = now()->toDateString();
        }

        // Paso 4: validar las fechas. Se valida aquí (y no con un Request) porque es una búsqueda GET:
        // si falla, se muestra el mensaje en la misma página en lugar de redirigir.
        $validador = Validator::make($filtros, [
            'desde'   => ['nullable', 'date'],
            'hasta'   => ['nullable', 'date', 'after_or_equal:desde'],
            'usuario' => ['nullable', 'regex:/^(\d+|ninguno)$/'],
            'accion'  => ['nullable', 'string', 'max:50'],
            'tabla'   => ['nullable', 'string', 'max:50'],
        ], [
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la fecha inicial',   // 4a
            'date'                 => 'Ingrese una fecha válida',
            'usuario.regex'        => 'Seleccione un usuario válido',
        ]);

        if ($validador->fails()) {
            $error = $validador->errors()->first();
            $registros = new LengthAwarePaginator([], 0, self::POR_PAGINA);   // no se busca nada
        } else {
            $error = null;
            $registros = Bitacora::with('usuario')
                ->when($filtros['desde'] ?? null, fn ($q, $desde) => $q->whereDate('fecha_hora', '>=', $desde))
                ->when($filtros['hasta'] ?? null, fn ($q, $hasta) => $q->whereDate('fecha_hora', '<=', $hasta))
                ->when(($filtros['usuario'] ?? null) === 'ninguno', fn ($q) => $q->whereNull('id_usuario'))
                ->when(ctype_digit((string) ($filtros['usuario'] ?? '')), fn ($q) => $q->where('id_usuario', (int) $filtros['usuario']))
                ->when($filtros['accion'] ?? null, fn ($q, $accion) => $q->where('accion', $accion))
                ->when($filtros['tabla'] ?? null, fn ($q, $tabla) => $q->where('tabla_afectada', $tabla))
                ->orderByDesc('fecha_hora')
                ->orderByDesc('id_bitacora')
                ->paginate(self::POR_PAGINA)
                ->withQueryString();
        }

        return view('usuarios_acceso.consultar_bitacora.index', [
            'registros' => $registros,
            'filtros'   => $filtros,
            'error'     => $error,
            'hayFiltros' => count(array_filter($filtros, fn ($v) => $v !== null && $v !== '')) > 0,
            // Listas de los filtros
            'usuarios'  => Usuario::orderBy('apellidos')->orderBy('nombres')->get(['id_usuario', 'nombres', 'apellidos', 'nombre_usuario']),
            'acciones'  => Bitacora::tiposDeAccion(),
            'tablas'    => Bitacora::tablasAfectadas(),
        ]);
    }
}
