<?php

namespace App\Modulos\asistencia_usuario\controller\consultar_asistente_virtual;

use App\Http\Controllers\Controller;
use App\Modulos\asistencia_usuario\models\PreguntaFrecuente;
use App\Modulos\asistencia_usuario\service\InterpreteAsistente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU24 Consultar Asistente Virtual (Ctrl_AsistenteVirtual en los diagramas).
 *
 * Lo usan dos actores con las mismas operaciones:
 *   - Paciente (con sesión):    rutas "asistente.*"          -> plantilla interna (compartido.app)
 *   - Visitante (sin sesión):   rutas "asistente.publico.*"  -> plantilla pública (compartido.publica)
 *
 * La conversación se guarda SOLO en la sesión de Laravel (no en la base de datos):
 * se borra al cerrar sesión o con "Limpiar conversación". Máximo los últimos 20 mensajes.
 * Cada acción redirige de vuelta al chat (así recargar la página no reenvía el mensaje).
 */
class AsistenteVirtualController extends Controller
{
    private const SESION = 'asistente_virtual.conversacion';
    private const MAXIMO_MENSAJES = 20;

    // Pasos 1-2: muestra el chat con la conversación guardada
    public function mostrar(): View
    {
        $datos = [
            'conversacion' => session(self::SESION, []),
            'prefijo' => $this->prefijo(),
            'categorias' => PreguntaFrecuente::ICONOS,
        ];

        return auth()->check()
            ? view('asistencia_usuario.consultar_asistente_virtual.paciente', $datos)
            : view('asistencia_usuario.consultar_asistente_virtual.visitante', $datos);
    }

    // Pasos 3-7: el usuario escribe una pregunta
    public function enviar(Request $request): RedirectResponse
    {
        // Paso 4 (flujos 4a y 4b). Si falla, Laravel vuelve al chat con el mensaje de error.
        $datos = $request->validate(
            ['mensaje' => ['required', 'string', 'max:200']],
            [
                'mensaje.required' => 'Escriba su pregunta',
                'mensaje.max' => 'La pregunta no puede superar los 200 caracteres',
            ]
        );

        $mensaje = trim($datos['mensaje']);
        $this->agregar($mensaje, InterpreteAsistente::responder($mensaje));

        return $this->volverAlChat();
    }

    // Flujo 3a (primera parte): el usuario elige una categoría y ve sus preguntas
    public function categoria(Request $request): RedirectResponse
    {
        $datos = $request->validate(['categoria' => ['required', Rule::in(PreguntaFrecuente::CATEGORIAS)]]);
        $categoria = $datos['categoria'];

        $sugerencias = PreguntaFrecuente::listarActivasPorCategoria($categoria)
            ->map(fn ($p) => ['id' => $p->id_pregunta_frecuente, 'pregunta' => $p->pregunta])
            ->all();

        $respuesta = $sugerencias === []
            ? InterpreteAsistente::respuesta("Por ahora no hay preguntas sobre {$categoria}. Escriba su pregunta o comuníquese con recepción.")
            : InterpreteAsistente::respuesta("Estas son las preguntas sobre {$categoria}:", sugerencias: $sugerencias);

        $this->agregar($categoria, $respuesta);

        return $this->volverAlChat();
    }

    // Flujo 3a (segunda parte) y flujo 6a: el usuario toca una pregunta sugerida
    public function pregunta(PreguntaFrecuente $preguntaFrecuente): RedirectResponse
    {
        // Si el administrador la desactivó mientras tanto, ya no se responde
        $respuesta = $preguntaFrecuente->activo
            ? InterpreteAsistente::respuesta($preguntaFrecuente->respuesta, referencia: $preguntaFrecuente->pregunta)
            : InterpreteAsistente::respuesta('Esa pregunta ya no está disponible. Escriba su pregunta o elija una categoría.', categorias: true);

        $this->agregar($preguntaFrecuente->pregunta, $respuesta);

        return $this->volverAlChat();
    }

    // "Limpiar conversación": borra la conversación de la sesión
    public function limpiar(): RedirectResponse
    {
        session()->forget(self::SESION);

        return $this->volverAlChat();
    }

    // ------------------------------------------------------------------

    /** Paso 7: agrega la pregunta del usuario y la respuesta a la conversación (máximo 20 mensajes). */
    private function agregar(string $deUsuario, array $respuesta): void
    {
        $hora = now()->format('H:i');
        $conversacion = session(self::SESION, []);
        $conversacion[] = ['de' => 'usuario', 'texto' => $deUsuario, 'hora' => $hora];
        $conversacion[] = ['de' => 'asistente', 'hora' => $hora] + $respuesta;

        session([self::SESION => array_slice($conversacion, -self::MAXIMO_MENSAJES)]);
    }

    /** Prefijo de las rutas según el actor: Paciente (con sesión) o Visitante (sin sesión). */
    private function prefijo(): string
    {
        return auth()->check() ? 'asistente.' : 'asistente.publico.';
    }

    private function volverAlChat(): RedirectResponse
    {
        return redirect()->route($this->prefijo() . 'mostrar');
    }
}
