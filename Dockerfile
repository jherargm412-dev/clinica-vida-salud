# =====================================================================
# Clínica Vida Salud — imagen Docker de la aplicación (Laravel 12)
# PHP 8.2 + Apache. Se usa igual en la Mac (docker compose) y en Railway.
# =====================================================================
FROM php:8.2-apache

# 1. Extensiones de PHP: pdo_pgsql (PostgreSQL) y opcache (rendimiento)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev unzip \
 && docker-php-ext-install pdo_pgsql opcache \
 && rm -rf /var/lib/apt/lists/*

# 2. Configuración de PHP para producción + zona horaria de Bolivia
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && echo "date.timezone=America/La_Paz" > "$PHP_INI_DIR/conf.d/zona-horaria.ini"

# 3. Apache: sitio que apunta a public/ y mod_rewrite (para las rutas de Laravel)
COPY docker/apache-sitio.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

# 4. Composer (se copia desde su imagen oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 5. Dependencias de PHP (primero solo composer.json/lock para aprovechar la caché de Docker)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# 6. Código de la aplicación (lo excluido está en .dockerignore)
COPY . .

# 7. Autoload optimizado + carpetas que Laravel necesita escribir (dueño: usuario de Apache)
RUN composer dump-autoload --optimize --no-dev --no-interaction \
 && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R ug+rwX storage bootstrap/cache

# 8. Script de arranque (puerto de Railway + caché de configuración)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Puerto por defecto (Railway lo reemplaza con la variable PORT)
ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
