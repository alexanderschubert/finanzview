#!/bin/sh
# FinanzView All-in-One: startet PostgreSQL, die App (Nginx, PHP-FPM, Scheduler)
# und die tägliche Datensicherung in einem Container.
#
# Alle Daten liegen im Volume /data:
#   /data/postgres   Datenbank
#   /data/storage    Laravel-Storage (inkl. APP_KEY, falls nicht gesetzt)
#   /data/backups    tägliche Sicherungen (14 Tage)
#   /data/.db_password   zufälliges Datenbank-Passwort (nur im Container nötig)
#   /data/import.sql     optional: wird beim allerersten Start in die neue Datenbank geladen
set -eu

DATA=/data
PGDATA="$DATA/postgres"
APP=/var/www/html
DB_NAME=finanzview
DB_USER=finanzview

PGBIN=$(dirname "$(ls /usr/libexec/postgresql*/initdb 2>/dev/null | head -n 1)")
PG_MAJOR=$(basename "$PGBIN" | sed 's/[^0-9]//g')

PG_PID=""
SUP_PID=""

log() {
    echo "[all-in-one] $*"
}

stop_all() {
    if [ -n "$SUP_PID" ] && kill -0 "$SUP_PID" 2>/dev/null; then
        kill -TERM "$SUP_PID" 2>/dev/null || true
        wait "$SUP_PID" 2>/dev/null || true
    fi

    if [ -n "$PG_PID" ] && kill -0 "$PG_PID" 2>/dev/null; then
        log "Fahre PostgreSQL sauber herunter..."
        kill -INT "$PG_PID" 2>/dev/null || true
        wait "$PG_PID" 2>/dev/null || true
    fi
}

on_signal() {
    stop_all
    exit 0
}

trap on_signal TERM INT

mkdir -p "$DATA/postgres" "$DATA/storage" "$DATA/backups"

# ---------------------------------------------------------
# Storage im Volume
# ---------------------------------------------------------

if [ ! -L "$APP/storage" ]; then
    # Erster Start mit diesem Volume: Gerüst aus dem Image übernehmen.
    if [ -z "$(ls -A "$DATA/storage" 2>/dev/null)" ]; then
        cp -a "$APP/storage/." "$DATA/storage/"
    fi

    rm -rf "$APP/storage"
    ln -s "$DATA/storage" "$APP/storage"
fi

# ---------------------------------------------------------
# Datenbank vorbereiten (nur beim ersten Start)
# ---------------------------------------------------------

if [ -s "$PGDATA/PG_VERSION" ] && [ "$(cat "$PGDATA/PG_VERSION")" != "$PG_MAJOR" ]; then
    log "FEHLER: Die Datenbank in $PGDATA ist PostgreSQL $(cat "$PGDATA/PG_VERSION"), dieses Image enthält PostgreSQL $PG_MAJOR."
    log "Bitte eine Sicherung aus /data/backups einspielen oder ein passendes Image verwenden."
    exit 1
fi

PW_FILE="$DATA/.db_password"

if [ ! -s "$PW_FILE" ]; then
    head -c 48 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 32 > "$PW_FILE"
    chmod 600 "$PW_FILE"
fi

DB_PASSWORD=$(cat "$PW_FILE")

if [ ! -s "$PGDATA/PG_VERSION" ]; then
    log "Lege neue Datenbank an (PostgreSQL $PG_MAJOR)..."

    chown postgres:postgres "$PGDATA"
    chmod 700 "$PGDATA"

    su-exec postgres "$PGBIN/initdb" -D "$PGDATA" \
        --username=postgres \
        --auth-local=trust \
        --auth-host=scram-sha-256 \
        --encoding=UTF8 \
        --locale=C.UTF-8 >/dev/null

    # Nur aus dem Container erreichbar.
    {
        echo "listen_addresses = '127.0.0.1'"
        echo "unix_socket_directories = '/run/postgresql'"
        echo "password_encryption = scram-sha-256"
    } >> "$PGDATA/postgresql.conf"

    su-exec postgres "$PGBIN/pg_ctl" -D "$PGDATA" -w -t 120 \
        -o "-c listen_addresses=''" start >/dev/null

    psql -h /run/postgresql -U postgres -v ON_ERROR_STOP=1 -q <<SQL
CREATE ROLE $DB_USER LOGIN PASSWORD '$DB_PASSWORD';
CREATE DATABASE $DB_NAME OWNER $DB_USER ENCODING 'UTF8' TEMPLATE template0 LC_COLLATE 'C.UTF-8' LC_CTYPE 'C.UTF-8';
SQL

    if [ -s "$DATA/import.sql" ]; then
        log "Lade $DATA/import.sql in die neue Datenbank..."
        # Als Benutzer der App einspielen: die Tabellen gehören dann ihm.
        psql -h /run/postgresql -U "$DB_USER" -d "$DB_NAME" -v ON_ERROR_STOP=1 -q -f "$DATA/import.sql"
        mv "$DATA/import.sql" "$DATA/import.sql.done"
        log "Import abgeschlossen (Datei umbenannt in import.sql.done)."
    fi

    su-exec postgres "$PGBIN/pg_ctl" -D "$PGDATA" -w -t 120 stop >/dev/null
fi

# ---------------------------------------------------------
# PostgreSQL starten
# ---------------------------------------------------------

mkdir -p /run/postgresql
chown postgres:postgres /run/postgresql

su-exec postgres "$PGBIN/postgres" -D "$PGDATA" &
PG_PID=$!

# ---------------------------------------------------------
# App vorbereiten (wartet auf die Datenbank, migriert, leert Caches)
# ---------------------------------------------------------

export DB_CONNECTION=pgsql
export DB_HOST=127.0.0.1
export DB_PORT=5432
export DB_DATABASE="$DB_NAME"
export DB_USERNAME="$DB_USER"
export DB_PASSWORD
export PGPASSWORD="$DB_PASSWORD"

finanzview-entrypoint true

# Artisan lief als root: Dateien im Storage gehören sonst root.
chown -R www-data:www-data "$DATA/storage" "$APP/bootstrap/cache"

# ---------------------------------------------------------
# Dienste starten und überwachen
# ---------------------------------------------------------

"$@" &
SUP_PID=$!

while kill -0 "$SUP_PID" 2>/dev/null && kill -0 "$PG_PID" 2>/dev/null; do
    sleep 5 &
    wait $!
done

log "Ein Dienst wurde beendet – Container wird beendet."
stop_all
exit 1
