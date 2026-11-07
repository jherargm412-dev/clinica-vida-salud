<?php

namespace App\Modulos\asistencia_usuario\service;

use App\Modulos\compartido\Texto;
use App\Modulos\medicos_especialidades\models\Especialidad;
use App\Modulos\usuarios_acceso\models\Usuario;

/**
 * CU25 Navegar por comando de voz: las REGLAS que convierten lo que dijo el usuario en una pantalla.
 *
 * interpretar() devuelve:
 *   ['url' => '...']        -> el controlador lleva al usuario a esa dirección (paso 8)
 *   ['error' => '...']      -> el usuario se queda donde estaba y ve el mensaje (flujos 6a, 6c, 7a)
 *
 * Los comandos SOLO buscan y navegan: nunca registran, modifican, cancelan, cobran ni desactivan.
 * Cada destino se revisa con los permisos del rol (paso 7), igual que el menú.
 */
class InterpreteComandoVoz
{
    /** Palabras de relleno al inicio ("por favor", "quiero", "puedes"...) que no cambian el comando (paso 5). */
    private const RELLENO_INICIAL = '/^(?:por favor|porfa|oye|asistente|quiero|quisiera|necesito|puedes|podrias|me puedes|me podrias|deseo)\s+/u';

    /** Verbos con los que se pide abrir una pantalla ("abrir pacientes", "ir a reportes"). */
    private const VERBOS_ABRIR = 'abrir|abre|ir a|ir al|ve a|ve al|vamos a|vamos al|llevame a|llevame al|entrar a|entrar al|entra a|entra al|mostrar|muestrame|ver';

