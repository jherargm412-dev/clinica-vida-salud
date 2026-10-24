<?php
namespace App\Modulos\medicos_especialidades\controller\gestionar_especialidad;

use App\Http\Controllers\Controller;
use App\Modulos\medicos_especialidades\models\Especialidad;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Modulos\medicos_especialidades\request\gestionar_especialidad\EspecialidadRequest;
use App\Modulos\usuarios_acceso\models\Bitacora;
use Illuminate\Http\RedirectResponse;

/** CU09 Gestionar Especialidad (Ctrl_Especialidades en los diagramas) */
class EspecialidadController extends Controller
{
    // Secuencia 1 (Listar) y 4 (Consultar)
    public function index(Request $request): View
    {
        $texto = $request->query('buscar');
        // Filtro Estado: 'activo' | 'inactivo' | null (Todos). Si llega otro valor, se ignora.
        $estado = in_array($request->query('estado'), ['activo', 'inactivo'], true) ? $request->query('estado') : null;

        $especialidades = Especialidad::query()
            ->when($texto, fn ($q) => $q->where('nombre', 'ILIKE', "%{$texto}%"))
            ->when($estado, fn ($q) => $q->where('activo', $estado === 'activo'))
            ->orderBy('nombre')
            ->get();

        return view('medicos_especialidades.gestionar_especialidad.index', [
            'especialidades' => $especialidades,
            'texto' => $texto,
            'estado' => $estado,
        ]);
    }

    // Secuencia 2.1: mostrar formulario vacío
    public function crear(): View
    {
        return view('medicos_especialidades.gestionar_especialidad.formulario', [
            'especialidad' => new Especialidad(),
        ]);
    }

    // Secuencia 2.3 a 2.14: registrar especialidad
    public function guardar(EspecialidadRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        if (Especialidad::existeNombre($datos['nombre'])) {
            return back()
                ->withErrors(['nombre' => 'Ya existe una especialidad con ese nombre'])
                ->withInput();
        }

        $especialidad = Especialidad::create($datos);

        Bitacora::registrar('REGISTRAR_ESPECIALIDAD', 'especialidad', 'Especialidad: ' . $especialidad->nombre);

        return redirect()->route('especialidades.index')->with('ok', 'Especialidad registrada');
    }

    // Secuencia 3.1 a 3.5: mostrar formulario con los datos de la especialidad
    // Laravel busca solo la especialidad por el {especialidad} de la URL (3.2 buscarPorId)
    public function editar(Especialidad $especialidad): View
    {
        return view('medicos_especialidades.gestionar_especialidad.formulario', [
            'especialidad' => $especialidad,
        ]);
    }

    // Secuencia 3.7 a 3.19: modificar especialidad
    public function actualizar(EspecialidadRequest $request, Especialidad $especialidad): RedirectResponse
    {
        $datos = $request->validated();

        // Se pasa su propio id para no compararse consigo misma (3.11 existeNombreEnOtra)
        if (Especialidad::existeNombre($datos['nombre'], $especialidad->id_especialidad)) {
            return back()
                ->withErrors(['nombre' => 'Ya existe una especialidad con ese nombre'])
                ->withInput();
        }

        $especialidad->update($datos);

        Bitacora::registrar('MODIFICAR_ESPECIALIDAD', 'especialidad', 'Especialidad: ' . $especialidad->nombre);

        return redirect()->route('especialidades.index')->with('ok', 'Especialidad actualizada');
    }

    // Secuencia 5.3 a 5.9: desactivar especialidad (la confirmación 5.1 la hace la vista)
    // Los médicos y citas que ya la tienen se conservan; solo deja de ofrecerse.
    public function desactivar(Especialidad $especialidad): RedirectResponse
    {
        $especialidad->update(['activo' => false]);

        Bitacora::registrar('DESACTIVAR_ESPECIALIDAD', 'especialidad', 'Especialidad: ' . $especialidad->nombre);

        return redirect()->route('especialidades.index')->with('ok', 'Especialidad desactivada');
    }

    // Secuencia 6.1 a 6.7: activar especialidad
    public function activar(Especialidad $especialidad): RedirectResponse
    {
        $especialidad->update(['activo' => true]);

        Bitacora::registrar('ACTIVAR_ESPECIALIDAD', 'especialidad', 'Especialidad: ' . $especialidad->nombre);

        return redirect()->route('especialidades.index')->with('ok', 'Especialidad activada');
    }
}