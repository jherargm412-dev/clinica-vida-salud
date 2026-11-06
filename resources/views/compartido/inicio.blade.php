@extends('compartido.app')

@section('titulo', 'Inicio')

@section('contenido')
    {{-- UI_Dashboard: bienvenida y accesos según el rol --}}
    <div class="mb-4">
        <h1 class="h3 fw-semibold mb-1">Hola, {{ auth()->user()->nombres }}</h1>
        <p class="text-muted mb-0">
            Rol: <span class="badge text-bg-light border">{{ auth()->user()->rol->nombre }}</span>
            · {{ now()->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
        </p>
    </div>

    @if (count($grupos) === 0)
        <div class="alert alert-info">Su rol todavía no tiene módulos asignados.</div>
    @endif

    {{-- Un bloque por grupo (paquete), en el mismo orden que el menú lateral --}}
    @foreach ($grupos as $grupo)
        <section class="mb-4">
            <h2 class="cvs-titulo-grupo">
                <i class="bi {{ $grupo['icono'] }}"></i> {{ $grupo['titulo'] }}
            </h2>
            <div class="row g-3">
                @foreach ($grupo['opciones'] as $modulo)
                    <div class="col-sm-6 col-xl-4">
                        @if ($modulo['ruta'])
                            <a href="{{ route($modulo['ruta']) }}" class="card cvs-modulo h-100 text-decoration-none text-body">
                        @else
                            <div class="card cvs-modulo pendiente h-100">
                        @endif
                            <div class="card-body d-flex gap-3 align-items-center">
                                <div class="icono"><i class="bi {{ $modulo['icono'] }}"></i></div>
                                <div>
                                    <h3 class="h6 fw-semibold mb-0">{{ $modulo['titulo'] }}</h3>
                                    @unless ($modulo['ruta'])
                                        <span class="badge text-bg-secondary mt-1">Próximamente</span>
                                    @endunless
                                </div>
                            </div>
                        @if ($modulo['ruta'])
                            </a>
                        @else
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection
