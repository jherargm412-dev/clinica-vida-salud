<?php

namespace App\Modulos\usuarios_acceso\middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el administrador desactiva o bloquea una cuenta mientras el usuario
 * tiene la sesión abierta, en la siguiente petición se cierra su sesión.
 */
class VerificarCuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && (! $usuario->activo || $usuario->bloqueado)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['credencial' => 'Su cuenta fue desactivada o bloqueada. Comuníquese con el administrador.']);
        }

        return $next($request);
    }
}
