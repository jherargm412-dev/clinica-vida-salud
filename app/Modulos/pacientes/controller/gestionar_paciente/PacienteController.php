<?php

namespace App\Modulos\pacientes\controller\gestionar_paciente;

use App\Http\Controllers\Controller;
use App\Modulos\citas\models\Cita;
use App\Modulos\compartido\Contrasena;
use App\Modulos\compartido\Texto;
use App\Modulos\pacientes\models\Paciente;
use App\Modulos\pacientes\request\gestionar_paciente\PacienteRequest;
use App\Modulos\usuarios_acceso\models\Bitacora;
use App\Modulos\usuarios_acceso\models\Rol;
use App\Modulos\usuarios_acceso\models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * CU06 Gestionar Paciente (PacienteController en los diagramas)
 * Vistas: resources/views/pacientes/gestionar_paciente/{index, formulario}.blade.php (UI_paciente)
 */
class PacienteController extends Controller
{
    // Secuencias 1 (mostrarPacientes) y 2 (buscarPaciente): muestra la lista, o solo los que
    // coinciden con el texto buscado (CI, nombre o apellido, sin distinguir mayúsculas ni tildes)
    public function listarPacientes(Request $request): View
    {
        $texto = $request->query('buscar');

        $pacientes = Paciente::with('usuario')
            ->when($texto, function ($q) use ($texto) {
                $buscado = '%' . Texto::sinTildes(trim($texto)) . '%';
                $q->where(function ($q) use ($buscado) {
                    $q->where('ci', 'ILIKE', $buscado)
                      ->orWhereHas('usuario', fn ($u) => $u->whereRaw(
                          Texto::sqlSinTildes("nombres || ' ' || apellidos") . ' ILIKE ?', [$buscado]));
                });
            })
            ->orderBy('codigo')
            ->paginate(15)
            ->withQueryString();

        return view('pacientes.gestionar_paciente.index', [
            'pacientes' => $pacientes,
            'texto' => $texto,
        ]);
    }

    // Secuencia 3 (crearPaciente): muestra el formulario vacío (pasos 3 y 4 del detalle)
    public function mostrarFormularioNuevo(): View
    {
        return view('pacientes.gestionar_paciente.formulario', ['paciente' => new Paciente()]);
    }

