{{-- CU19 · Paso 2 (y 2a): reportes disponibles, agrupados en Citas, Pacientes y Pagos y caja (pestañas arriba) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 d-flex flex-wrap align-items-center column-gap-4 row-gap-2">
        @foreach ($reportes as $grupo => $deGrupo)
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small text-uppercase text-muted fw-semibold">{{ $grupo }}</span>
                <div class="nav nav-pills gap-1 small">
                    @foreach ($deGrupo as $clave => $r)
                        <a href="{{ route('reportes.index', ['tipo' => $clave]) }}"
                           class="nav-link py-1 px-3 {{ $tipo === $clave ? 'active' : 'border' }}"
                           @if ($tipo === $clave) aria-current="page" @endif>{{ $r['titulo'] }}</a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