    public static function interpretar(string $dicho, Usuario $usuario): array
    {
        $texto = self::normalizar($dicho);
        if ($texto === '') {
            return ['error' => 'No se escuchó ningún comando. Intente de nuevo'];
        }

        // ayuda (flujo 6b)
        if (preg_match('/^(?:ayuda|ayudame|que (?:puedo|se puede) decir|(?:que |los |lista de )?comandos(?: de voz)?(?: disponibles| hay)?)$/u', $texto)) {
            return ['url' => route('voz.ayuda')];
        }

        // inicio
        if (preg_match('/^(?:(?:ir|ve|vamos|volver|regresar|abrir|abre|llevame)(?: a| al| a la)? )?(?:la )?(?:inicio|pagina (?:de )?inicio|pagina principal|panel principal)$/u', $texto)) {
            return ['url' => route('inicio')];
        }

        // citas de hoy / citas de mañana -> Controlar estado de citas filtrado por fecha
        if (preg_match('/^(?:(?:ver|mostrar|muestrame|abrir|abre) )?(?:las )?citas (?:de |para )?(hoy|manana|mañana)$/u', $texto, $m)) {
            if (! $usuario->tienePermiso('citas.controlar_estado')) {
                return self::sinAcceso('Controlar estado de citas');
            }
            return ['url' => route('citas.estado.buscar', ['fecha' => self::fecha($m[1])])];
        }

        // mi agenda / mi agenda de mañana (solo médicos)
        if (preg_match('/^(?:(?:ver|mostrar|muestrame|abrir|abre) )?mi agenda(?: (?:de |para )?(hoy|manana|mañana))?$/u', $texto, $m)) {
            if (! $usuario->tienePermiso('agenda.consultar') || ! $usuario->medico()->exists()) {
                return self::sinAcceso('Mi agenda');
            }
            return ['url' => route('agenda.mi', ['fecha' => self::fecha($m[1] ?? 'hoy')])];
        }

        // buscar paciente [nombre o CI]
        if (preg_match('/^(?:busca|buscar|buscame|busque|encuentra|encontrar|localiza|localizar) (?:a |al |a la |el |la )?pacientes?(?: (?:llamad[oa]|de nombre|con ci|con carnet|ci|carnet))?(?: (.+))?$/u', $texto, $m)) {
            if (! $usuario->tienePermiso('pacientes.gestionar')) {
                return self::sinAcceso('Pacientes');
            }
            return ['url' => route('pacientes.index', array_filter(['buscar' => self::dato($m[1] ?? '')]))];
        }

        // buscar médico [nombre o especialidad]
        if (preg_match('/^(?:busca|buscar|buscame|busque|encuentra|encontrar|localiza|localizar) (?:a |al |a la |el |la )?(?:medic[oa]s?|doctor(?:a|es|as)?|dr|dra|especialistas?)(?: llamad[oa])?(?: (.+))?$/u', $texto, $m)) {
            if (! $usuario->tienePermiso('medicos.gestionar')) {
                return self::sinAcceso('Médicos');
            }
            return ['url' => route('medicos.index', array_filter(['buscar' => self::dato($m[1] ?? '')]))];
        }

        // historial de [paciente]
        if (preg_match('/^(?:(?:ver|mostrar|muestrame|abrir|abre|buscar|busca) )?(?:el |mi )?historial(?: de citas)?(?: (.+))?$/u', $texto, $m)) {
            if (! $usuario->tienePermiso('historial.consultar')) {
                return self::sinAcceso('Historial de citas');
            }
            // El paciente solo ve sus propias citas
            if ($usuario->paciente()->exists()) {
                return ['url' => route('historial.mis_citas')];
            }
            $paciente = self::dato($m[1] ?? '');
            return ['url' => $paciente !== '' ? route('historial.buscar', ['texto' => $paciente]) : route('historial.index')];
        }

        // registrar cita / nueva cita
        if (preg_match('/^(?:(?:registrar|registra|crear|crea|agendar|sacar|saca|reservar|reserva|programar) (?:una )?(?:nueva )?cita(?: nueva)?|nueva cita)$/u', $texto)) {
            if (! $usuario->tienePermiso('citas.registrar')) {
                return self::sinAcceso('Registrar cita');
            }
            return ['url' => route('citas.crear')];
        }

        // disponibilidad de [especialidad]
        if (preg_match('/^(?:(?:ver|mostrar|muestrame|consultar|consulta|abrir|abre) )?(?:la )?disponibilidad(?: (.+))?$/u', $texto, $m)) {
            if (! $usuario->tienePermiso('disponibilidad.consultar')) {
                return self::sinAcceso('Disponibilidad');
            }
            $nombre = self::dato($m[1] ?? '');
            if ($nombre === '') {
                return ['url' => route('disponibilidad.index')];
            }
            $especialidad = self::buscarEspecialidad($nombre);
            if (! $especialidad) {
                return ['error' => "No encontré la especialidad «{$nombre}»"];   // flujo 6c
            }
            return ['url' => route('disponibilidad.medicos', ['id_especialidad' => $especialidad->id_especialidad])];
        }

        // abrir / ir a [módulo]   (o solo el nombre del módulo: "pacientes")
        $modulo = preg_match('/^(?:' . self::VERBOS_ABRIR . ') (?:la |el |los |las )?(.+)$/u', $texto, $m)
            ? $m[1]
            : $texto;
        $resultado = self::abrirModulo($modulo, $usuario);
        if ($resultado) {
            return $resultado;
        }

        // Flujo 6a: no corresponde a ningún comando
        return ['error' => 'No entendí el comando «' . trim($dicho) . '». Diga «ayuda» para ver los comandos disponibles'];
    }

