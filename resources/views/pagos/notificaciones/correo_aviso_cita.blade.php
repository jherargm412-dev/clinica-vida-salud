{{-- RF31: correo de respaldo de los avisos de cita (programada, reprogramada, cancelada y recordatorio 24 h).
     Se usa cuando el paciente no tiene notificaciones push activas o el push falla. HTML simple, sin la plantilla del sistema. --}}
<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #212529; line-height: 1.5">
    <h2 style="color: #0f766e; margin-bottom: 4px">{{ config('app.name') }}</h2>
    <h3 style="margin-top: 0">{{ $titulo }}</h3>
    <p>Hola {{ $cita->paciente->nombre_completo }}:</p>
    <p>{{ $texto }}</p>

    <table cellpadding="6" style="border-collapse: collapse">
        <tr><td><strong>Código</strong></td><td>{{ $cita->codigo }}</td></tr>
        <tr><td><strong>Especialidad</strong></td><td>{{ $cita->especialidad->nombre }}</td></tr>
        <tr><td><strong>Médico</strong></td><td>{{ $cita->medico->nombre_completo }}</td></tr>
        <tr><td><strong>Fecha</strong></td><td>{{ $cita->fecha->format('d/m/Y') }}</td></tr>
        <tr><td><strong>Hora</strong></td><td>{{ substr($cita->hora_inicio, 0, 5) }} – {{ substr($cita->hora_fin, 0, 5) }}</td></tr>
        <tr><td><strong>Estado</strong></td><td>{{ $cita->estado }}</td></tr>
    </table>

    <p><a href="{{ $url }}" style="color: #0f766e">Ver mis citas</a></p>

    <p style="color: #6c757d; font-size: 12px">
        Puede recibir estos avisos como notificación en su navegador: actívelas en "Mi perfil".<br>
        Este es un mensaje automático; no responda a este correo.
    </p>
</body>
</html>
