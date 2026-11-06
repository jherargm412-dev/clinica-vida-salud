{{-- CU13 Registrar Cita · UI_cita: asistente por pasos (incluye CU12 para elegir el horario y lleva a CU21 para pagar) --}}
@extends('compartido.app')

@section('titulo', 'Registrar cita')

@section('contenido')
    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-1">Registrar cita</h1>
        <p class="text-muted mb-0">
            Elija {{ $esPaciente ? '' : 'el paciente, ' }}la especialidad, el médico y un horario libre.
            @if ($puedePagar) Al confirmar, la cita reserva el horario 10 minutos hasta que se pague. @endif
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @foreach ($avisos as $aviso)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i> {{ $aviso }}</div>
    @endforeach

    <div class="card border-0 shadow-sm">
        {{-- Barra de pasos --}}
        <div class="card-header bg-transparent py-3">
            @include('citas.registrar_cita._pasos', ['pasos' => $pasos, 'actual' => $paso, 'urlsPasos' => $urlsPasos])
        </div>

        {{-- Contenido del paso actual (resources/views/citas/registrar_cita/pasos/) --}}
        <div class="card-body p-4">
            @include('citas.registrar_cita.pasos.' . $paso)
        </div>

        {{-- Atrás: vuelve al paso anterior conservando lo ya elegido --}}
        @if ($urlAtras)
            <div class="card-footer bg-transparent py-3">
                <a href="{{ $urlAtras }}" class="btn btn-link text-decoration-none px-0">
                    <i class="bi bi-arrow-left me-1"></i> Atrás
                </a>
            </div>
        @endif
    </div>
@endsection
