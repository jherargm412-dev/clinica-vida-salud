<?php

namespace App\Modulos\usuarios_acceso\models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Entidad Permiso (tabla "permiso"). La "clave" se usa en el código, p. ej. "medicos.gestionar". */
class Permiso extends Model
{
    protected $table = 'permiso';
    protected $primaryKey = 'id_permiso';
    public $timestamps = false;

    protected $fillable = ['clave', 'nombre', 'descripcion'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'id_permiso', 'id_rol');
    }

    /** Permiso que el rol Administrador nunca puede perder (CU03). */
    public const CLAVE_ROLES = 'roles.gestionar';

    /** Grupos para mostrar los permisos ordenados por paquete (diagrama de paquetes). */
    public const GRUPOS = [
        'Usuarios y acceso'        => ['usuarios.gestionar', 'roles.gestionar', 'bitacora.consultar'],
        'Pacientes'                => ['pacientes.gestionar', 'perfil_medico.gestionar', 'historial.consultar'],
        'Médicos y especialidades' => ['especialidades.gestionar', 'medicos.gestionar', 'horarios.gestionar'],
        'Citas'                    => ['disponibilidad.consultar', 'citas.registrar', 'citas.reprogramar', 'citas.cancelar', 'citas.controlar_estado'],
        'Agenda médica'            => ['agenda.consultar', 'citas.registrar_atencion'],
        'Reportes'                 => ['reportes.generar'],
    ];

    /**
     * Secuencia "listar permisos": todos los permisos agrupados por paquete.
     * Devuelve ['Usuarios y acceso' => [Permiso, ...], ...]. Si en el futuro hay uno sin grupo, va a "Otros".
     */
    public static function agrupados(?iterable $permisos = null): array
    {
        $permisos = collect($permisos ?? self::orderBy('id_permiso')->get());
        $grupos = [];

        foreach (self::GRUPOS as $grupo => $claves) {
            $deEsteGrupo = $permisos->filter(fn ($p) => in_array($p->clave, $claves, true))
                                    ->sortBy(fn ($p) => array_search($p->clave, $claves))->values();
            if ($deEsteGrupo->isNotEmpty()) {
                $grupos[$grupo] = $deEsteGrupo;
            }
        }

        $todas = array_merge(...array_values(self::GRUPOS));
        $otros = $permisos->reject(fn ($p) => in_array($p->clave, $todas, true))->values();
        if ($otros->isNotEmpty()) {
            $grupos['Otros'] = $otros;
        }

        return $grupos;
    }
}
