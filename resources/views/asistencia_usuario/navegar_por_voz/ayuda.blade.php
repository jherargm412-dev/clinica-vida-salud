{{-- CU25 Navegar por Comando de Voz · flujo 6b: comandos disponibles para el rol del usuario --}}
@extends('compartido.app')

@section('titulo', 'Comandos de voz')

@section('contenido')
    <div class="mb-3">
        <h1 class="h3 fw-semibold mb-0">Comandos de voz</h1>
        <p class="text-muted mb-0">
            Presione el botón <i class="bi bi-mic"></i> de la barra superior y diga uno de estos comandos.
            Los comandos solo buscan y abren pantallas: nunca registran, modifican ni cancelan nada.
        </p>
    </div>

    <div class="card border-0 shadow-sm" style="max-width: 900px">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Comando</th>
                            <th>Ejemplo</th>
                            <th>Qué hace</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comandos as [$comando, $ejemplo, $descripcion])
                            <tr>
                                <td class="fw-semibold text-nowrap">{{ $comando }}</td>
                                <td class="text-primary text-nowrap"><i class="bi bi-mic me-1"></i>{{ $ejemplo }}</td>
                                <td class="text-muted">{{ $descripcion }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0" style="max-width: 900px">
        Funciona en Chrome, Edge y Safari. La primera vez, el navegador pedirá permiso para usar el micrófono.
        En Chrome, la voz se procesa en el servicio de reconocimiento de Google: no dicte datos personales ni clínicos.
    </p>
@endsection
