<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CU02 Iniciar Sesión  (Ctrl_Login en los diagramas)
 * Vista: resources/views/auth/login.blade.php  (UI_login)
 */
class AuthController extends Controller
{
    /** Máximo de intentos fallidos consecutivos antes de bloquear la cuenta (RF2). */
    private const MAX_INTENTOS = 3;

    /** Muestra el formulario de inicio de sesión. */
    public function mostrarLogin(): View
    {
        return view('auth.login');
    }

    /** Valida las credenciales e inicia la sesión. */
    public function iniciarSesion(Request $request): RedirectResponse
    {
        // Validación en el servidor (también se valida en el navegador con JavaScript)
        $datos = $request->validate([
            'credencial' => ['required', 'string', 'max:100'],
            'contrasena' => ['required', 'string', 'max:100'],
        ], [
            'credencial.required' => 'Ingrese su nombre de usuario o correo electrónico.',
            'contrasena.required' => 'Ingrese su contraseña.',
            '*.max' => 'El dato ingresado es demasiado largo.',
        ]);

        $mensajeGenerico = 'Usuario o contraseña incorrectos.';
        $usuario = Usuario::buscarPorCredencial($datos['credencial']);

        // Usuario no encontrado
        if (! $usuario) {
            Bitacora::registrar('INICIO_SESION_FALLIDO', 'usuario',
                'Usuario inexistente: ' . Str::limit($datos['credencial'], 60));

            return $this->rechazar($mensajeGenerico);
        }

        // Cuenta inactiva
        if (! $usuario->activo) {
            Bitacora::registrar('INICIO_SESION_FALLIDO', 'usuario', 'Cuenta inactiva', $usuario->id_usuario);

            return $this->rechazar('La cuenta está desactivada. Comuníquese con el administrador.');
        }

        // Cuenta bloqueada por intentos fallidos
        if ($usuario->bloqueado) {
            Bitacora::registrar('INICIO_SESION_FALLIDO', 'usuario', 'Cuenta bloqueada', $usuario->id_usuario);

            return $this->rechazar('La cuenta está bloqueada por intentos fallidos. Comuníquese con el administrador.');
        }

        // Rol deshabilitado
        if (! $usuario->rol || ! $usuario->rol->activo) {
            Bitacora::registrar('INICIO_SESION_FALLIDO', 'usuario', 'Rol deshabilitado', $usuario->id_usuario);

            return $this->rechazar('El rol asignado a la cuenta está deshabilitado. Comuníquese con el administrador.');
        }

        // Contraseña incorrecta: suma un intento y bloquea al tercero
        if (! Hash::check($datos['contrasena'], $usuario->contrasena)) {
            $usuario->intentos_fallidos = min($usuario->intentos_fallidos + 1, self::MAX_INTENTOS);

            if ($usuario->intentos_fallidos >= self::MAX_INTENTOS) {
                $usuario->bloqueado = true;
                $usuario->save();
                Bitacora::registrar('BLOQUEO_CUENTA', 'usuario',
                    'Cuenta bloqueada tras ' . self::MAX_INTENTOS . ' intentos fallidos', $usuario->id_usuario);

                return $this->rechazar('La cuenta fue bloqueada por ' . self::MAX_INTENTOS
                    . ' intentos fallidos. Comuníquese con el administrador.');
            }

            $usuario->save();
            Bitacora::registrar('INICIO_SESION_FALLIDO', 'usuario',
                'Contraseña incorrecta (intento ' . $usuario->intentos_fallidos . ')', $usuario->id_usuario);

            $restantes = self::MAX_INTENTOS - $usuario->intentos_fallidos;

            return $this->rechazar($mensajeGenerico . " Le quedan {$restantes} intento(s).");
        }

        // Credenciales correctas: reinicia el contador y crea la sesión
        $usuario->intentos_fallidos = 0;
        $usuario->save();

        Auth::login($usuario);
        $request->session()->regenerate();   // evita la fijación de sesión

        Bitacora::registrar('INICIO_SESION', 'usuario', 'Inicio de sesión correcto', $usuario->id_usuario);

        return redirect()->intended(route('inicio'));
    }

    /** Cierra la sesión del usuario. */
    public function cerrarSesion(Request $request): RedirectResponse
    {
        Bitacora::registrar('CIERRE_SESION', 'usuario', 'Cierre de sesión');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('ok', 'Sesión cerrada correctamente.');
    }

    /** Vuelve al formulario con el mensaje de error, conservando lo escrito en "credencial". */
    private function rechazar(string $mensaje): RedirectResponse
    {
        return back()->withErrors(['credencial' => $mensaje])->onlyInput('credencial');
    }
}
