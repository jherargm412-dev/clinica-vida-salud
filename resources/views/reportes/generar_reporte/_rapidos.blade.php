{{--
    CU19 · Atajos de fechas ("Rápido"): llenan los filtros de fecha y generan el reporte (public/js/app.js, sección 12).
    Son el mismo filtro que "Período" (o que las fechas de registro, en pacientes), solo que con un clic.
    Va fuera de la parte plegable de los filtros: así se puede cambiar de período sin abrirlos.
--}}
@php
    $v = fn (string $campo) => (string) ($valores[$campo] ?? '');
    $hoy = now();
    $lunes = $hoy->copy()->startOfWeek(\Carbon\Carbon::MONDAY);

    $rapidos = match ($tipo) {
        'agenda' => [
            'Hoy'         => ['periodo' => 'diario',  'fecha' => $hoy->toDateString()],
            'Mañana'      => ['periodo' => 'diario',  'fecha' => $hoy->copy()->addDay()->toDateString()],
            'Esta semana' => ['periodo' => 'semanal', 'fecha' => $hoy->toDateString()],
        ],
        // Pacientes: por fecha de registro. "Todos" borra las fechas.
        'pacientes' => [
            'Esta semana' => ['fecha_inicio' => $lunes->toDateString(), 'fecha_fin' => $lunes->copy()->addDays(6)->toDateString()],
            'Este mes'    => ['fecha_inicio' => $hoy->copy()->startOfMonth()->toDateString(), 'fecha_fin' => $hoy->copy()->endOfMonth()->toDateString()],
            'Este año'    => ['fecha_inicio' => $hoy->copy()->startOfYear()->toDateString(), 'fecha_fin' => $hoy->copy()->endOfYear()->toDateString()],
            'Todos'       => ['fecha_inicio' => '', 'fecha_fin' => ''],
        ],
        default => [
            'Hoy'         => ['periodo' => 'diario',  'fecha' => $hoy->toDateString()],
            'Esta semana' => ['periodo' => 'semanal', 'fecha' => $hoy->toDateString()],
            'Este mes'    => ['periodo' => 'mensual', 'mes' => $hoy->format('Y-m')],
        ],
    };

    // ¿Los filtros actuales son los de este atajo? En "semanal" basta con que la fecha caiga en esta semana.
    $activo = function (array $r) use ($v, $lunes) {
        if (($r['periodo'] ?? null) === 'semanal') {
            return $v('periodo') === 'semanal' && rescue(fn () => \Carbon\Carbon::createFromFormat('Y-m-d', $v('fecha'))
                ->startOfWeek(\Carbon\Carbon::MONDAY)->isSameDay($lunes), false, false);
        }
        foreach ($r as $campo => $valor) {
            if ($v($campo) !== (string) $valor) {
                return false;
            }
        }

        return true;
    };
@endphp

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <span class="small text-muted">Rápido:</span>
    @foreach ($rapidos as $texto => $r)
        <button type="button" class="btn btn-sm rounded-pill {{ $activo($r) ? 'btn-primary' : 'btn-outline-secondary' }}"
                data-periodo-rapido="{{ json_encode($r) }}">{{ $texto }}</button>
    @endforeach
</div>
