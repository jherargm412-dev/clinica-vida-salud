{{-- CU24 Consultar Asistente Virtual · actor Visitante (sin sesión, plantilla pública del login) --}}
@extends('compartido.publica')

@section('titulo', 'Asistente virtual')

@section('contenido')
    <div class="mb-3">
        <a href="{{ route('login') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Volver a iniciar sesión
        </a>
    </div>

    @include('asistencia_usuario.consultar_asistente_virtual._chat')
@endsection
