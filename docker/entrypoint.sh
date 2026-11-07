#!/bin/sh
# =====================================================================
# Se ejecuta cada vez que arranca el contenedor.
# =====================================================================
set -e

# 1. Puerto: Railway indica en qué puerto escuchar con la variable PORT (por defecto 8080)
PORT="${PORT:-8080}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# 1b. Apache debe tener UN solo MPM (módulo que decide cómo atiende las peticiones).
#     PHP (mod_php) necesita "prefork". En Railway aparecía otro MPM activo y Apache no
#     arrancaba (error AH00534 "More than one MPM loaded"), así que se quitan los demás.
echo "MPM activos al arrancar: $(ls /etc/apache2/mods-enabled/ | grep '^mpm_.*\.load$' | tr '\n' ' ')"
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
a2enmod -q mpm_prefork

# 2. Aviso si falta la clave de la aplicación (sin ella no funcionan las sesiones)
if [ -z "$APP_KEY" ]; then
    echo "ATENCION: falta la variable APP_KEY (generarla con: php artisan key:generate --show)"
fi

# 3. Caché de configuración, rutas y vistas (lee las variables de entorno de ESTE arranque)
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

# 4. Scheduler de Laravel en segundo plano (routes/console.php):
#    - cada 5 min: recordatorios de cita 24 h antes (RF31)
#    - cada minuto: liberar reservas de pago vencidas (CU21)
#    Corre como www-data para que los archivos que cree en storage sigan siendo de Apache.
#    Sus mensajes salen en los logs de Railway junto con los de Apache.
runuser -u www-data -- php artisan schedule:work &

# 5. Iniciar Apache en primer plano
exec apache2-foreground
