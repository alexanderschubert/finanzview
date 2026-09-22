#!/bin/sh
set -eu

cd /var/www/html

echo "========================================"
echo " FinanzView – Container Start"
echo "========================================"

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data \
    storage \
    bootstrap/cache

# ---------------------------------------------------------
# APP_KEY
# ---------------------------------------------------------

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY fehlt – generiere neuen Application Key"
    php artisan key:generate --force
else
    echo "APP_KEY vorhanden – verwende bestehenden Key"
fi

# ---------------------------------------------------------
# Datenbank
# ---------------------------------------------------------

echo "Warte auf PostgreSQL..."

until php -r '
try {
    $pdo = new PDO(
        "pgsql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"),
        getenv("DB_USERNAME"),
        getenv("DB_PASSWORD"),
        [PDO::ATTR_TIMEOUT => 3]
    );
    $pdo->query("SELECT 1");
    exit(0);
} catch (Throwable $e) {
    exit(1);
}
'; do
    echo "PostgreSQL noch nicht bereit..."
    sleep 2
done

echo "PostgreSQL ist erreichbar."

# ---------------------------------------------------------
# Migrationen
# ---------------------------------------------------------

echo "Führe Datenbank-Migrationen aus..."

php artisan migrate --force

# ---------------------------------------------------------
# Laravel Cache
# ---------------------------------------------------------

echo "Leere Laravel-Caches..."

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# ---------------------------------------------------------
# Storage
# ---------------------------------------------------------

if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

# ---------------------------------------------------------
# Start
# ---------------------------------------------------------

echo "========================================"
echo " FinanzView ist bereit"
echo "========================================"

exec "$@"
