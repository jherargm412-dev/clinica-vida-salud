{{-- Menú lateral según los permisos del rol, agrupado por paquete (config/modulos.php).
     Se incluye dos veces (pantallas grandes y celular): $menuId hace que los ids no se repitan.
     Los grupos se abren/cierran con el "collapse" de Bootstrap; public/js/app.js (punto 9)
     recuerda en este navegador cuáles dejó abiertos. --}}
@php
    $menuId = $menuId ?? 'menu';
    $grupos = auth()->user()->gruposMenu();

    // ¿La página actual pertenece a esta opción? (patrones "activo" o, si no hay, el prefijo de su ruta)
    $estaActiva = fn (array $opcion) => $opcion['ruta'] && request()->routeIs(
        ...($opcion['activo'] ?? [\Illuminate\Support\Str::beforeLast($opcion['ruta'], '.') . '.*'])
    );
@endphp

<nav class="nav flex-column cvs-menu">
    <a class="nav-link {{ request()->routeIs('inicio') ? 'active' : '' }}" href="{{ route('inicio') }}">
        <i class="bi bi-house"></i> Inicio
    </a>

    <div class="seccion">Módulos</div>
    @foreach ($grupos as $clave => $grupo)
        @php
            // Un grupo con una sola opción se muestra como enlace directo (sin desplegable)
            $desplegable = count($grupo['opciones']) > 1;
            $contieneActiva = collect($grupo['opciones'])->contains($estaActiva);
            // Abierto si tiene la página actual o si es el único grupo del rol
            $abierto = $contieneActiva || count($grupos) === 1;
            $idGrupo = "{$menuId}-grupo-{$clave}";
        @endphp

        @if ($desplegable)
            <button type="button" class="nav-link cvs-grupo {{ $abierto ? '' : 'collapsed' }} {{ $contieneActiva ? 'contiene-activa' : '' }}"
                    data-bs-toggle="collapse" data-bs-target="#{{ $idGrupo }}"
                    aria-expanded="{{ $abierto ? 'true' : 'false' }}" aria-controls="{{ $idGrupo }}">
                <i class="bi {{ $grupo['icono'] }}"></i>
                <span class="flex-grow-1 text-start">{{ $grupo['titulo'] }}</span>
                <i class="bi bi-chevron-down cvs-flecha"></i>
            </button>
            <div class="collapse {{ $abierto ? 'show' : '' }}" id="{{ $idGrupo }}" data-grupo-menu="{{ $clave }}">
                <div class="cvs-subopciones">
        @endif

        @foreach ($grupo['opciones'] as $opcion)
            @php
                $tituloOpcion = ($opcion['cu'] ?? null) === 'CU08' && auth()->user()->rol?->nombre === 'Paciente'
                    ? 'Mis citas'
                    : $opcion['titulo'];
            @endphp
            @if ($opcion['ruta'])
                <a class="nav-link {{ $estaActiva($opcion) ? 'active' : '' }}" href="{{ route($opcion['ruta']) }}">
                    <i class="bi {{ $opcion['icono'] }}"></i> {{ $tituloOpcion }}
                </a>
            @else
                <span class="nav-link disabled" title="Próximamente">
                    <i class="bi {{ $opcion['icono'] }}"></i> {{ $tituloOpcion }}
                </span>
            @endif
        @endforeach

        @if ($desplegable)
                </div>
            </div>
        @endif
    @endforeach

    <hr class="my-2">
    {{-- CU04 Configurar perfil de usuario: visible para todos (no depende de permisos) --}}
    <a class="nav-link {{ request()->routeIs('perfil.*') ? 'active' : '' }}" href="{{ route('perfil.ver') }}">
        <i class="bi bi-person-gear"></i> Mi perfil
    </a>
</nav>
