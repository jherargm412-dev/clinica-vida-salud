{{--
    CU19 · mostrarFiltros(filtros): campos de filtro según el reporte elegido.
    $valores: lo que eligió el usuario (o los valores por defecto) · $listas: opciones de los selectores.
--}}
@php
    $v = fn (string $campo) => $valores[$campo] ?? '';
    $periodo = $v('periodo');
    // Los campos que no son del período elegido se ocultan (public/js/app.js los muestra al cambiar el período)
    $oculto = fn (array $periodos) => in_array($periodo, $periodos, true) ? '' : 'd-none';
    $msjFechas = \App\Modulos\reportes\controller\generar_reporte\ReporteController::MSJ_FECHAS;
    $nombreMedico = fn ($m) => $m->usuario?->apellidos . ', ' . $m->usuario?->nombres . ' (' . $m->codigo . ')' . ($m->activo ? '' : ' (inactivo)');

    $conPeriodo = in_array($tipo, ['citas', 'asistencia', 'ingresos', 'cierres'], true);
@endphp

<div class="row g-3">

    {{-- Período: Diario, Semanal, Mensual o Rango de fechas --}}
    @if ($conPeriodo)
        <div class="col-sm-6 col-md-3">
            <label for="periodo" class="form-label">Período</label>
            <select id="periodo" name="periodo" class="form-select @error('periodo') is-invalid @enderror" data-periodo required>
                @foreach (\App\Modulos\reportes\service\Periodo::TIPOS as $clave => $texto)
                    <option value="{{ $clave }}" @selected($periodo === $clave)>{{ $texto }}</option>
                @endforeach
            </select>
            @error('periodo') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-sm-6 col-md-3 {{ $oculto(['diario', 'semanal']) }}" data-periodo-campo="diario semanal">
            <label for="fecha" class="form-label">Fecha</label>
            <input type="date" id="fecha" name="fecha" value="{{ $v('fecha') }}"
                   class="form-control @error('fecha') is-invalid @enderror" required>
            <div class="form-text">En "Semanal": cualquier día de la semana (lunes a domingo).</div>
            <div class="invalid-feedback">{{ $errors->first('fecha') ?: 'Seleccione la fecha' }}</div>
        </div>

        <div class="col-sm-6 col-md-3 {{ $oculto(['mensual']) }}" data-periodo-campo="mensual">
            <label for="mes" class="form-label">Mes</label>
            <input type="month" id="mes" name="mes" value="{{ $v('mes') }}"
                   class="form-control @error('mes') is-invalid @enderror" required>
            <div class="invalid-feedback">{{ $errors->first('mes') ?: 'Seleccione el mes' }}</div>
        </div>

        <div class="col-sm-6 col-md-3 {{ $oculto(['rango']) }}" data-periodo-campo="rango">
            <label for="fecha_inicio" class="form-label">Fecha de inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" data-fecha-inicio="{{ $msjFechas }}" value="{{ $v('fecha_inicio') }}"
                   class="form-control @error('fecha_inicio') is-invalid @enderror" required>
            <div class="invalid-feedback">{{ $errors->first('fecha_inicio') ?: 'Ingrese la fecha de inicio' }}</div>
        </div>

        <div class="col-sm-6 col-md-3 {{ $oculto(['rango']) }}" data-periodo-campo="rango">
            <label for="fecha_fin" class="form-label">Fecha de fin</label>
            <input type="date" id="fecha_fin" name="fecha_fin" data-fecha-fin value="{{ $v('fecha_fin') }}"
                   class="form-control @error('fecha_fin') is-invalid @enderror" required>
            <div class="invalid-feedback">{{ $errors->first('fecha_fin') ?: 'Ingrese la fecha de fin' }}</div>
        </div>
    @endif

    {{-- Agenda (3b): un día o la semana (lunes a domingo) de un médico; fecha y médico obligatorios --}}
    @if ($tipo === 'agenda')
        <div class="col-sm-6 col-md-3">
            <label for="periodo" class="form-label">Agenda</label>
            <select id="periodo" name="periodo" class="form-select @error('periodo') is-invalid @enderror">
                <option value="diario" @selected($periodo !== 'semanal')>Del día</option>
                <option value="semanal" @selected($periodo === 'semanal')>De la semana</option>
            </select>
            @error('periodo') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-6 col-md-3">
            <label for="fecha" class="form-label">Fecha</label>
            <input type="date" id="fecha" name="fecha" value="{{ $v('fecha') }}"
                   class="form-control @error('fecha') is-invalid @enderror" required>
            <div class="form-text">En "De la semana": cualquier día de esa semana.</div>
            <div class="invalid-feedback">{{ $errors->first('fecha') ?: 'Seleccione la fecha' }}</div>
        </div>
    @endif

    {{-- Médico: reporte de citas (solo administrador), agenda diaria e ingresos --}}
    @if (isset($listas['medicos']) && ($tipo !== 'citas' || $completo))
        <div class="col-sm-6 col-md-4">
            <label for="id_medico" class="form-label">Médico</label>
            <select id="id_medico" name="id_medico" class="form-select @error('id_medico') is-invalid @enderror"
                    @if ($tipo === 'agenda') required @endif>
                <option value="">{{ $tipo === 'agenda' ? 'Seleccione un médico' : 'Todos' }}</option>
                @foreach ($listas['medicos'] as $m)
                    <option value="{{ $m->id_medico }}" @selected((string) $v('id_medico') === (string) $m->id_medico)>{{ $nombreMedico($m) }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback">@error('id_medico') {{ $message }} @else Seleccione el médico @enderror</div>
        </div>
    @endif

    {{-- Especialidad: reporte de citas (solo administrador) e ingresos --}}
    @if (isset($listas['especialidades']) && ($tipo !== 'citas' || $completo))
        <div class="col-sm-6 col-md-4">
            <label for="id_especialidad" class="form-label">Especialidad</label>
            <select id="id_especialidad" name="id_especialidad" class="form-select @error('id_especialidad') is-invalid @enderror">
                <option value="">Todas</option>
                @foreach ($listas['especialidades'] as $e)
                    <option value="{{ $e->id_especialidad }}" @selected((string) $v('id_especialidad') === (string) $e->id_especialidad)>
                        {{ $e->nombre }}{{ $e->activo ? '' : ' (inactiva)' }}
                    </option>
                @endforeach
            </select>
            @error('id_especialidad') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    {{-- Estado de la cita (reporte de citas) --}}
    @if ($tipo === 'citas')
        <div class="col-sm-6 col-md-4">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror">
                <option value="">Todos</option>
                @foreach ($listas['estados'] as $estado)
                    <option value="{{ $estado }}" @selected($v('estado') === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
            @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    {{-- Pacientes registrados (3c): estado y rango de fechas de registro (opcional) --}}
    @if ($tipo === 'pacientes')
        <div class="col-sm-6 col-md-4">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" class="form-select @error('estado') is-invalid @enderror">
                <option value="">Todos</option>
                <option value="activo" @selected($v('estado') === 'activo')>Activo</option>
                <option value="inactivo" @selected($v('estado') === 'inactivo')>Inactivo</option>
            </select>
            @error('estado') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-6 col-md-4">
            <label for="fecha_inicio" class="form-label">Registrados desde</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" data-fecha-inicio="{{ $msjFechas }}" value="{{ $v('fecha_inicio') }}"
                   class="form-control @error('fecha_inicio') is-invalid @enderror">
            <div class="invalid-feedback">{{ $errors->first('fecha_inicio') ?: 'Ingrese la fecha de inicio' }}</div>
        </div>
        <div class="col-sm-6 col-md-4">
            <label for="fecha_fin" class="form-label">Hasta</label>
            <input type="date" id="fecha_fin" name="fecha_fin" data-fecha-fin value="{{ $v('fecha_fin') }}"
                   class="form-control @error('fecha_fin') is-invalid @enderror">
            <div class="invalid-feedback">{{ $errors->first('fecha_fin') ?: 'Ingrese la fecha de fin' }}</div>
        </div>
    @endif

    {{-- Método de pago (ingresos) --}}
    @if ($tipo === 'ingresos')
        <div class="col-sm-6 col-md-4">
            <label for="id_metodo_pago" class="form-label">Método de pago</label>
            <select id="id_metodo_pago" name="id_metodo_pago" class="form-select @error('id_metodo_pago') is-invalid @enderror">
                <option value="">Todos</option>
                @foreach ($listas['metodos'] as $metodo)
                    <option value="{{ $metodo->id_metodo_pago }}" @selected((string) $v('id_metodo_pago') === (string) $metodo->id_metodo_pago)>
                        {{ $metodo->nombre }}{{ $metodo->activo ? '' : ' (deshabilitado)' }}
                    </option>
                @endforeach
            </select>
            @error('id_metodo_pago') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif

    {{-- Recepcionista (cierres de caja) --}}
    @if ($tipo === 'cierres')
        <div class="col-sm-6 col-md-4">
            <label for="id_usuario" class="form-label">Recepcionista</label>
            <select id="id_usuario" name="id_usuario" class="form-select @error('id_usuario') is-invalid @enderror">
                <option value="">Todos</option>
                @foreach ($listas['recepcionistas'] as $u)
                    <option value="{{ $u->id_usuario }}" @selected((string) $v('id_usuario') === (string) $u->id_usuario)>
                        {{ $u->apellidos }}, {{ $u->nombres }}{{ $u->activo ? '' : ' (inactivo)' }}
                    </option>
                @endforeach
            </select>
            @error('id_usuario') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @endif
</div>