    // Secuencia 3 (registrarPaciente): pasos 5 a 8 del detalle
    public function registrarPaciente(PacienteRequest $request): RedirectResponse
    {
        $datos = $request->validated();     // 6a, 6c y 6d ya validados por PacienteRequest

        if (Paciente::existeCI($datos['ci'])) {                                              // 6b
            return back()
                ->withErrors(['ci' => 'Ya existe un paciente registrado con ese CI'])
                ->withInput();
        }

        if (! empty($datos['correo']) && Usuario::existeCorreo($datos['correo'])) {          // 6e
            return back()
                ->withErrors(['correo' => 'El correo ya está registrado'])
                ->withInput();
        }

        // El nombre de usuario del paciente es su CI (en minúscula)
        $nombreUsuario = mb_strtolower($datos['ci']);
        if (Usuario::existeNombreUsuario($nombreUsuario)) {
            return back()
                ->withErrors(['ci' => 'Ya existe una cuenta de usuario con ese CI'])
                ->withInput();
        }

        $contrasenaTemporal = Contrasena::generarTemporal();
        $rol = Rol::obtenerPorNombre('Paciente');

        // Cuenta de usuario + paciente en una sola transacción: se guardan las dos cosas o ninguna
        $paciente = DB::transaction(function () use ($datos, $nombreUsuario, $contrasenaTemporal, $rol) {
            $usuario = Usuario::create([
                'id_rol' => $rol->id_rol,
                'nombre_usuario' => $nombreUsuario,
                'correo' => $datos['correo'] ?? null,
                'contrasena' => Hash::make($contrasenaTemporal),       // contraseña cifrada
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
            ]);

            return Paciente::create([
                'id_usuario' => $usuario->id_usuario,
                'codigo' => Paciente::generarCodigoPaciente(),         // PAC-0001
                'ci' => $datos['ci'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'sexo' => $datos['sexo'],
                'telefono' => $datos['telefono'],
                'direccion' => $datos['direccion'] ?? null,
            ]);
        });

        Bitacora::registrar('REGISTRAR_PACIENTE', 'paciente', 'Paciente: ' . $paciente->codigo . ' - ' . $paciente->nombre_completo);

        // "Paciente registrado" + los datos de acceso, para que la recepcionista se los entregue (paso 8)
        return redirect()->route('pacientes.index')
            ->with('ok', 'Paciente registrado')
            ->with('credenciales', ['usuario' => $nombreUsuario, 'contrasena' => $contrasenaTemporal]);
    }

    // Secuencia 4 (editarPaciente): mostrar el formulario con los datos actuales
    public function mostrarFormularioEdicion(Paciente $paciente): View
    {
        $paciente->load('usuario');

        return view('pacientes.gestionar_paciente.formulario', ['paciente' => $paciente]);
    }

    // Secuencia 4 (modificarPaciente): actualizar la ficha y su cuenta de usuario
    public function modificarPaciente(PacienteRequest $request, Paciente $paciente): RedirectResponse
    {
        $datos = $request->validated();
        $usuario = $paciente->usuario;

        if (! empty($datos['correo']) && Usuario::existeCorreo($datos['correo'], $usuario->id_usuario)) {
            return back()
                ->withErrors(['correo' => 'El correo ya está registrado'])
                ->withInput();
        }

        DB::transaction(function () use ($paciente, $usuario, $datos) {
            Paciente::actualizarPaciente($paciente->id_paciente, $datos);
            Usuario::actualizarDatosUsuario($usuario->id_usuario, $datos);
            Bitacora::registrar('MODIFICAR_PACIENTE', 'paciente', 'Paciente: ' . $paciente->codigo);
        });

        return redirect()->route('pacientes.index')->with('ok', 'Paciente actualizado');
    }

    // Secuencia 5 (desactivarPaciente): impedir la desactivación si hay citas pendientes
    public function desactivarPaciente(Paciente $paciente): RedirectResponse
    {
        if (! $paciente->activo) {
            return redirect()->route('pacientes.index')->with('error', 'El paciente ya está desactivado');
        }

        $desactivado = DB::transaction(function () use ($paciente) {
            if (Cita::existenCitasPendientesPaciente($paciente->id_paciente)) {
                return false;
            }

            Paciente::cambiarEstadoPaciente($paciente->id_paciente, false);
            Usuario::cambiarEstadoUsuario($paciente->id_usuario, false);

            Bitacora::registrar(
                'DESACTIVAR_PACIENTE',
                'paciente',
                'Paciente: ' . $paciente->codigo
            );

            return true;
        });

        if (! $desactivado) {
            return redirect()->route('pacientes.index')
                ->with('error', 'No se puede desactivar un paciente con citas pendientes');
        }

        return redirect()->route('pacientes.index')->with('ok', 'Paciente desactivado');
    }

    // Secuencia 6 (activarPaciente): reactivar la ficha y su cuenta de usuario
    public function activarPaciente(Paciente $paciente): RedirectResponse
    {
        if ($paciente->activo) {
            return redirect()->route('pacientes.index')->with('error', 'El paciente ya está activo');
        }

        DB::transaction(function () use ($paciente) {
            Paciente::cambiarEstadoPaciente($paciente->id_paciente, true);
            Usuario::cambiarEstadoUsuario($paciente->id_usuario, true);

            Bitacora::registrar(
                'ACTIVAR_PACIENTE',
                'paciente',
                'Paciente: ' . $paciente->codigo
            );
        });

        return redirect()->route('pacientes.index')->with('ok', 'Paciente activado');
    }
}