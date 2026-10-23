<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar solo si el rol del usuario tiene alguno de los permisos indicados.
 * Uso en routes/web.php:  ->middleware('permiso:medicos.gestionar')
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $usuario = $request->user();

        foreach ($permisos as $permiso) {
            if ($usuario && $usuario->tienePermiso($permiso)) {
                return $next($request);
            }
        }

        abort(403, 'No tiene permiso para acceder a esta opción.');
    }
}
