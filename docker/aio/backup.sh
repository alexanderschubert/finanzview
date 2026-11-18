#!/bin/sh
# Tägliche Sicherung der Datenbank nach /data/backups (14 Tage).
# Läuft unter Supervisor; PGPASSWORD und DB_* kommen aus dem Entrypoint.
set -u

DIR=/data/backups
KEEP_DAYS=14

# Nach dem Start etwas warten, dann einmal täglich.
sleep 600

while true; do
    FILE="$DIR/finanzview-$(date +%Y-%m-%d).sql.gz"

    if pg_dump -h "${DB_HOST:-127.0.0.1}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-finanzview}" \
        --no-owner --no-privileges "${DB_DATABASE:-finanzview}" | gzip > "$FILE.tmp"; then
        mv "$FILE.tmp" "$FILE"
        chmod 600 "$FILE"
        echo "[backup] $FILE geschrieben"
    else
        rm -f "$FILE.tmp"
        echo "[backup] Sicherung fehlgeschlagen"
    fi

    find "$DIR" -name 'finanzview-*.sql.gz' -mtime +"$KEEP_DAYS" -delete

    sleep 86400
done
