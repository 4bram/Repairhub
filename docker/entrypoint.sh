#!/bin/sh
set -e
cd /var/www/html

# Carpetas que Laravel necesita poder escribir
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Cachea la configuracion con las variables de entorno que Render inyecta al arrancar
php artisan config:cache

# Crea la tabla de tokens de Sanctum si no existe (el resto de la BD ya vive en Supabase)
php artisan migrate --force || echo "AVISO: migrate fallo; revisa las variables DB_*"

# Los comandos anteriores corren como root: devolver los archivos a Apache
chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground