<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @include('compartido._tema')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Páginas que llevan un token en la URL (restablecer contraseña) no lo envían a otros sitios (CDN) --}}
    @stack('meta')
    <title>@yield('titulo') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
{{-- Plantilla de las páginas públicas (sin sesión): login (CU02), recuperar y restablecer contraseña --}}
{{-- Modo claro / oscuro (public/js/app.js, punto 6) --}}
<button type="button" class="btn btn-outline-secondary rounded-circle position-fixed top-0 end-0 m-3" style="z-index: 10"
        data-cambiar-tema title="Cambiar a modo oscuro / claro" aria-label="Cambiar entre modo claro y oscuro">
    <i class="bi bi-moon-stars"></i>
</button>
<div class="container-fluid cvs-login">
    <div class="row min-vh-100">
        {{-- Panel de la marca (solo en pantallas medianas o grandes) --}}
        <div class="col-md-5 col-lg-6 panel-marca d-none d-md-flex flex-column justify-content-center p-5">
            <i class="bi bi-heart-pulse-fill mb-3"></i>
            <h1 class="fw-bold">Clínica Vida Salud</h1>
            <p class="lead mb-0">Sistema web de gestión de citas médicas.</p>
        </div>

        {{-- Contenido de cada página --}}
        <div class="col-md-7 col-lg-6 d-flex align-items-center justify-content-center p-4">
            <div class="w-100" style="max-width: 400px">
                <div class="text-center d-md-none mb-4 text-primary">
                    <i class="bi bi-heart-pulse-fill fs-1"></i>
                    <h4 class="fw-bold">Clínica Vida Salud</h4>
                </div>

                @yield('contenido')
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
