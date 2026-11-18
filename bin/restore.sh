#!/usr/bin/env bash
# Stellt eine Sicherung von bin/backup.sh wieder her.
#
#   ./bin/restore.sh backups/finanzview-db-2026-11-16_030000.sql.gz [backups/finanzview-files-2026-11-16_030000.tar.gz]
#
# ACHTUNG: ueberschreibt die Tabellen der FinanzView-Datenbank (nur diese) und die Dateien im Ordner storage/app.
# Funktioniert mit der eingebauten Datenbank und mit einer vorhandenen PostgreSQL. Die App muss laufen.
set -euo pipefail

APP="${FINANZVIEW_CONTAINER:-FinanzView}"
PG_IMAGE="${PG_IMAGE:-postgres:18-alpine}"
PGBIN=/usr/libexec/postgresql18
DB_DUMP="${1:-}"
FILES="${2:-}"

[ -f "$DB_DUMP" ] || { echo "Aufruf: $0 <db-dump.sql.gz> [files.tar.gz]" >&2; exit 1; }
[ -z "$FILES" ] || [ -f "$FILES" ] || { echo "Datei nicht gefunden: $FILES" >&2; exit 1; }
docker inspect "$APP" > /dev/null 2>&1 || { echo "Container '$APP' nicht gefunden (FINANZVIEW_CONTAINER setzen)." >&2; exit 1; }

env_of() { docker exec "$APP" printenv "$1" 2>/dev/null || true; }

DB_HOST="$(env_of DB_HOST)"
DB_PORT="$(env_of DB_PORT)"
DB_DATABASE="$(env_of DB_DATABASE)"
DB_USERNAME="$(env_of DB_USERNAME)"
: "${DB_DATABASE:=finanzview}" "${DB_USERNAME:=finanzview}" "${DB_PORT:=5432}"

if [ -z "$DB_HOST" ]; then
    echo "Zieldatenbank: eingebaute Datenbank $DB_DATABASE (Container '$APP')"
else
    echo "Zieldatenbank: $DB_USERNAME@$DB_HOST:$DB_PORT/$DB_DATABASE (Container '$APP')"
fi
echo "Alle aktuellen FinanzView-Daten werden ersetzt durch: $DB_DUMP${FILES:+ und $FILES}"
read -r -p "Zum Fortfahren 'ja' eingeben: " answer
[ "$answer" = "ja" ] || { echo "Abgebrochen."; exit 1; }

echo "Datenbank wiederherstellen ..."
# Dumps aus PostgreSQL 17/18 enthalten eine Zeile (transaction_timeout), die aeltere Server nicht kennen - sie ist ohne Wirkung
if [ -z "$DB_HOST" ]; then
    # Eingebaut: lokale Verbindung ohne Passwort; Eigentuemer der Tabellen wird der App-Benutzer
    gunzip -c "$DB_DUMP" | grep -v '^SET transaction_timeout' | docker exec -i "$APP" "$PGBIN/psql" \
        -v ON_ERROR_STOP=1 -q -h /run/postgresql -U "$DB_USERNAME" -d "$DB_DATABASE" > /dev/null
else
    export PGPASSWORD="$(env_of DB_PASSWORD)"
    export PGSSLMODE="$(env_of DB_SSLMODE)"
    : "${PGSSLMODE:=prefer}"
    export PGSSLMODE

    gunzip -c "$DB_DUMP" | grep -v '^SET transaction_timeout' | docker run --rm -i --network "container:$APP" -e PGPASSWORD -e PGSSLMODE "$PG_IMAGE" \
        psql -v ON_ERROR_STOP=1 -q -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" > /dev/null
fi

if [ -n "$FILES" ]; then
    echo "Dateien wiederherstellen ..."
    docker exec -i "$APP" tar -C /var/www/html/storage -xzf - < "$FILES"
    docker exec "$APP" chown -R www-data:www-data /var/www/html/storage/app
fi

echo "App neu starten (spielt fehlende Migrationen ein) ..."
docker restart "$APP" > /dev/null

echo "Fertig. Pruefen: anmelden und den Katalog ansehen."
