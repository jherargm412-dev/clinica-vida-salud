<?php

namespace App\Modulos\usuarios_acceso\controller\configurar_perfil;

use App\Http\Controllers\Controller;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\Usuario;
use App\Modulos\usuarios_acceso\request\configurar_perfil\ContrasenaRequest;
use App\Modulos\usuarios_acceso\request\configurar_perfil\PerfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * CU04 Configurar Perfil de Usuario (Ctrl_Perfil en los diagramas)
 * Siempre trabaja con el usuario de la sesión: nadie puede ver ni editar el perfil de otro.
 * Vista: resources/views/usuarios_acceso/configurar_perfil/ver.blade.php (UI_Perfil: datos, contraseña, cuenta e inicios de sesión)
 */
class PerfilController extends Controller
{
    /** El usuario con sesión iniciada (con su rol cargado). */
    private function usuario(): Usuario
    {
        return Auth::user()->load('rol');
    }

    // Pasos 1-3: obtener perfil y mostrar datos (editables) + cambio de contraseña + información de la cuenta
    public function ver(): View
    {
        $usuario = $this->usuario();

        return view('usuarios_acceso.configurar_perfil.ver', [
            'usuario' => $usuario,
            // Si la cuenta es de un médico: su ficha del CU10 (código, registro, teléfono, especialidades)
            'medico' => $usuario->medico?->load('especialidades'),
            // Últimos 5 inicios de sesión (de la bitácora)
            'inicios' => Bitacora::where('id_usuario', $usuario->id_usuario)
                ->where('accion', 'INICIO_SESION')
                ->orderByDesc('fecha_hora')
                ->limit(5)
                ->get(),
        ]);
    }

    // Pasos 4-5 (secuencia actualizar_datos): actualizar perfil
    public function actualizar(PerfilRequest $request): RedirectResponse
    {
        $usuario = $this->usuario();
        $datos = $request->validated();     // 4a ya validado

        if (! empty($datos['correo']) && Usuario::existeCorreo($datos['correo'], $usuario->id_usuario)) {   // 4b
            return back()->withErrors(['correo' => 'El correo ya está registrado'])->withInput();
        }

        // Solo estos 3 campos: el nombre de usuario y el rol NO se pueden cambiar desde aquí
        $usuario->update([
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'correo' => $datos['correo'] ?? null,
        ]);

        Bitacora::registrar('ACTUALIZAR_PERFIL', 'usuario', 'Usuario: ' . $usuario->nombre_usuario);

        return redirect()->route('perfil.ver')->with('ok', 'Perfil actualizado');
    }

    // Secuencia cambiar_contrasena
    public function cambiarContrasena(ContrasenaRequest $request): RedirectResponse
    {
        $usuario = $this->usuario();
        $datos = $request->validated();     // política y confirmación ya validadas

        if (! $usuario->verificarContrasena($datos['contrasena_actual'])) {
            return back()->withErrors(['contrasena_actual' => 'La contraseña actual es incorrecta']);
        }

        if ($usuario->verificarContrasena($datos['contrasena'])) {
            return back()->withErrors(['contrasena' => 'La nueva contraseña debe ser diferente de la actual']);
        }

        $usuario->update(['contrasena' => Hash::make($datos['contrasena'])]);   // cifrada

        Bitacora::registrar('CAMBIAR_CONTRASENA', 'usuario', 'Usuario: ' . $usuario->nombre_usuario);

        return redirect()->route('perfil.ver')->with('ok', 'Contraseña actualizada');   // el formulario queda vacío
    }
}
