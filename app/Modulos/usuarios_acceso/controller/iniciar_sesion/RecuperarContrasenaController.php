<?php

namespace App\Modulos\usuarios_acceso\controller\iniciar_sesion;

use App\Http\Controllers\Controller;
use App\Modulos\compartido\Contrasena;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\RecuperacionContrasena;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * CU02 Iniciar Sesión — flujo alternativo "Recuperar contraseña"  (Ctrl_RecuperarContrasena)
 * Vistas: resources/views/usuarios_acceso/iniciar_sesion/
 *   recuperar.blade.php        (UI_RecuperarContrasena: pedir el correo)
 *   restablecer.blade.php      (UI_RestablecerContrasena: escribir la nueva contraseña)
 *   correo_recuperar.blade.php (el correo con el enlace, enviado por Brevo)
 */
class RecuperarContrasenaController extends Controller
{
    /**
     * Mensaje que se muestra SIEMPRE tras pedir el enlace, exista o no el correo.
     * Así nadie puede usar este formulario para averiguar qué correos están registrados.
     */
    private const MENSAJE_ENVIADO = 'Si el correo está registrado, le enviamos un enlace para restablecer su contraseña. '
        . 'Revise también la carpeta de spam. El enlace vence en ' . RecuperacionContrasena::MINUTOS_VIGENCIA . ' minutos.';

    private const MENSAJE_ENLACE_INVALIDO = 'El enlace no es válido o ya venció. Solicite uno nuevo.';

    /** Paso 1: formulario para escribir el correo. */
    public function mostrarSolicitud(): View
    {
        return view('usuarios_acceso.iniciar_sesion.recuperar');
    }

    /** Paso 2: si el correo pertenece a una cuenta activa, envía el enlace. */
    public function enviarEnlace(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'correo' => ['required', 'email', 'max:100'],
        ], [
            'correo.required' => 'Ingrese su correo electrónico.',
            'correo.email' => 'Ingrese un correo electrónico válido.',
            'correo.max' => 'El dato ingresado es demasiado largo.',
        ]);

        $usuario = Usuario::buscarPorCorreo($datos['correo']);

        // Correo no registrado: se anota en la bitácora (sin usuario, igual que un login fallido)
        if (! $usuario) {
            Bitacora::registrar('SOLICITAR_RECUPERACION', 'usuario',
                'Correo no registrado: ' . Str::limit($datos['correo'], 60));

            return $this->volverAlLogin();
        }

        // Cuenta desactivada por el administrador: no se envía nada
        if (! $usuario->activo) {
            Bitacora::registrar('SOLICITAR_RECUPERACION', 'usuario', 'Cuenta inactiva: no se envió el enlace', $usuario->id_usuario);

            return $this->volverAlLogin();
        }

        // Ya pidió un enlace hace menos de un minuto: no se envía otro (el anterior sigue sirviendo)
        if (RecuperacionContrasena::solicitadaRecientemente($usuario)) {
            return $this->volverAlLogin();
        }

        $token = RecuperacionContrasena::generarPara($usuario);

        try {
            Mail::send('usuarios_acceso.iniciar_sesion.correo_recuperar', [
                'usuario' => $usuario,
                'enlace' => route('contrasena.restablecer', $token),
                'minutos' => RecuperacionContrasena::MINUTOS_VIGENCIA,
            ], function ($mensaje) use ($usuario) {
                $mensaje->to($usuario->correo, $usuario->nombres . ' ' . $usuario->apellidos)
                    ->subject('Restablecer contraseña - ' . config('app.name'));
            });
        } catch (Throwable $e) {
            // Si Brevo falla, se anula el enlace y se avisa (sin mostrar el detalle técnico)
            Log::warning('No se pudo enviar el correo de recuperación a ' . $usuario->nombre_usuario . ': ' . $e->getMessage());
            RecuperacionContrasena::anularPendientes($usuario);

            return back()->withErrors(['correo' => 'No se pudo enviar el correo. Intente nuevamente más tarde.'])->onlyInput('correo');
        }

        Bitacora::registrar('SOLICITAR_RECUPERACION', 'usuario', 'Enlace enviado al correo de la cuenta', $usuario->id_usuario);

        return $this->volverAlLogin();
    }

    /** Paso 3: el usuario abre el enlace del correo y ve el formulario de nueva contraseña. */
    public function mostrarRestablecer(string $token): View|RedirectResponse
    {
        $recuperacion = RecuperacionContrasena::buscarVigente($token);

        if (! $recuperacion) {
            return redirect()->route('contrasena.solicitar')->withErrors(['correo' => self::MENSAJE_ENLACE_INVALIDO]);
        }

        return view('usuarios_acceso.iniciar_sesion.restablecer', [
            'token' => $token,
            'usuario' => $recuperacion->usuario,
        ]);
    }

    /** Paso 4: guarda la nueva contraseña, desbloquea la cuenta y anula el enlace. */
    public function restablecer(Request $request, string $token): RedirectResponse
    {
        // Misma política de contraseñas que CU01 y CU04 (mín. 8, mayúscula, minúscula, número, especial y confirmación)
        $datos = $request->validate([
            'contrasena' => Contrasena::reglas(true, true),
        ], [
            'contrasena.required' => 'Ingrese la nueva contraseña.',
            'contrasena.min' => Contrasena::MENSAJE,
            'contrasena.regex' => Contrasena::MENSAJE,
            'contrasena.confirmed' => Contrasena::MENSAJE_CONFIRMAR,
            'contrasena.max' => 'El dato ingresado es demasiado largo.',
        ]);

        $recuperacion = RecuperacionContrasena::buscarVigente($token);

        if (! $recuperacion) {
            return redirect()->route('contrasena.solicitar')->withErrors(['correo' => self::MENSAJE_ENLACE_INVALIDO]);
        }

        $usuario = $recuperacion->usuario;
        $estabaBloqueada = $usuario->bloqueado;

        // Todo junto: si algo falla, no queda la contraseña cambiada con el enlace todavía válido
        DB::transaction(function () use ($usuario, $datos) {
            $usuario->restablecerContrasena($datos['contrasena']);
            RecuperacionContrasena::anularPendientes($usuario);   // este enlace y cualquier otro pendiente
        });

        Bitacora::registrar('RESTABLECER_CONTRASENA', 'usuario',
            'Contraseña restablecida por correo' . ($estabaBloqueada ? ' y cuenta desbloqueada' : ''), $usuario->id_usuario);

        return redirect()->route('login')->with('ok', 'Su contraseña fue restablecida. Ya puede iniciar sesión.');
    }

    /** Vuelve al login con el mensaje genérico (no revela si el correo existe). */
    private function volverAlLogin(): RedirectResponse
    {
        return redirect()->route('login')->with('ok', self::MENSAJE_ENVIADO);
    }
}
