<?php

namespace App\Modulos\usuarios_acceso\controller\gestionar_usuarios;

use App\Http\Controllers\Controller;
use App\Modulos\compartido\Texto;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\Rol;
use App\Modulos\usuarios_acceso\models\Usuario;
use App\Modulos\usuarios_acceso\request\gestionar_usuarios\UsuarioRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * CU01 Gestionar Usuarios y Asignar Roles (Ctrl_Usuarios en los diagramas)
 * Vistas: resources/views/usuarios_acceso/gestionar_usuarios/{index, formulario}.blade.php (UI_Usuarios)
 */
class UsuarioController extends Controller
{
    // Secuencia 1 (Listar) y 4 (Consultar): busca por nombre, apellidos, usuario o correo
    public function index(Request $request): View
    {
        $texto = $request->query('buscar');
        // Filtros: Rol (id) y Estado ('activo' | 'inactivo' | 'bloqueado'); vacío = Todos
        $idRol = $request->integer('rol') ?: null;
        $estado = in_array($request->query('estado'), ['activo', 'inactivo', 'bloqueado'], true) ? $request->query('estado') : null;

        $usuarios = Usuario::with('rol')
            ->when($texto, function ($q) use ($texto) {
                // Sin distinguir mayúsculas ni tildes: "perez" encuentra "Pérez"
                $buscado = '%' . Texto::sinTildes(trim($texto)) . '%';
                $q->where(function ($q) use ($buscado) {
                    $q->whereRaw(Texto::sqlSinTildes("nombres || ' ' || apellidos") . ' ILIKE ?', [$buscado])
                      ->orWhere('nombre_usuario', 'ILIKE', $buscado)
                      ->orWhere('correo', 'ILIKE', $buscado);
                });
            })
            ->when($idRol, fn ($q) => $q->where('id_rol', $idRol))
            ->when($estado === 'activo', fn ($q) => $q->where('activo', true))
            ->when($estado === 'inactivo', fn ($q) => $q->where('activo', false))
            ->when($estado === 'bloqueado', fn ($q) => $q->where('bloqueado', true))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(15)
            ->withQueryString();   // la paginación conserva lo buscado

        return view('usuarios_acceso.gestionar_usuarios.index', [
            'usuarios' => $usuarios,
            'texto' => $texto,
            'roles' => Rol::orderBy('id_rol')->get(),   // lista del filtro (incluye Paciente)
            'idRol' => $idRol,
            'estado' => $estado,
        ]);
    }

    // Secuencia 2.1 a 2.4: obtener roles activos y mostrar formulario vacío
    public function crear(): View
    {
        return view('usuarios_acceso.gestionar_usuarios.formulario', [
            'usuario' => new Usuario(),
            'roles' => Rol::paraAsignar(),
        ]);
    }

    // Secuencia 2.6 a 2.12: registrar usuario
    public function guardar(UsuarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();     // 6a ya validado por el Request

        if (Usuario::existeUsuarioOCorreo($datos['nombre_usuario'], $datos['correo'] ?? null)) {   // 6b
            return back()
                ->withErrors(['nombre_usuario' => 'El nombre de usuario o correo ya está registrado'])
                ->withInput();
        }

        $datos['contrasena'] = Hash::make($datos['contrasena']);   // contraseña cifrada (paso 7)
        $usuario = Usuario::create($datos);                        // activo = true por defecto en la BD

        Bitacora::registrar('REGISTRAR_USUARIO', 'usuario',
            'Usuario: ' . $usuario->nombre_usuario . ' (' . $usuario->rol->nombre . ')');

        return redirect()->route('usuarios.index')->with('ok', 'Usuario registrado');
    }

    // Secuencia 3.1 a 3.6: obtener datos del usuario y roles, mostrar formulario lleno
    public function editar(Usuario $usuario): View
    {
        return view('usuarios_acceso.gestionar_usuarios.formulario', [
            'usuario' => $usuario,
            'roles' => Rol::paraAsignar($usuario->id_rol),
        ]);
    }

    // Secuencia 3.8 a 3.14: modificar usuario
    public function actualizar(UsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        $datos = $request->validated();

        if (Usuario::existeUsuarioOCorreo($datos['nombre_usuario'], $datos['correo'] ?? null, $usuario->id_usuario)) {
            return back()
                ->withErrors(['nombre_usuario' => 'El nombre de usuario o correo ya está registrado'])
                ->withInput();
        }

        // Si la contraseña se deja vacía, se mantiene la actual
        if (empty($datos['contrasena'])) {
            unset($datos['contrasena']);
        } else {
            $datos['contrasena'] = Hash::make($datos['contrasena']);
        }

        $usuario->update($datos);

        Bitacora::registrar('MODIFICAR_USUARIO', 'usuario', 'Usuario: ' . $usuario->nombre_usuario);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado');
    }

    // Secuencia 5: desactivar usuario (la confirmación 5.1 la hace la vista)
    public function desactivar(Usuario $usuario): RedirectResponse
    {
        // 5.4 esPropiaCuenta: el administrador no puede desactivarse a sí mismo
        if ((int) $usuario->id_usuario === (int) Auth::id()) {
            return back()->with('error', 'No puede desactivar su propia cuenta');
        }

        $usuario->update(['activo' => false]);
        Bitacora::registrar('DESACTIVAR_USUARIO', 'usuario', 'Usuario: ' . $usuario->nombre_usuario);

        // Si la cuenta es de un médico activo, también se desactiva como médico (no recibe nuevas citas).
        // Al reactivar la cuenta NO se reactiva el médico: eso se hace a propósito en el CU10.
        $medico = $usuario->medico;
        if ($medico && $medico->activo) {
            $medico->update(['activo' => false]);
            Bitacora::registrar('DESACTIVAR_MEDICO', 'medico', 'Médico: ' . $medico->codigo . ' (por desactivación de su cuenta)');

            return redirect()->route('usuarios.index')->with('ok', 'Usuario desactivado. También se desactivó como médico');
        }

        return redirect()->route('usuarios.index')->with('ok', 'Usuario desactivado');
    }

    // Secuencia 6: reactivar usuario
    public function reactivar(Usuario $usuario): RedirectResponse
    {
        $usuario->update(['activo' => true]);

        Bitacora::registrar('REACTIVAR_USUARIO', 'usuario', 'Usuario: ' . $usuario->nombre_usuario);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario activado');
    }

    // Secuencia 7: desbloquear cuenta (quita el bloqueo y reinicia los intentos fallidos)
    public function desbloquear(Usuario $usuario): RedirectResponse
    {
        $usuario->update(['bloqueado' => false, 'intentos_fallidos' => 0]);

        Bitacora::registrar('DESBLOQUEAR_USUARIO', 'usuario', 'Usuario: ' . $usuario->nombre_usuario);

        return redirect()->route('usuarios.index')->with('ok', 'Cuenta desbloqueada');
    }
}
