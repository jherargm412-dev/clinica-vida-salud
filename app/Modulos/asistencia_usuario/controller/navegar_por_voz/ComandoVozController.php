<?php

namespace App\Modulos\asistencia_usuario\controller\navegar_por_voz;

use App\Http\Controllers\Controller;
use App\Modulos\asistencia_usuario\service\InterpreteComandoVoz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CU25 Navegar por Comando de Voz (Ctrl_ComandoVoz en los diagramas).
 *
 * El navegador convierte la voz en texto (public/js/comando_voz.js) y lo envía aquí con un
 * formulario normal. Este controlador NO cambia datos: solo redirige a otra pantalla.
 */
class ComandoVozController extends Controller
{
    // Pasos 4-8: interpreta el texto dictado y lleva al usuario a la pantalla pedida
    public function interpretar(Request $request): RedirectResponse
    {
        // Se valida a mano para mostrar el mensaje como alerta en CUALQUIER pantalla (session 'error'),
        // ya que el comando se envía desde la barra superior y no desde un formulario con campos.
        $texto = trim((string) $request->input('texto'));
        if ($texto === '') {
            return back()->with('error', 'No se escuchó ningún comando. Intente de nuevo');   // flujo 3a
        }
        if (mb_strlen($texto) > 200) {
            return back()->with('error', 'El comando es demasiado largo. Intente con una frase más corta');
        }

        $resultado = InterpreteComandoVoz::interpretar($texto, $request->user());

        // Flujos 6a, 6c y 7a: el usuario se queda en la pantalla en la que estaba
        if (isset($resultado['error'])) {
            return back()->with('error', $resultado['error']);
        }

        // Paso 8: pantalla destino + el comando reconocido (lo muestra la plantilla compartido.app)
        return redirect()->to($resultado['url'])->with('comando', $texto);
    }

    // Flujo 6b: lista de comandos disponibles para el rol del usuario
    public function ayuda(Request $request): View
    {
        return view('asistencia_usuario.navegar_por_voz.ayuda', [
            'comandos' => InterpreteComandoVoz::comandosDisponibles($request->user()),
        ]);
    }
}
