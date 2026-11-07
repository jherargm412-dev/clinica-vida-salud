<?php

namespace App\Modulos\asistencia_usuario\controller\gestionar_preguntas_frecuentes;

use App\Http\Controllers\Controller;
use App\Modulos\asistencia_usuario\models\PreguntaFrecuente;
use App\Modulos\asistencia_usuario\request\gestionar_preguntas_frecuentes\PreguntaFrecuenteRequest;
use App\Modulos\usuarios_acceso\models\Bitacora;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU23 Gestionar Preguntas Frecuentes (Ctrl_PreguntasFrecuentes en los diagramas) */
class PreguntaFrecuenteController extends Controller
{
    // Pasos 1-2 (Listar) y Consultar: lista con filtro por categoría, estado y texto
    public function index(Request $request): View
    {
        $texto = trim((string) $request->query('buscar')) ?: null;
        // Si llega una categoría o un estado que no existe, se ignora
        $categoria = in_array($request->query('categoria'), PreguntaFrecuente::CATEGORIAS, true) ? $request->query('categoria') : null;
        $estado = in_array($request->query('estado'), ['activo', 'inactivo'], true) ? $request->query('estado') : null;

        return view('asistencia_usuario.gestionar_preguntas_frecuentes.index', [
            'preguntas' => PreguntaFrecuente::listar($texto, $categoria, $estado),
            'texto' => $texto,
            'categoria' => $categoria,
            'estado' => $estado,
            'categorias' => PreguntaFrecuente::CATEGORIAS,
        ]);
    }

    // Pasos 3-4: formulario vacío
    public function crear(): View
    {
        return view('asistencia_usuario.gestionar_preguntas_frecuentes.formulario', [
            'pregunta' => new PreguntaFrecuente(),
            'categorias' => PreguntaFrecuente::CATEGORIAS,
        ]);
    }

    // Pasos 5-8: registrar la pregunta
    public function guardar(PreguntaFrecuenteRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        // Flujo 6c: no se repite el texto de la pregunta
        if (PreguntaFrecuente::existePregunta($datos['pregunta'])) {
            return back()
                ->withErrors(['pregunta' => 'Ya existe una pregunta frecuente con ese texto'])
                ->withInput();
        }

        // Paso 7: palabras clave ordenadas y la pregunta activa
        $datos['palabras_clave'] = PreguntaFrecuente::normalizarPalabrasClave($datos['palabras_clave']);
        $pregunta = PreguntaFrecuente::create($datos + ['activo' => true]);

        Bitacora::registrar('REGISTRAR_PREGUNTA_FRECUENTE', 'pregunta_frecuente',
            $pregunta->categoria . ': ' . $pregunta->pregunta);

        return redirect()->route('faq.index')->with('ok', 'Pregunta registrada');
    }

    // Modificar: formulario con los datos de la pregunta
    public function editar(PreguntaFrecuente $preguntaFrecuente): View
    {
        return view('asistencia_usuario.gestionar_preguntas_frecuentes.formulario', [
            'pregunta' => $preguntaFrecuente,
            'categorias' => PreguntaFrecuente::CATEGORIAS,
        ]);
    }

    // Modificar: mismas validaciones, sin compararse consigo misma
    public function actualizar(PreguntaFrecuenteRequest $request, PreguntaFrecuente $preguntaFrecuente): RedirectResponse
    {
        $datos = $request->validated();

        if (PreguntaFrecuente::existePregunta($datos['pregunta'], $preguntaFrecuente->id_pregunta_frecuente)) {
            return back()
                ->withErrors(['pregunta' => 'Ya existe una pregunta frecuente con ese texto'])
                ->withInput();
        }

        $datos['palabras_clave'] = PreguntaFrecuente::normalizarPalabrasClave($datos['palabras_clave']);
        $preguntaFrecuente->update($datos);

        Bitacora::registrar('MODIFICAR_PREGUNTA_FRECUENTE', 'pregunta_frecuente',
            $preguntaFrecuente->categoria . ': ' . $preguntaFrecuente->pregunta);

        return redirect()->route('faq.index')->with('ok', 'Pregunta actualizada');
    }

    // Desactivar (la confirmación la pide la vista con data-confirmar). El asistente deja de usarla.
    public function desactivar(PreguntaFrecuente $preguntaFrecuente): RedirectResponse
    {
        $preguntaFrecuente->update(['activo' => false]);

        Bitacora::registrar('DESACTIVAR_PREGUNTA_FRECUENTE', 'pregunta_frecuente',
            $preguntaFrecuente->categoria . ': ' . $preguntaFrecuente->pregunta);

        return redirect()->route('faq.index')->with('ok', 'Pregunta desactivada');
    }

    // Activar
    public function activar(PreguntaFrecuente $preguntaFrecuente): RedirectResponse
    {
        $preguntaFrecuente->update(['activo' => true]);

        Bitacora::registrar('ACTIVAR_PREGUNTA_FRECUENTE', 'pregunta_frecuente',
            $preguntaFrecuente->categoria . ': ' . $preguntaFrecuente->pregunta);

        return redirect()->route('faq.index')->with('ok', 'Pregunta activada');
    }
}
