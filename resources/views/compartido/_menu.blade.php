{{-- Menú según los permisos del rol (config/modulos.php) --}}
<nav class="nav flex-column cvs-menu">
    <a class="nav-link {{ request()->routeIs('inicio') ? 'active' : '' }}" href="{{ route('inicio') }}">
        <i class="bi bi-house"></i> Inicio
    </a>

    <div class="seccion">Módulos</div>
    @foreach (auth()->user()->modulosPermitidos() as $modulo)
        @if ($modulo['ruta'])
            <a class="nav-link {{ request()->routeIs(\Illuminate\Support\Str::beforeLast($modulo['ruta'], '.') . '.*') ? 'active' : '' }}"
               href="{{ route($modulo['ruta']) }}">
                <i class="bi {{ $modulo['icono'] }}"></i> {{ $modulo['titulo'] }}
            </a>
        @else
            <span class="nav-link disabled" title="Próximamente">
                <i class="bi {{ $modulo['icono'] }}"></i> {{ $modulo['titulo'] }}
            </span>
        @endif
    @endforeach
</nav>
