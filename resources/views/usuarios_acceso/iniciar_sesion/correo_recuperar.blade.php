{{-- CU02 (Recuperar contraseña): correo con el enlace de un solo uso. HTML simple, sin la plantilla del sistema. --}}
<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #212529; line-height: 1.5">
    <h2 style="color: #0d6efd; margin-bottom: 4px">{{ config('app.name') }}</h2>
    <p>Hola {{ $usuario->nombres }}:</p>
    <p>Recibimos una solicitud para restablecer la contraseña de su cuenta <strong>{{ $usuario->nombre_usuario }}</strong>.</p>

    <p>
        <a href="{{ $enlace }}"
           style="display: inline-block; background: #0d6efd; color: #ffffff; padding: 10px 18px; border-radius: 6px; text-decoration: none">
            Restablecer contraseña
        </a>
    </p>

    <p>El enlace vence en <strong>{{ $minutos }} minutos</strong> y solo se puede usar una vez.
        Si su cuenta estaba bloqueada por intentos fallidos, se desbloqueará al guardar la nueva contraseña.</p>

    <p>Si el botón no funciona, copie esta dirección en su navegador:<br>
        <span style="word-break: break-all; color: #6c757d">{{ $enlace }}</span></p>

    <p>Si usted no pidió este cambio, ignore este correo: su contraseña actual sigue funcionando.</p>

    <p style="color: #6c757d; font-size: 12px">Este es un mensaje automático; no responda a este correo.</p>
</body>
</html>
