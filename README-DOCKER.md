# FinanzView V11 – Docker + PostgreSQL

## Start
docker compose build
docker compose up -d

Öffnen: http://localhost:8099

## Prüfen
docker compose ps
docker compose logs -f app

## Stoppen
docker compose down

PostgreSQL liegt persistent im Docker-Volume `finanzblick_postgres`.
Das Entwicklungs-Passwort aus `.env.docker` wird später für Unraid/Produktion ersetzt.
