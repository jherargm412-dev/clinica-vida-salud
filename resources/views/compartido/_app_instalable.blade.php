{{-- RF30: datos para "Agregar a la pantalla de inicio". En iPhone/iPad las notificaciones push
     solo funcionan si el paciente abre la clínica desde ese ícono (iOS 16.4 o posterior). --}}
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#0f766e">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('img/icono-192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('img/icono-192.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Vida Salud">
