{{-- CU02 Iniciar Sesión, flujo alternativo "Recuperar contraseña" — UI_RestablecerContrasena (nueva contraseña) --}}
@extends('compartido.publica')

@section('titulo', 'Restablecer contraseña')

{{-- La URL lleva el token: así el navegador no lo envía a otros sitios (CDN de Bootstrap) --}}
@push('meta')
    <meta name="referrer" content="no-referrer">
@endpush

@section('contenido')
    <h2 class="h4 fw-semibold mb-1">Restablecer contraseña</h2>
    <p class="text-muted mb-4">
        Cuenta: <strong>{{ $usuario->nombre_usuario }}</strong>. Escriba su nueva contraseña.
    </p>

    @if ($errors->any())
        <div class="alert alert-danger py-2" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('contrasena.guardar', $token) }}" class="needs-validation" novalidate>
        @csrf

        <div class="mb-3">
            <label for="contrasena" class="form-label">Nueva contraseña</label>
            <input type="password" id="contrasena" name="contrasena" maxlength="100" required autofocus
                   autocomplete="new-password" class="form-control @error('contrasena') is-invalid @enderror">
            <div class="invalid-feedback">Ingrese la nueva contraseña.</div>
            {{-- Requisitos que se marcan en verde al escribir (public/js/app.js, punto 4) --}}
            <ul class="list-unstyled small text-muted mt-2 mb-0" data-requisitos-contrasena="contrasena">
                <li data-regla="largo"><i class="bi bi-circle"></i> Al menos 8 caracteres</li>
                <li data-regla="mayuscula"><i class="bi bi-circle"></i> Una letra mayúscula</li>
                <li data-regla="minuscula"><i class="bi bi-circle"></i> Una letra minúscula</li>
                <li data-regla="numero"><i class="bi bi-circle"></i> Un número</li>
                <li data-regla="especial"><i class="bi bi-circle"></i> Un carácter especial (! @ # $ % &amp; *)</li>
            </ul>
        </div>

        <div class="mb-3">
            <label for="contrasena_confirmation" class="form-label">Confirmar nueva contraseña</label>
            <input type="password" id="contrasena_confirmation" name="contrasena_confirmation" maxlength="100" required
                   autocomplete="new-password" class="form-control">
            <div class="invalid-feedback">Confirme la nueva contraseña.</div>
        </div>

        <div class="form-check mb-4">
            {{-- Muestra u oculta las 2 contraseñas a la vez (public/js/app.js, punto 5) --}}
            <input class="form-check-input" type="checkbox" id="mostrarContrasenas"
                   data-mostrar-contrasenas="contrasena,contrasena_confirmation">
            <label class="form-check-label" for="mostrarContrasenas">Mostrar contraseñas</label>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2">
            <i class="bi bi-key me-1"></i> Guardar contraseña
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}" class="small"><i class="bi bi-arrow-left me-1"></i> Volver a iniciar sesión</a>
    </div>
@endsection