    /**
     * Comandos para la pantalla de ayuda (flujo 6b): solo los que el rol del usuario puede usar.
     * Cada uno: ['comando', 'ejemplo', 'descripcion']
     */
    public static function comandosDisponibles(Usuario $usuario): array
    {
        $esPaciente = $usuario->paciente()->exists();
        $comandos = [
            ['inicio', '"ir al inicio"', 'Vuelve al panel principal.', true],
            ['abrir [opción del menú]', '"abrir ' . self::ejemploModulo($usuario) . '"', 'Abre cualquier opción del menú que su rol puede ver.', true],
            ['ayuda', '"qué puedo decir"', 'Muestra esta lista.', true],
            ['buscar paciente [nombre o CI]', '"buscar paciente Pérez"', 'Abre Pacientes con la búsqueda aplicada.', $usuario->tienePermiso('pacientes.gestionar')],
            ['buscar médico [nombre o especialidad]', '"buscar médico cardiología"', 'Abre Médicos con la búsqueda aplicada.', $usuario->tienePermiso('medicos.gestionar')],
            $esPaciente
                ? ['historial', '"ver mi historial"', 'Abre Mis citas.', $usuario->tienePermiso('historial.consultar')]
                : ['historial de [paciente]', '"historial de Pérez"', 'Busca al paciente en Historial de citas.', $usuario->tienePermiso('historial.consultar')],
            ['registrar cita', '"nueva cita"', 'Abre el registro de una cita.', $usuario->tienePermiso('citas.registrar')],
            ['disponibilidad de [especialidad]', '"disponibilidad de pediatría"', 'Muestra los médicos y horarios libres de esa especialidad.', $usuario->tienePermiso('disponibilidad.consultar')],
            ['citas de hoy / citas de mañana', '"citas de mañana"', 'Abre Controlar estado de citas filtrado por esa fecha.', $usuario->tienePermiso('citas.controlar_estado')],
            ['mi agenda / mi agenda de mañana', '"mi agenda de mañana"', 'Abre su agenda en esa fecha.', $usuario->tienePermiso('agenda.consultar') && $usuario->medico()->exists()],
        ];

        return array_map(fn ($c) => array_slice($c, 0, 3), array_values(array_filter($comandos, fn ($c) => $c[3])));
    }

    // ------------------------------------------------------------------
    // Paso 5: preparar el texto
    // ------------------------------------------------------------------

