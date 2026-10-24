<?php

namespace App\Modulos\usuarios_acceso\controller\gestionar_roles;

use App\Http\Controllers\Controller;
use App\Modulos\compartido\Texto;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\Permiso;
use App\Modulos\usuarios_acceso\models\Rol;
use App\Modulos\usuarios_acceso\request\gestionar_roles\RolRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CU03 Gestionar Roles y Permisos (Ctrl_Roles en los diagramas)
 * Vistas: resources/views/usuarios_acceso/gestionar_roles/{index, formulario, ver}.blade.php (UI_Roles)
 */
class RolController extends Controller
{
    // Secuencia Listar: roles con su número de permisos y de usuarios activos (+ buscador y filtro Estado)
    public function index(Request $request): View
    {
        $texto = $request->query('buscar');
        $estado = in_array($request->query('estado'), ['activo', 'inactivo'], true) ? $request->query('estado') : null;

        $roles = Rol::query()
            ->withCount([
                'permisos',
                'usuarios as usuarios_activos_count' => fn ($q) => $q->where('activo', true),
            ])
            ->when($texto, fn ($q) => $q->whereRaw(
                Texto::sqlSinTildes('nombre') . ' ILIKE ?', ['%' . Texto::sinTildes(trim($texto)) . '%']))
            ->when($estado, fn ($q) => $q->where('activo', $estado === 'activo'))
            ->orderBy('id_rol')
            ->get();

        return view('usuarios_acceso.gestionar_roles.index', [
            'roles' => $roles,
            'texto' => $texto,
            'estado' => $estado,
        ]);
    }

    // Secuencia Registrar: obtener permisos y mostrar formulario vacío
    public function crear(): View
    {
        return view('usuarios_acceso.gestionar_roles.formulario', [
            'rol' => new Rol(),
            'grupos' => Permiso::agrupados(),
            'marcados' => [],
        ]);
    }

    // Secuencia Registrar: registrar rol con sus permisos
    public function guardar(RolRequest $request): RedirectResponse
    {
        $datos = $request->validated();     // 6a y 6c ya validados

        if (Rol::existeNombre($datos['nombre'])) {     // 6b
            return back()->withErrors(['nombre' => 'Ya existe un rol con ese nombre'])->withInput();
        }

        $rol = DB::transaction(function () use ($datos) {
            $rol = Rol::create(['nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion'] ?? null]);
            $rol->permisos()->sync($datos['permisos']);     // tabla puente rol_permiso
            return $rol;
        });

        Bitacora::registrar('REGISTRAR_ROL', 'rol', 'Rol: ' . $rol->nombre . ' (' . count($datos['permisos']) . ' permisos)');

        return redirect()->route('roles.index')->with('ok', 'Rol registrado');
    }

    // Secuencia Consultar ("Ver"): datos del rol y lista de sus permisos
    public function ver(Rol $rol): View
    {
        return view('usuarios_acceso.gestionar_roles.ver', [
            'rol' => $rol,
            'grupos' => Permiso::agrupados($rol->permisos()->orderBy('permiso.id_permiso')->get()),
            'usuariosActivos' => $rol->contarUsuariosActivos(),
        ]);
    }

    // Secuencia Modificar: obtener rol con permisos y mostrar formulario lleno
    public function editar(Rol $rol): View
    {
        return view('usuarios_acceso.gestionar_roles.formulario', [
            'rol' => $rol,
            'grupos' => Permiso::agrupados(),
            'marcados' => $rol->permisos()->pluck('permiso.id_permiso')->all(),
        ]);
    }

    // Secuencia Modificar: modificar rol y sus permisos (se aplican de inmediato a sus usuarios)
    public function actualizar(RolRequest $request, Rol $rol): RedirectResponse
    {
        $datos = $request->validated();

        // Regla del rol Administrador: no puede perder "Gestionar roles y permisos"
        $idPermisoRoles = (int) Permiso::where('clave', Permiso::CLAVE_ROLES)->value('id_permiso');
        if ($rol->esAdministrador() && ! in_array($idPermisoRoles, array_map('intval', $datos['permisos']), true)) {
            return back()
                ->withErrors(['permisos' => 'El rol Administrador debe conservar el permiso Gestionar roles y permisos'])
                ->withInput();
        }

        if (Rol::existeNombre($datos['nombre'], $rol->id_rol)) {     // 6b sin compararse consigo mismo
            return back()->withErrors(['nombre' => 'Ya existe un rol con ese nombre'])->withInput();
        }

        DB::transaction(function () use ($rol, $datos) {
            $rol->update(['nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion'] ?? null]);
            $rol->permisos()->sync($datos['permisos']);     // agrega los nuevos y quita los desmarcados
        });

        Bitacora::registrar('MODIFICAR_ROL', 'rol', 'Rol: ' . $rol->nombre . ' (' . count($datos['permisos']) . ' permisos)');

        return redirect()->route('roles.index')->with('ok', 'Rol actualizado');
    }

    // Secuencia Deshabilitar (la confirmación la hace la vista)
    public function deshabilitar(Rol $rol): RedirectResponse
    {
        if ($rol->esAdministrador()) {
            return back()->with('error', 'El rol Administrador no se puede deshabilitar');
        }

        if ($rol->contarUsuariosActivos() > 0) {
            return back()->with('error', 'No se puede deshabilitar un rol con usuarios activos');
        }

        $rol->update(['activo' => false]);

        Bitacora::registrar('DESHABILITAR_ROL', 'rol', 'Rol: ' . $rol->nombre);

        return redirect()->route('roles.index')->with('ok', 'Rol deshabilitado');
    }

    // Habilitar (detallar)
    public function habilitar(Rol $rol): RedirectResponse
    {
        $rol->update(['activo' => true]);

        Bitacora::registrar('HABILITAR_ROL', 'rol', 'Rol: ' . $rol->nombre);

        return redirect()->route('roles.index')->with('ok', 'Rol habilitado');
    }
}
