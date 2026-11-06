{{-- CU02 Iniciar Sesión, flujo alternativo "Recuperar contraseña" — UI_RecuperarContrasena (pedir el correo) --}}
@extends('compartido.publica')

@section('titulo', 'Recuperar contraseña')

@section('contenido')
    <h2 class="h4 fw-semibold mb-1">Recuperar contraseña</h2>
    <p class="text-muted mb-4">
        Escriba el correo electrónico de su cuenta. Le enviaremos un enlace para crear una contraseña nueva.
    </p>

    @if ($errors->any())
        <div class="alert alert-danger py-2" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('contrasena.enviar') }}" class="needs-validation" novalidate>
        @csrf

        <div class="mb-4">
            <label for="correo" class="form-label">Correo electrónico</label>
            <div class="input-group has-validation">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" id="correo" name="correo" value="{{ old('correo') }}"
                       class="form-control @error('correo') is-invalid @enderror"
                       maxlength="100" required autofocus autocomplete="email">
                <div class="invalid-feedback">Ingrese un correo electrónico válido.</div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-send me-1"></i> Enviar enlace
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="small"><i class="bi bi-arrow-left me-1"></i> Volver a iniciar sesión</a>
    </div>

    <p class="text-center text-muted small mt-4 mb-0">
        Si su cuenta no tiene correo registrado, acuda a recepción para restablecer su contraseña.
    </p>
@endsection