    /**
     * Minúsculas, sin tildes (la ñ se conserva para buscar "Peña"), sin signos y sin relleno.
     * "Por favor, busca al paciente Pérez." -> "busca al paciente perez"
     */
    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(Texto::sinTildes($texto));
        $texto = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $texto);
        $texto = trim(preg_replace('/\s+/u', ' ', $texto));
        $texto = trim(preg_replace('/\s*\bpor favor\b\s*/u', ' ', $texto));

        // Puede haber varias palabras de relleno seguidas ("oye por favor quiero...")
        do {
            $antes = $texto;
            $texto = preg_replace(self::RELLENO_INICIAL, '', $texto);
        } while ($texto !== $antes);

        return trim($texto);
    }

    /** Limpia el dato del comando: quita artículos y títulos del inicio ("al doctor García" -> "garcia"). */
    private static function dato(string $dato): string
    {
        $dato = trim($dato);
        do {
            $antes = $dato;
            $dato = preg_replace('/^(?:a|al|el|la|los|las|de|del|en|para|doctor|doctora|dr|dra|senor|senora|paciente|especialidad)\s+/u', '', $dato);
        } while ($dato !== $antes);

        return trim($dato);
    }

    /** "hoy" / "mañana" -> fecha en formato Y-m-d */
    private static function fecha(string $palabra): string
    {
        return in_array($palabra, ['manana', 'mañana'], true)
            ? today()->addDay()->toDateString()
            : today()->toDateString();
    }

    private static function sinAcceso(string $pantalla): array
    {
        return ['error' => "No tiene acceso a «{$pantalla}»"];   // flujo 7a
    }

    /** Quita la "s" final de cada palabra para comparar singular y plural ("pacientes" = "paciente"). */
    private static function singular(string $texto): string
    {
        return implode(' ', array_map(
            fn ($p) => mb_strlen($p) > 3 && str_ends_with($p, 's') ? mb_substr($p, 0, -1) : $p,
            explode(' ', $texto)
        ));
    }

    /** Nombre sin tildes, en minúsculas y en singular, para comparar. */
    private static function comparable(string $texto): string
    {
        return self::singular(self::normalizar($texto));
    }

    // ------------------------------------------------------------------
    // "abrir [módulo]": usa las opciones del menú (config/modulos.php)
    // ------------------------------------------------------------------

    /** Devuelve ['url'] si encontró una opción permitida, ['error'] si existe pero no tiene acceso, o null. */
    private static function abrirModulo(string $nombre, Usuario $usuario): ?array
    {
        $buscado = self::comparable($nombre);
        if (mb_strlen($buscado) < 3) {
            return null;
        }

        $permitidas = collect($usuario->modulosPermitidos())->filter(fn ($o) => $o['ruta']);
        $esPaciente = $usuario->paciente()->exists();

        // Candidatos: cada opción del menú (con su título), "Mi perfil" y los títulos de los grupos del menú
        $candidatos = [];
        foreach (config('modulos.opciones', []) as $opcion) {
            $titulos = [$opcion['titulo']];
            if (($opcion['cu'] ?? null) === 'CU08' && $esPaciente) {
                $titulos[] = 'Mis citas';   // el menú del paciente la muestra con ese nombre
            }
            $permitida = $permitidas->first(fn ($o) => $o['titulo'] === $opcion['titulo']);
            foreach ($titulos as $titulo) {
                $candidatos[] = ['titulo' => $titulo, 'ruta' => $permitida['ruta'] ?? null];
            }
        }
        $candidatos[] = ['titulo' => 'Mi perfil', 'ruta' => 'perfil.ver'];
        foreach (config('modulos.grupos', []) as $clave => $grupo) {
            // El grupo abre su primera opción permitida
            $primera = $permitidas->first(fn ($o) => $o['grupo'] === $clave);
            $candidatos[] = ['titulo' => $grupo['titulo'], 'ruta' => $primera['ruta'] ?? null];
        }

        // Prioridad: nombre igual > el nombre dicho es el comienzo del título > el título contiene lo dicho
        $reglas = [
            fn ($t) => $t === $buscado,
            fn ($t) => mb_strlen($buscado) >= 4 && str_starts_with($t, $buscado),
            fn ($t) => mb_strlen($buscado) >= 5 && str_contains($t, $buscado),
        ];
        foreach ($reglas as $regla) {
            $encontrados = array_filter($candidatos, fn ($c) => $regla(self::comparable($c['titulo'])));
            if ($encontrados === []) {
                continue;
            }
            // Si alguno está permitido, se abre; si ninguno, el usuario no tiene acceso (flujo 7a)
            foreach ($encontrados as $candidato) {
                if ($candidato['ruta']) {
                    return ['url' => route($candidato['ruta'])];
                }
            }
            return self::sinAcceso(reset($encontrados)['titulo']);
        }

        return null;
    }

    /** Un ejemplo real para la ayuda: el título de la primera opción del menú del usuario. */
    private static function ejemploModulo(Usuario $usuario): string
    {
        $primera = collect($usuario->modulosPermitidos())->first(fn ($o) => $o['ruta']);

        return $primera ? mb_strtolower($primera['titulo']) : 'mi perfil';
    }

    /** Especialidad activa por su nombre (igual, el comienzo o una palabra del nombre). */
    private static function buscarEspecialidad(string $nombre): ?Especialidad
    {
        $buscado = self::comparable($nombre);
        $especialidades = Especialidad::listarActivas();

        return $especialidades->first(fn ($e) => self::comparable($e->nombre) === $buscado)
            ?? $especialidades->first(fn ($e) => mb_strlen($buscado) >= 4 && str_starts_with(self::comparable($e->nombre), $buscado))
            ?? $especialidades->first(fn ($e) => mb_strlen($buscado) >= 5 && str_contains(self::comparable($e->nombre), $buscado));
    }
}
