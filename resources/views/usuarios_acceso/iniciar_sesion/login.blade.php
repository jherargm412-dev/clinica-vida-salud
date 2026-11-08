{{-- CU02 Iniciar Sesión — UI_login --}}
@extends('compartido.publica')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <h2 class="h4 fw-semibold mb-4">Iniciar sesión</h2>

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

        <div class="mb-2">
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

        {{-- Flujo alternativo "Recuperar contraseña" --}}
        <div class="text-end mb-4">
            <a href="{{ route('contrasena.solicitar') }}" class="small">¿Olvidó su contraseña?</a>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
        </button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        Tras 3 intentos fallidos la cuenta se bloquea. Puede desbloquearla restableciendo su contraseña
        o pidiéndolo al administrador.
    </p>
@endsection
