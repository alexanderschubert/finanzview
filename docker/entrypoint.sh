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
# Eingebaute Datenbank
# ---------------------------------------------------------

# Mit DB_HOST wird eine vorhandene PostgreSQL-Instanz verwendet. Ohne DB_HOST
# läuft PostgreSQL 18 im Container (nur über 127.0.0.1 erreichbar). Die Daten
# liegen im Volume /var/lib/postgresql/data, in einem Unterordner pro
# Hauptversion (pg18). Passwort und Zugang erzeugt der Container selbst; das
# Passwort steht in storage/app/.db_password.

EMBEDDED_DB=0
PG_MAJOR=18
PGBIN=/usr/libexec/postgresql$PG_MAJOR
PG_ROOT=/var/lib/postgresql/data
PGDATA=$PG_ROOT/pg$PG_MAJOR
PGOPTS="-c listen_addresses=127.0.0.1 -c unix_socket_directories=/run/postgresql -c shared_buffers=128MB -c max_connections=60"

as_postgres() {
    su-exec postgres sh -c "$1"
}

rm -f /etc/supervisor/conf.d/postgres.conf

if [ "${DB_CONNECTION:-pgsql}" = "pgsql" ] && [ -z "${DB_HOST:-}" ]; then
    EMBEDDED_DB=1
    DBNAME="${DB_DATABASE:-finanzview}"
    DBUSER="${DB_USERNAME:-finanzview}"

    case "$DBNAME$DBUSER" in
        *[!a-z0-9_]*|"")
            echo "DB_DATABASE und DB_USERNAME dürfen bei der eingebauten Datenbank nur a-z, 0-9 und _ enthalten."
            exit 1
            ;;
    esac

    mkdir -p "$PG_ROOT" /run/postgresql storage/app
    chown postgres:postgres "$PG_ROOT" /run/postgresql
    chmod 700 "$PG_ROOT"

    # Passwort einmalig erzeugen. Es wird bei jedem Start der Datenbank neu
    # zugewiesen: fehlt die Datei, heilt sich das von selbst.
    if [ ! -s storage/app/.db_password ]; then
        head -c 64 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 32 > storage/app/.db_password
    fi

    DBPASS="$(cat storage/app/.db_password)"

    case "$DBPASS" in
        *[!A-Za-z0-9]*|"")
            echo "storage/app/.db_password darf nur Buchstaben und Ziffern enthalten (Datei löschen, dann wird sie neu erzeugt)."
            exit 1
            ;;
    esac

    chown www-data:www-data storage/app/.db_password
    chmod 640 storage/app/.db_password

    if [ -s "$PG_ROOT/PG_VERSION" ] || { [ ! -s "$PGDATA/PG_VERSION" ] && [ -n "$(ls -A "$PG_ROOT" 2>/dev/null)" ]; }; then
        echo "Der Datenordner $PG_ROOT enthält keine PostgreSQL-$PG_MAJOR-Datenbank im Unterordner pg$PG_MAJOR."
        echo "Bei Daten einer anderen PostgreSQL-Version: mit der alten Version sichern (bin/backup.sh) und in eine neue Datenbank einspielen (bin/restore.sh)."
        exit 1
    fi

    if [ ! -s "$PGDATA/PG_VERSION" ]; then
        echo "Lege die eingebaute PostgreSQL-Datenbank an..."
        mkdir -p "$PGDATA"
        chown postgres:postgres "$PGDATA"
        chmod 700 "$PGDATA"

        INITDB="$PGBIN/initdb -D '$PGDATA' -U postgres -E UTF8 --auth-local=trust --auth-host=scram-sha-256"

        as_postgres "$INITDB --locale=C.UTF-8 > /dev/null" || {
            rm -rf "$PGDATA"/* "$PGDATA"/.[!.]* 2>/dev/null || true
            as_postgres "$INITDB --locale=C > /dev/null"
        }
    fi

    # Gehören die Dateien einem anderen Benutzer (z. B. nach dem Kopieren des Ordners), Besitzer angleichen.
    if [ "$(stat -c %u "$PGDATA")" != "$(id -u postgres)" ] || [ "$(stat -c %u "$PGDATA/PG_VERSION")" != "$(id -u postgres)" ]; then
        echo "Passe die Dateirechte des Datenordners an..."
        chown -R postgres:postgres "$PGDATA"
    fi

    rm -f /run/postgresql/start.log

    as_postgres "$PGBIN/pg_ctl -D '$PGDATA' -o '$PGOPTS' -w -t 120 -l /run/postgresql/start.log start > /dev/null" || {
        cat /run/postgresql/start.log
        echo "Die eingebaute Datenbank startet nicht."
        exit 1
    }

    as_postgres "$PGBIN/psql -h /run/postgresql -d postgres -v ON_ERROR_STOP=1 -q" <<SQL
DO \$\$ BEGIN
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '$DBUSER') THEN CREATE ROLE "$DBUSER" LOGIN; END IF;
END \$\$;
ALTER ROLE "$DBUSER" WITH LOGIN PASSWORD '$DBPASS';
SELECT 'CREATE DATABASE "$DBNAME" OWNER "$DBUSER"' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$DBNAME')\gexec
SQL

    export DB_CONNECTION=pgsql
    export DB_HOST=127.0.0.1
    export DB_PORT=5432
    export DB_DATABASE="$DBNAME"
    export DB_USERNAME="$DBUSER"
    export DB_PASSWORD="$DBPASS"

    # Nach dem Einrichten übernimmt Supervisor den Betrieb (Neustart bei Absturz).
    cat > /etc/supervisor/conf.d/postgres.conf <<CONF
[program:postgres]
command=$PGBIN/postgres -D $PGDATA $PGOPTS
user=postgres
priority=1
autorestart=true
stopsignal=INT
stopwaitsecs=60
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
CONF
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

# Die eingebaute Datenbank lief nur für die Einrichtung; im Betrieb startet
# sie Supervisor.
if [ "$EMBEDDED_DB" = "1" ]; then
    as_postgres "$PGBIN/pg_ctl -D '$PGDATA' -m fast -w -t 120 stop > /dev/null"
fi

# ---------------------------------------------------------
# Start
# ---------------------------------------------------------

echo "========================================"
echo " FinanzView ist bereit"
echo "========================================"

exec "$@"
