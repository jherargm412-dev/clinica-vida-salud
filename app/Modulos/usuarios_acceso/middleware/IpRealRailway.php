<?php

namespace App\Modulos\usuarios_acceso\middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hace que $request->ip() devuelva la IP real del usuario cuando la app corre en Railway.
 *
 * Camino de cada petición en Railway:
 *   usuario → entrada (edge) de Railway en São Paulo (46.151.194.x) → red interna (100.64.x.x) → app
 *
 * El encabezado X-Forwarded-For termina con la IP de la entrada de São Paulo, y por eso la bitácora
 * guardaba esa IP para todos los usuarios. La entrada de Railway agrega además el encabezado
 * X-Real-IP con la IP real del cliente (documentación de Railway, "Specs & Limits").
 *
 * Este filtro copia X-Real-IP en X-Forwarded-For; luego el TrustProxies de Laravel (bootstrap/app.php)
 * la usa como IP del usuario. Así la bitácora y los límites por IP (throttle) usan la IP correcta.
 *
 * Solo se aplica si la petición viene de la red interna de Railway (100.64.0.0/10): esas direcciones
 * no existen en internet, así que nadie puede inventarse una IP mandando X-Real-IP desde fuera.
 * En local (127.0.0.1) no hace nada.
 */
class IpRealRailway
{
    private const RED_INTERNA_RAILWAY = '100.64.0.0/10';

    public function handle(Request $request, Closure $next): Response
    {
        $ipConexion = (string) $request->server->get('REMOTE_ADDR', '');
        $ipReal = trim((string) $request->headers->get('X-Real-IP', ''));

        if (IpUtils::checkIp($ipConexion, self::RED_INTERNA_RAILWAY)
            && filter_var($ipReal, FILTER_VALIDATE_IP) !== false) {
            $request->headers->set('X-Forwarded-For', $ipReal);
        }

        return $next($request);
    }
}
