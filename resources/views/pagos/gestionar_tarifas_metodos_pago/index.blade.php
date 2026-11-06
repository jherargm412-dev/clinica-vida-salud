{{-- CU20 Gestionar Tarifas y Métodos de Pago · UI_TarifasMetodosPago: mostrarTarifasYMetodos (paso 2) --}}
@extends('compartido.app')

@section('titulo', 'Tarifas y métodos de pago')

@section('contenido')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-semibold mb-0">Tarifas y métodos de pago</h1>
            <p class="text-muted mb-0">Cuánto se cobra por la consulta de cada especialidad y cómo se puede pagar</p>
        </div>
        {{-- Paso 3: "Nueva tarifa" --}}
        <a href="{{ route('tarifas.crear') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva tarifa
        </a>
    </div>

    {{-- 1. Tarifas: especialidad, monto, vigente desde y estado --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 fw-semibold mb-3"><i class="bi bi-cash-coin me-1"></i> Tarifas de consulta</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Especialidad</th>
                            <th class="text-end">Monto</th>
                            <th>Vigente desde</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tarifas as $tarifa)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $tarifa->especialidad->nombre }}
                                    @unless ($tarifa->especialidad->activo)
                                        <span class="text-muted fw-normal small">(especialidad inactiva)</span>
                                    @endunless
                                </td>
                                <td class="text-end text-nowrap">Bs {{ number_format($tarifa->monto, 2) }}</td>
                                <td>{{ $tarifa->vigente_desde->format('d/m/Y') }}</td>
                                <td>
                                    @if ($tarifa->activo)
                                        <span class="badge text-bg-success">Activa</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    {{-- Solo las tarifas activas se modifican o desactivan; las inactivas son historial --}}
                                    @if ($tarifa->activo)
                                        <a href="{{ route('tarifas.editar', $tarifa) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Modificar
                                        </a>
                                        <form method="POST" action="{{ route('tarifas.desactivar', $tarifa) }}" class="d-inline"
                                              data-confirmar="¿Desactivar la tarifa de {{ $tarifa->especialidad->nombre }}? La especialidad no podrá recibir nuevas citas hasta que tenga una tarifa activa.">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-slash-circle"></i> Desactivar
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Historial</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Aún no hay tarifas registradas</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 2. Métodos de pago: nombre, si es en línea y estado --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 fw-semibold mb-3"><i class="bi bi-credit-card me-1"></i> Métodos de pago</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>En línea</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($metodos as $metodo)
                            <tr>
                                <td class="fw-semibold">{{ $metodo->nombre }}</td>
                                <td>{{ $metodo->en_linea ? 'Sí' : 'No' }}</td>
                                <td>
                                    @if ($metodo->activo)
                                        <span class="badge text-bg-success">Habilitado</span>
                                    @else
                                        <span class="badge text-bg-secondary">Deshabilitado</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    {{-- Envía el estado nuevo: activo = 0 (deshabilitar) o 1 (habilitar) --}}
                                    <form method="POST" action="{{ route('metodos_pago.estado', $metodo) }}" class="d-inline"
                                          @if ($metodo->activo) data-confirmar="¿Deshabilitar el método {{ $metodo->nombre }}?" @endif>
                                        @csrf
                                        <input type="hidden" name="activo" value="{{ $metodo->activo ? 0 : 1 }}">
                                        @if ($metodo->activo)
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-toggle-off"></i> Deshabilitar
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-toggle-on"></i> Habilitar
                                            </button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
