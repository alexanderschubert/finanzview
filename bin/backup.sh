#!/usr/bin/env bash
# Sichert Datenbank (pg_dump) und Dateien (App-Schluessel, Zugangsdaten) des FinanzView.
#
#   ./bin/backup.sh                       # Container "FinanzView", Ziel ./backups
#   FINANZVIEW_CONTAINER=FinanzView BACKUP_DIR=/mnt/user/backups/finanzview KEEP=14 ./bin/backup.sh
#
# Funktioniert mit der eingebauten Datenbank (ein Container) und mit einer vorhandenen PostgreSQL:
# - eingebaut: pg_dump laeuft im FinanzView-Container selbst
# - vorhanden: pg_dump laeuft in einem kurzlebigen postgres-Container im Netz der App
# Auf Unraid per Plugin "User Scripts" taeglich ausfuehren (siehe README).
set -euo pipefail

APP="${FINANZVIEW_CONTAINER:-FinanzView}"
DEST="${BACKUP_DIR:-./backups}"
KEEP="${KEEP:-14}"
# Fuer vorhandene Datenbanken: pg_dump in der neuesten Version kann auch aeltere Server lesen (nicht umgekehrt)
PG_IMAGE="${PG_IMAGE:-postgres:18-alpine}"
PGBIN=/usr/libexec/postgresql18

docker inspect "$APP" > /dev/null 2>&1 || { echo "Container '$APP' nicht gefunden (FINANZVIEW_CONTAINER setzen)." >&2; exit 1; }

env_of() { docker exec "$APP" printenv "$1" 2>/dev/null || true; }

DB_HOST="$(env_of DB_HOST)"
DB_PORT="$(env_of DB_PORT)"
DB_DATABASE="$(env_of DB_DATABASE)"
DB_USERNAME="$(env_of DB_USERNAME)"
: "${DB_DATABASE:=finanzview}" "${DB_USERNAME:=finanzview}" "${DB_PORT:=5432}"

stamp="$(date +%Y-%m-%d_%H%M%S)"
mkdir -p "$DEST"
db_file="$DEST/finanzview-db-$stamp.sql.gz"
files_file="$DEST/finanzview-files-$stamp.tar.gz"

echo "Datenbank sichern ..."
if [ -z "$DB_HOST" ]; then
    # Eingebaute Datenbank: Unix-Socket im Container, lokal ohne Passwort
    docker exec "$APP" test -s /var/lib/postgresql/data/pg18/PG_VERSION || { echo "Weder DB_HOST noch eine eingebaute Datenbank (PostgreSQL 18) gefunden." >&2; exit 1; }
    docker exec "$APP" "$PGBIN/pg_dump" -h /run/postgresql -U "$DB_USERNAME" -d "$DB_DATABASE" \
        --no-owner --no-privileges --clean --if-exists | gzip > "$db_file.part"
else
    export PGPASSWORD="$(env_of DB_PASSWORD)"
    export PGSSLMODE="$(env_of DB_SSLMODE)"
    : "${PGSSLMODE:=prefer}"
    export PGSSLMODE

    docker run --rm --network "container:$APP" -e PGPASSWORD -e PGSSLMODE "$PG_IMAGE" \
        pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" \
        --no-owner --no-privileges --clean --if-exists | gzip > "$db_file.part"
fi
mv "$db_file.part" "$db_file"

echo "Dateien sichern ..."
docker exec "$APP" tar -C /var/www/html/storage -czf - app > "$files_file.part"
mv "$files_file.part" "$files_file"

chmod 600 "$db_file" "$files_file"

# Nur die juengsten KEEP Sicherungen behalten
for pattern in "finanzview-db-*.sql.gz" "finanzview-files-*.tar.gz"; do
    # shellcheck disable=SC2012
    ls -1t "$DEST"/$pattern 2>/dev/null | tail -n +"$((KEEP + 1))" | while read -r old; do rm -f -- "$old"; done
done

echo "Fertig:"
ls -lh "$db_file" "$files_file"
if [ -n "$(env_of APP_KEY)" ]; then
    echo "Hinweis: APP_KEY ist als Variable gesetzt und steckt nicht in der Dateisicherung - separat aufbewahren."
fi
