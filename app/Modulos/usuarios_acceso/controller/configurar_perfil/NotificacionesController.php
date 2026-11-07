<?php

namespace App\Modulos\usuarios_acceso\controller\configurar_perfil;

use App\Http\Controllers\Controller;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\SuscripcionPush;
use App\Modulos\usuarios_acceso\request\configurar_perfil\SuscripcionPushRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CU04 Configurar perfil — flujo alternativo "Configurar notificaciones" (RF30)  (Ctrl_Notificaciones)
 * El paciente activa o desactiva las notificaciones push en cada uno de sus dispositivos.
 * Vista: tarjeta "Notificaciones" de resources/views/usuarios_acceso/configurar_perfil/ver.blade.php
 * JavaScript: public/js/notificaciones.js (pide el permiso al navegador y llena el formulario oculto)
 */
class NotificacionesController extends Controller
{
    /** Activar en este dispositivo: guarda los datos que entregó el navegador. */
    public function activar(SuscripcionPushRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario->paciente()->exists(), 403);   // RF30 es solo para pacientes

        $datos = $request->validated();
        $dispositivo = Str::limit($datos['dispositivo'] ?? 'Navegador', 97);

        SuscripcionPush::activar($usuario->id_usuario, $datos['endpoint'], $datos['p256dh'], $datos['auth'], $dispositivo);

        Bitacora::registrar('ACTIVAR_NOTIFICACIONES', 'suscripcion_push', 'Dispositivo: ' . $dispositivo);

        return redirect()->to(route('perfil.ver') . '#notificaciones')
            ->with('ok', 'Notificaciones activadas en este dispositivo');
    }

    /** Desactivar un dispositivo (este u otro de la lista). Solo puede tocar las suyas. */
    public function desactivar(Request $request, SuscripcionPush $suscripcion): RedirectResponse
    {
        abort_unless((int) $suscripcion->id_usuario === (int) $request->user()->id_usuario, 404);

        $suscripcion->desactivar();

        Bitacora::registrar('DESACTIVAR_NOTIFICACIONES', 'suscripcion_push', 'Dispositivo: ' . $suscripcion->dispositivo);

        return redirect()->to(route('perfil.ver') . '#notificaciones')
            ->with('ok', 'Notificaciones desactivadas en ' . $suscripcion->dispositivo);
    }
}
