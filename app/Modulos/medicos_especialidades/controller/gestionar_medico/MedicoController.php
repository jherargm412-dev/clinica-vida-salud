<?php

namespace App\Modulos\medicos_especialidades\controller\gestionar_medico;

use App\Http\Controllers\Controller;
use App\Modulos\compartido\Texto;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\medicos_especialidades\models\Medico;
use App\Modulos\medicos_especialidades\request\gestionar_medico\MedicoRequest;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CU10 Gestionar Médico (Ctrl_Medicos en los diagramas)
 * Vistas: resources/views/medicos_especialidades/gestionar_medico/{index, formulario}.blade.php (UI_Medicos)
 */
class MedicoController extends Controller
{
    // Secuencia Listar y Consultar: busca por nombre, especialidad, código o registro (sin mayúsculas ni tildes)
    public function index(Request $request): View
    {
        $texto = $request->query('buscar');
        // Filtros: Especialidad (id) y Estado ('activo' | 'inactivo'); vacío = Todos
        $idEspecialidad = $request->integer('especialidad') ?: null;
        $estado = in_array($request->query('estado'), ['activo', 'inactivo'], true) ? $request->query('estado') : null;

        $medicos = Medico::with(['usuario', 'especialidades'])
            ->when($texto, function ($q) use ($texto) {
                $buscado = '%' . Texto::sinTildes(trim($texto)) . '%';
                $q->where(function ($q) use ($buscado) {
                    $q->whereHas('usuario', fn ($u) => $u->whereRaw(
                            Texto::sqlSinTildes("nombres || ' ' || apellidos") . ' ILIKE ?', [$buscado]))
                      ->orWhereHas('especialidades', fn ($e) => $e->whereRaw(
                            Texto::sqlSinTildes('especialidad.nombre') . ' ILIKE ?', [$buscado]))
                      ->orWhere('codigo', 'ILIKE', $buscado)
                      ->orWhere('registro_profesional', 'ILIKE', $buscado);
                });
            })
            ->when($idEspecialidad, fn ($q) => $q->whereHas('especialidades',
                fn ($e) => $e->where('especialidad.id_especialidad', $idEspecialidad)))
            ->when($estado, fn ($q) => $q->where('activo', $estado === 'activo'))
            ->orderBy('codigo')
            ->paginate(15)
            ->withQueryString();

        return view('medicos_especialidades.gestionar_medico.index', [
            'medicos' => $medicos,
            'texto' => $texto,
            'especialidades' => Especialidad::orderBy('nombre')->get(),   // lista del filtro (incluye inactivas)
            'idEspecialidad' => $idEspecialidad,
            'estado' => $estado,
        ]);
    }

    // Secuencia Registrar 2.1 a 2.9: obtener especialidades activas y cuentas sin vincular, mostrar formulario
    public function crear(): View
    {
        return view('medicos_especialidades.gestionar_medico.formulario', [
            'medico' => new Medico(),
            'especialidades' => Especialidad::paraAsignar(),
            'cuentas' => Usuario::medicosSinVincular(),
            'marcadas' => [],
        ]);
    }

    // Secuencia Registrar 2.10 a 2.16: registrar médico
    public function guardar(MedicoRequest $request): RedirectResponse
    {
        $datos = $request->validated();     // 6a, 6b y 6d ya validados

        if (Medico::existeRegistro($datos['registro_profesional'])) {        // 6c
            return back()
                ->withErrors(['registro_profesional' => 'El número de registro profesional ya está registrado'])
                ->withInput();
        }

        // Médico + especialidades en una sola transacción: se guardan las dos cosas o ninguna
        $medico = DB::transaction(function () use ($datos) {
            $medico = Medico::create([
                'id_usuario' => $datos['id_usuario'],
                'codigo' => Medico::generarCodigo(),                           // MED-0001 (paso 7)
                'registro_profesional' => $datos['registro_profesional'],
                'telefono' => $datos['telefono'],
            ]);
            $medico->especialidades()->sync($datos['especialidades']);        // tabla puente medico_especialidad

            return $medico;
        });

        Bitacora::registrar('REGISTRAR_MEDICO', 'medico', 'Médico: ' . $medico->codigo . ' - ' . $medico->nombre_completo);

        return redirect()->route('medicos.index')->with('ok', 'Médico registrado');
    }

    // Secuencia Modificar: obtener médico, especialidades y cuentas; mostrar formulario lleno
    public function editar(Medico $medico): View
    {
        $marcadas = $medico->especialidades()->pluck('especialidad.id_especialidad')->all();

        return view('medicos_especialidades.gestionar_medico.formulario', [
            'medico' => $medico,
            'especialidades' => Especialidad::paraAsignar($marcadas),
            'cuentas' => Usuario::medicosSinVincular($medico->id_usuario),
            'marcadas' => $marcadas,
        ]);
    }

    // Secuencia Modificar: modificar médico
    public function actualizar(MedicoRequest $request, Medico $medico): RedirectResponse
    {
        $datos = $request->validated();

        if (Medico::existeRegistro($datos['registro_profesional'], $medico->id_medico)) {   // 6c sin compararse consigo mismo
            return back()
                ->withErrors(['registro_profesional' => 'El número de registro profesional ya está registrado'])
                ->withInput();
        }

        DB::transaction(function () use ($medico, $datos) {
            $medico->update([
                'id_usuario' => $datos['id_usuario'],
                'registro_profesional' => $datos['registro_profesional'],
                'telefono' => $datos['telefono'],
            ]);
            $medico->especialidades()->sync($datos['especialidades']);   // agrega las nuevas y quita las desmarcadas
        });

        Bitacora::registrar('MODIFICAR_MEDICO', 'medico', 'Médico: ' . $medico->codigo);

        return redirect()->route('medicos.index')->with('ok', 'Médico actualizado');
    }

    // Secuencia Desactivar (la confirmación la hace la vista). Sus citas anteriores se conservan.
    // Su cuenta de usuario sigue activa: puede entrar a ver su agenda e historial.
    public function desactivar(Medico $medico): RedirectResponse
    {
        $medico->update(['activo' => false]);

        Bitacora::registrar('DESACTIVAR_MEDICO', 'medico', 'Médico: ' . $medico->codigo);

        return redirect()->route('medicos.index')->with('ok', 'Médico desactivado');
    }

    // Secuencia Activar: solo si su cuenta de usuario está activa
    public function activar(Medico $medico): RedirectResponse
    {
        if (! $medico->usuario?->activo) {
            return back()->with('error', 'Active primero la cuenta de usuario del médico');
        }

        $medico->update(['activo' => true]);

        Bitacora::registrar('ACTIVAR_MEDICO', 'medico', 'Médico: ' . $medico->codigo);

        return redirect()->route('medicos.index')->with('ok', 'Médico activado');
    }
}
