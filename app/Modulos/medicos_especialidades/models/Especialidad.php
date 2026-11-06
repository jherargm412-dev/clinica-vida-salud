<?php

namespace App\Modulos\medicos_especialidades\models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// La carpeta models/ guarda una clase por cada tabla de la base de datos. 
// Es la capa que habla con PostgreSQL: el controller nunca escribe SQL, le pide los datos al modelo.

class Especialidad extends Model 
{
    protected $table = 'especialidad';
    protected $primaryKey = 'id_especialidad';
    public $timestamps = false;
    protected $fillable = ['nombre', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    // Pasa el nombre a minúsculas, sin tildes y sin espacios de más
    // "  Cardiología " -> "cardiologia"   (secuencia 2.6 normalizar)
    public static function normalizar(string $nombre): string
    {
        return Str::lower(Str::ascii(trim($nombre)));
    }

    // ¿Ya existe otra especialidad con ese nombre normalizado?   (secuencia 2.7 existeNombre)
    // $excepto: id a ignorar (se usa en Modificar para no compararse consigo misma)
    public static function existeNombre(string $nombre, ?int $excepto = null): bool
    {
        $buscado = self::normalizar($nombre);

        return self::query()
            ->when($excepto, fn ($q) => $q->where('id_especialidad', '<>', $excepto))
            ->get()
            ->contains(fn ($e) => self::normalizar($e->nombre) === $buscado);
    }

    // CU10 (secuencia "listarActivas"): especialidades que se pueden marcar para un médico.
    // $incluir: ids que deben aparecer aunque estén inactivas (las que el médico ya tiene), para no quitárselas sin querer.
    public static function paraAsignar(array $incluir = [])
    {
        return self::query()
            ->where('activo', true)
            ->when($incluir, fn ($q) => $q->orWhereIn('id_especialidad', $incluir))
            ->orderBy('nombre')
            ->get();
    }

    /** CU12 (secuencia 1): especialidades disponibles para consultar citas. */
    public static function listarActivas(): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    /** CU20 (2.2 listarActivasSinTarifa): especialidades activas que todavía no tienen una tarifa activa. */
    public static function listarActivasSinTarifa(): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->where('activo', true)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('tarifa')
                ->whereColumn('tarifa.id_especialidad', 'especialidad.id_especialidad')
                ->where('tarifa.activo', true))
            ->orderBy('nombre')
            ->get();
    }

    /**
     * CU13 (asistente, paso Especialidad): especialidades que pueden recibir citas:
     * activas, con tarifa activa (CU20) y con al menos un médico activo con cuenta activa.
     * Cada una trae "monto_tarifa" para mostrar el precio de la consulta.
     */
    public static function listarParaRegistrarCita(): \Illuminate\Database\Eloquent\Collection
    {
        return self::query()
            ->where('activo', true)
            ->addSelect(['monto_tarifa' => \App\Modulos\pagos\models\Tarifa::query()
                ->select('monto')
                ->whereColumn('tarifa.id_especialidad', 'especialidad.id_especialidad')
                ->where('tarifa.activo', true)
                ->limit(1)])
            ->whereExists(fn ($q) => $q->selectRaw('1')
                ->from('tarifa')
                ->whereColumn('tarifa.id_especialidad', 'especialidad.id_especialidad')
                ->where('tarifa.activo', true))
            ->whereExists(fn ($q) => $q->selectRaw('1')
                ->from('medico_especialidad')
                ->join('medico', 'medico.id_medico', '=', 'medico_especialidad.id_medico')
                ->join('usuario', 'usuario.id_usuario', '=', 'medico.id_usuario')
                ->whereColumn('medico_especialidad.id_especialidad', 'especialidad.id_especialidad')
                ->where('medico.activo', true)
                ->where('usuario.activo', true))
            ->orderBy('nombre')
            ->get();
    }

    /** CU13 (secuencia 5.4): obtiene una especialidad por identificador. */
    public static function obtenerPorId(int $idEspecialidad): ?self
    {
        return self::find($idEspecialidad);
    }
}
