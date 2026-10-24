<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @include('compartido._tema')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
{{-- CU02 Iniciar Sesión — UI_login --}}
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

        {{-- Formulario --}}
        <div class="col-md-7 col-lg-6 d-flex align-items-center justify-content-center p-4">
            <div class="w-100" style="max-width: 400px">
                <div class="text-center d-md-none mb-4 text-primary">
                    <i class="bi bi-heart-pulse-fill fs-1"></i>
                    <h4 class="fw-bold">Clínica Vida Salud</h4>
                </div>

                <h2 class="h4 fw-semibold mb-1">Iniciar sesión</h2>
                <p class="text-muted mb-4">Ingrese con su nombre de usuario o correo electrónico.</p>

                @if (session('ok'))
                    <div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i> {{ session('ok') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger py-2" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.intentar') }}" class="needs-validation" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="credencial" class="form-label">Usuario o correo electrónico</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" id="credencial" name="credencial" value="{{ old('credencial') }}"
                                   class="form-control @error('credencial') is-invalid @enderror"
                                   maxlength="100" required autofocus autocomplete="username">
                            <div class="invalid-feedback">Ingrese su nombre de usuario o correo electrónico.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="contrasena" name="contrasena"
                                   class="form-control" maxlength="100" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" data-mostrar-contrasena="contrasena"
                                    aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                            <div class="invalid-feedback">Ingrese su contraseña.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                    </button>
                </form>

                <p class="text-center text-muted small mt-4 mb-0">
                    Tras 3 intentos fallidos la cuenta se bloquea y solo el administrador puede desbloquearla.
                </p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
