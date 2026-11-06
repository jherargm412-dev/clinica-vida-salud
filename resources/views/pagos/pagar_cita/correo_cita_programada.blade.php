{{-- CU21 (paso 8): correo "Cita programada" que recibe el paciente (RF29). HTML simple, sin la plantilla del sistema. --}}
<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #212529; line-height: 1.5">
    <h2 style="color: #0d6efd; margin-bottom: 4px">{{ config('app.name') }}</h2>
    <p>Hola {{ $cita->paciente->nombre_completo }}:</p>
    <p>Recibimos el pago de su consulta. Su cita quedó <strong>programada</strong>.</p>

    <table cellpadding="6" style="border-collapse: collapse">
        <tr><td><strong>Código</strong></td><td>{{ $cita->codigo }}</td></tr>
        <tr><td><strong>Especialidad</strong></td><td>{{ $cita->especialidad->nombre }}</td></tr>
        <tr><td><strong>Médico</strong></td><td>{{ $cita->medico->nombre_completo }}</td></tr>
        <tr><td><strong>Fecha</strong></td><td>{{ $cita->fecha->format('d/m/Y') }}</td></tr>
        <tr><td><strong>Hora</strong></td><td>{{ substr($cita->hora_inicio, 0, 5) }} – {{ substr($cita->hora_fin, 0, 5) }}</td></tr>
    </table>

    <p style="color: #6c757d; font-size: 12px">Este es un mensaje automático; no responda a este correo.</p>
</body>
</html>
