{{-- CU24 Consultar Asistente Virtual · actor Paciente (con sesión, plantilla interna) --}}
@extends('compartido.app')

@section('titulo', 'Asistente virtual')

@section('contenido')
    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-0">Asistente virtual</h1>
        <p class="text-muted mb-0">Resuelve tus dudas sobre citas, pagos, notificaciones, tu cuenta y la clínica.</p>
    </div>

    <div style="max-width: 820px">
        @include('asistencia_usuario.consultar_asistente_virtual._chat')
    </div>
@endsection
