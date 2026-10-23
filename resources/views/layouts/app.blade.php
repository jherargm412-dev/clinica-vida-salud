<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('estilos')
</head>
<body>
    {{-- Barra superior --}}
    <nav class="navbar navbar-dark cvs-navbar sticky-top">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-link text-white d-lg-none p-0 me-1" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#menuMovil" aria-label="Abrir menú">
                    <i class="bi bi-list fs-3"></i>
                </button>
                <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('inicio') }}">
                    <i class="bi bi-heart-pulse-fill"></i> Clínica Vida Salud
                </a>
            </div>

            <div class="dropdown">
                <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center gap-2"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span class="d-none d-sm-inline">{{ auth()->user()->nombre_completo }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li class="px-3 py-2">
                        <div class="fw-semibold">{{ auth()->user()->nombre_completo }}</div>
                        <small class="text-muted">{{ auth()->user()->rol->nombre ?? '' }} · {{ auth()->user()->nombre_usuario }}</small>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><span class="dropdown-item disabled"><i class="bi bi-person me-2"></i>Mi perfil <small>(próximamente)</small></span></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        {{-- Menú lateral (pantallas grandes) --}}
        <aside class="cvs-sidebar d-none d-lg-block p-3">
            @include('layouts._menu')
        </aside>

        {{-- Menú lateral (celular) --}}
        <div class="offcanvas offcanvas-start" tabindex="-1" id="menuMovil">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title">Menú</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
            </div>
            <div class="offcanvas-body">
                @include('layouts._menu')
            </div>
        </div>

        {{-- Contenido de cada pantalla --}}
        <main class="flex-grow-1 p-3 p-md-4" style="min-width: 0">
            @if (session('ok'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-1"></i> {{ session('ok') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('contenido')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
