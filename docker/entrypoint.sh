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

# Ohne APP_KEY wird ein Key erzeugt und im Storage-Volume
# gespeichert, damit er Neustarts überlebt. Besser ist es,
# APP_KEY fest in der Container-Konfiguration zu setzen.

if [ -z "${APP_KEY:-}" ]; then
    KEY_FILE=storage/app/.app_key

    if [ ! -s "$KEY_FILE" ]; then
        echo "APP_KEY fehlt – erzeuge neuen Key in $KEY_FILE"
        mkdir -p storage/app
        php -r 'echo "base64:" . base64_encode(random_bytes(32));' > "$KEY_FILE"
        chmod 600 "$KEY_FILE"
    else
        echo "APP_KEY fehlt – verwende gespeicherten Key aus $KEY_FILE"
    fi

    APP_KEY="$(cat "$KEY_FILE")"
    export APP_KEY
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
# Paket-Cache neu aufbauen
# ---------------------------------------------------------

# bootstrap/cache liegt oft in einem Volume und kann eine
# Paketliste aus einem älteren Image enthalten (z. B. mit
# Entwicklungspaketen, die es im Image nicht mehr gibt).
# Laravel würde dann beim Start abstürzen.

rm -f \
    bootstrap/cache/packages.php \
    bootstrap/cache/services.php

php artisan package:discover --ansi

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
# Berechtigungen
# ---------------------------------------------------------

# Artisan lief oben als root. Dateien, die dabei entstanden
# sind (z. B. storage/logs/laravel.log), gehören sonst root
# und der Webserver (www-data) kann nicht mehr hineinschreiben.

chown -R www-data:www-data \
    storage \
    bootstrap/cache

# ---------------------------------------------------------
# Start
# ---------------------------------------------------------

echo "========================================"
echo " FinanzView ist bereit"
echo "========================================"

exec "$@"
