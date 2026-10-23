<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('codigo') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">
    {{-- Página de error: no muestra detalles internos al usuario --}}
    <div class="text-center" style="max-width: 460px">
        <i class="bi @yield('icono', 'bi-exclamation-octagon') text-primary" style="font-size: 3.5rem"></i>
        <h1 class="display-6 fw-semibold mt-2">@yield('codigo')</h1>
        <p class="lead">@yield('mensaje')</p>
        <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house me-1"></i> Volver al inicio</a>
    </div>
</body>
</html>
