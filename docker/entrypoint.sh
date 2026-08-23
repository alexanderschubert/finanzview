#!/bin/sh
set -eu
cd /var/www/html
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
if [ ! -f .env ]; then cp .env.docker .env; fi
php artisan key:generate --force
php artisan config:clear
php artisan cache:clear || true
php artisan migrate --force
exec "$@"
