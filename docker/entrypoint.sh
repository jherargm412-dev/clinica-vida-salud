#!/bin/sh
# =====================================================================
# Se ejecuta cada vez que arranca el contenedor.
# =====================================================================
set -e

# 1. Puerto: Railway indica en qué puerto escuchar con la variable PORT (por defecto 8080)
PORT="${PORT:-8080}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# 2. Aviso si falta la clave de la aplicación (sin ella no funcionan las sesiones)
if [ -z "$APP_KEY" ]; then
    echo "ATENCION: falta la variable APP_KEY (generarla con: php artisan key:generate --show)"
fi

# 3. Caché de configuración, rutas y vistas (lee las variables de entorno de ESTE arranque)
php artisan config:cache
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

# 4. Iniciar Apache en primer plano
exec apache2-foreground
