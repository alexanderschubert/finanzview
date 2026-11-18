# FinanzView

[![Test, Build and Push](https://github.com/alexanderschubert/finanzview/actions/workflows/docker.yml/badge.svg)](https://github.com/alexanderschubert/finanzview/actions/workflows/docker.yml)
[![Lizenz: MIT](https://img.shields.io/badge/Lizenz-MIT-green.svg)](LICENSE)

**FinanzView** ist eine selbst gehostete Finanzverwaltung für den privaten Überblick – Konten, Buchungen, Budgets, Kredite und Kreditkarten an einem Ort, im aufgeräumten Apple-Stil, hell und dunkel, als Web-App und installierbar auf dem Handy.

Deine Daten bleiben auf deinem eigenen Server (z. B. Unraid, NAS oder Homelab).

---

## Inhalt

- [Funktionen](#funktionen)
- [Installation](#installation)
  - [Unraid](#unraid)
  - [Docker Compose](#docker-compose)
  - [Erste Schritte](#erste-schritte)
- [Konfiguration](#konfiguration)
- [Bankabruf per FinTS](#bankabruf-per-fints)
- [Single Sign-On (Authentik)](#single-sign-on-authentik)
- [Updates und Datensicherung](#updates-und-datensicherung)
- [Entwicklung](#entwicklung)
- [Lizenz](#lizenz)

---

## Funktionen

### 💳 Konten und Buchungen
- Mehrere Konten (Giro, Sparen, Kreditkarte, PayPal, Bargeld, Depot …) als **Wallet-Karten** mit Anbieter-Logo
- Einnahmen, Ausgaben und **Umbuchungen** zwischen eigenen Konten – Umbuchungen zählen nie doppelt
- Suche und Filter nach Monat, Art, Konto, Kategorie und Tag
- **Tags** quer zu Kategorien (z. B. „Urlaub 2026“) mit Summen je Tag
- Wiederkehrende Buchungen (wöchentlich bis jährlich), automatisch jede Nacht gebucht

### 📥 Umsätze übernehmen
- **CSV-Import** von Kontoauszügen – Kodierung, Trennzeichen, Kopfzeile, Zahlen- und Datumsformat werden automatisch erkannt (getestet mit Sparkasse, DKB, ING, comdirect, N26, PayPal, American Express)
- Vorschau vor dem Import, **Duplikat-Erkennung** (überlappende Zeiträume gefahrlos erneut importierbar), „Vorzeichen umkehren“ für Kreditkarten-Exporte
- **Bankabruf per FinTS** auf Knopfdruck – mehrere Konten pro Zugang, pushTAN-Freigabe, Saldo-Abgleich ([mehr dazu](#bankabruf-per-fints))
- **Umbuchungen erkennen:** z. B. die Kreditkarten-Abrechnung (Abbuchung im Girokonto + Gutschrift im Kartenkonto) wird als ein Paar erkannt und mit einem Tipp zusammengefasst

### 🗂️ Ordnung
- Kategorien mit Emoji und Farbe
- **Kategorie-Regeln** („enthält ‚Netflix‘ → Abos“) für neue Buchungen, Import und Bankabruf
- Kategorie-Vorschläge, die aus deinen bisherigen Buchungen lernen

### 🎯 Planung
- **Budgets** – monatlich, jährlich oder frei, mit Verbrauch, Rest und Warnung bei Überschreitung
- **Kredite** mit Tilgungsplan, Zinsen und Sondertilgungen
- **Kreditkarten** mit Limit-Auslastung und Monatsabrechnungen; der Saldo wird aus dem Kartenkonto berechnet

### 📊 Auswertung
- **Dashboard** mit frei anordenbaren Widgets (Vermögen, Monat, Budgets, Kredite, Kreditkarten, Vermögensentwicklung …)
- **Monatsbericht** – Vergleich mit Vormonat und üblichem Monat, Auffälligkeiten in Worten („Lebensmittel: 40 % mehr als üblich“), Budgets, größte Ausgaben, neue Händler
- **Analysen** über frei wählbare Zeiträume mit Kategorien, Heatmap, Top-Händlern und CSV-Export

### 🔐 Sicherheit
- Passwort, **Zwei-Faktor-Authentifizierung** (TOTP) und **Passkeys** (Face ID, Touch ID, Windows Hello)
- **Single Sign-On** über OpenID Connect (z. B. Authentik)
- Liste angemeldeter Geräte, „Auf allen anderen Geräten abmelden“, automatisch nach Passwortänderung
- Mehrbenutzerfähig mit Administration (Benutzer, Registrierung, Finanzanbieter, 2FA/Passkeys zurücksetzen)
- Jeder Benutzer sieht nur seine eigenen Daten

### 📱 Komfort
- **Installierbar als App** (PWA) auf iPhone, iPad, Android und Desktop – Finanzdaten werden dabei nie im Browser-Cache gespeichert
- Hell, Dunkel oder automatisch
- **Datensicherung** als JSON (inkl. Wiederherstellung) und CSV-Export

---

## Installation

FinanzView läuft als **ein** Docker-Container (`ghcr.io/alexanderschubert/finanzview`). Die Datenbank ist **PostgreSQL**: Ohne `DB_HOST` läuft sie **eingebaut im selben Container** (PostgreSQL 18, nur im Container erreichbar), mit `DB_HOST` verwendet FinanzView eine eigene Instanz. Beim Start werden Datenbank-Migrationen automatisch ausgeführt und – falls nicht gesetzt – ein `APP_KEY` erzeugt und im Storage gespeichert.

> **HTTPS empfohlen:** Für die Installation als App (PWA) und für Passkeys ist ein Aufruf über HTTPS mit eigener Domain nötig, z. B. hinter Nginx Proxy Manager, Traefik oder Caddy. Über `http://IP:Port` funktioniert alles andere.

### Unraid

1. Im Unraid-Docker-Tab **„Add Container“** wählen und als Template-URL eintragen:
   ```
   https://raw.githubusercontent.com/alexanderschubert/finanzview/main/finanzview.xml
   ```
2. **Application URL** (`APP_URL`) ausfüllen. Die Pfade **Storage** und **Datenbank (eingebaut)** zeigen standardmäßig auf `/mnt/user/appdata/finanzview/storage` und `/mnt/user/appdata/finanzview/postgres`.
3. **PostgreSQL: Host leer lassen** für die eingebaute Datenbank. Wer eine eigene Instanz nutzt, trägt Host, Datenbank, Benutzer und Passwort ein.
4. Container starten und die Weboberfläche öffnen.

### Docker Compose

Mit eingebauter Datenbank (ein Container):

```yaml
services:
  finanzview:
    image: ghcr.io/alexanderschubert/finanzview:latest
    container_name: finanzview
    restart: unless-stopped
    ports:
      - "8080:80"
    environment:
      APP_URL: https://finanzen.example.com   # bzw. http://<server-ip>:8080
      TZ: Europe/Berlin
    volumes:
      - ./storage:/var/www/html/storage
      - ./postgres:/var/lib/postgresql/data
```

Mit eigener PostgreSQL-Instanz:

```yaml
services:
  finanzview:
    image: ghcr.io/alexanderschubert/finanzview:latest
    container_name: finanzview
    restart: unless-stopped
    ports:
      - "8080:80"
    environment:
      APP_URL: https://finanzen.example.com   # bzw. http://<server-ip>:8080
      TZ: Europe/Berlin
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_PORT: 5432
      DB_DATABASE: finanzview
      DB_USERNAME: finanzview
      DB_PASSWORD: bitte-aendern
    volumes:
      - ./storage:/var/www/html/storage
    depends_on:
      postgres:
        condition: service_healthy

  postgres:
    image: postgres:17-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: finanzview
      POSTGRES_USER: finanzview
      POSTGRES_PASSWORD: bitte-aendern
    volumes:
      - postgres-data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U finanzview -d finanzview"]
      interval: 5s
      retries: 20

volumes:
  postgres-data:
```

```bash
docker compose up -d
```

Danach `http://<server-ip>:8080` öffnen. Der Container meldet unter `/up` seinen Zustand (für Healthchecks).

### Eingebaute Datenbank

- Sie hört nur im Container (127.0.0.1). Das Passwort erzeugt der Container und legt es in `storage/app/.db_password` ab.
- Die Daten liegen im Ordner „Datenbank (eingebaut)“ in einem Unterordner pro PostgreSQL-Hauptversion (`pg18`). **Storage und Datenbank gehören in die Sicherung.** Ein Datenordner einer anderen Hauptversion wird nicht gestartet, sondern mit einer Fehlermeldung abgelehnt.
- Sicherung und Wiederherstellung übernehmen die Skripte `bin/backup.sh` und `bin/restore.sh` (siehe [Updates und Datensicherung](#updates-und-datensicherung)).

**Umzug von einer eigenen PostgreSQL-Datenbank auf die eingebaute:**

1. Mit `bin/backup.sh` sichern (der Container läuft noch mit der alten Datenbank).
2. Container ohne `DB_HOST` neu anlegen, dabei den bisherigen `APP_KEY` setzen. Er legt die eingebaute Datenbank an und migriert.
3. Mit `bin/restore.sh <db-dump> [files-tar]` die Sicherung einspielen.

### Erste Schritte

1. **Registrieren** – der **erste Benutzer wird automatisch Administrator**.
2. Unter *Administration* bei Bedarf die öffentliche Registrierung abschalten.
3. Konten anlegen und Umsätze per CSV importieren (*Buchungen → Importieren*).
4. Optional: *Einstellungen → Sicherheit* – Zwei-Faktor-Authentifizierung oder Passkey einrichten.
5. Optional: App installieren – *Einstellungen → FinanzView als App*.

Ausgesperrt? Einen Benutzer wieder zum Administrator machen:

```bash
docker exec -it finanzview php artisan finanzview:admin max@example.com
```

---

## Konfiguration

Alle Einstellungen werden als Umgebungsvariablen gesetzt (Unraid-Template bzw. `environment:` in Compose).

| Variable | Pflicht | Beschreibung |
|---|---|---|
| `APP_URL` | ja | Öffentliche Adresse, z. B. `https://finanzen.example.com` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | ja | PostgreSQL-Verbindung (`DB_CONNECTION=pgsql`) |
| `APP_KEY` | – | Verschlüsselungsschlüssel. Leer lassen: wird beim ersten Start erzeugt und im Storage gespeichert. **Einmal gesetzt nie ändern.** |
| `TZ` | – | Zeitzone, Standard `Europe/Berlin` |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | – | E-Mail-Versand für „Passwort vergessen“ (Standard: nur ins Log) |
| `OIDC_ENABLED`, `OIDC_ISSUER`, `OIDC_CLIENT_ID`, `OIDC_CLIENT_SECRET`, `OIDC_BUTTON_LABEL`, `OIDC_TRUST_EMAIL` | – | Single Sign-On, siehe unten |
| `FINTS_PRODUCT_ID` | – | FinTS-Produktregistrierungsnummer für den Bankabruf, siehe unten |
| `PASSKEYS_RP_ID`, `PASSKEYS_ALLOWED_ORIGINS` | – | Nur falls Passkeys hinter einem ungewöhnlichen Proxy nicht funktionieren (Standard: aufgerufene Domain) |

Wichtig: Das Storage-Volume (`/var/www/html/storage`) enthält den erzeugten `APP_KEY`, Uploads und Logs und muss erhalten bleiben.

---

## Bankabruf per FinTS

FinanzView kann Umsätze und Kontostände direkt von deutschen Banken abrufen (FinTS/HBCI, z. B. Sparkassen, Volks- und Raiffeisenbanken) – auf Knopfdruck, nur lesend.

- Die **Online-Banking-PIN wird nie gespeichert**, sondern bei jedem Abruf abgefragt.
- Freigabe per **pushTAN-App** oder TAN-Eingabe, falls die Bank sie verlangt (meist nur alle 90–180 Tage).
- Mehrere Konten pro Zugang (z. B. Girokonto + Sparbuch), Saldo-Abgleich, Duplikat-Erkennung und Kategorie-Regeln.

**Voraussetzung:** Jede Installation braucht eine eigene, **kostenlose FinTS-Produktregistrierungsnummer** der Deutschen Kreditwirtschaft (Formular auf [hbci-zka.de](https://www.hbci-zka.de/register/prod_register.htm), Produktkategorie „Web-Server“, kein Gewerbe nötig). Die Nummer als `FINTS_PRODUCT_ID` eintragen und den Container neu starten; danach erscheint *Einstellungen → Bankverbindungen*. Die Nummer ist an dich gebunden und gehört nicht in öffentliche Repositories.

Ohne Nummer bleibt der CSV-Import der Weg.

---

## Single Sign-On (Authentik)

1. In Authentik einen **OAuth2/OpenID Provider** anlegen:
   - Client type: *Confidential*
   - Redirect URI: `https://<deine-finanzview-url>/auth/oidc/callback`
   - Scopes: `openid`, `profile`, `email`
2. Eine **Application** (z. B. Slug `finanzview`) mit diesem Provider anlegen.
3. In FinanzView setzen:

```env
OIDC_ENABLED=true
OIDC_ISSUER=https://auth.example.com/application/o/finanzview/
OIDC_CLIENT_ID=<Client ID>
OIDC_CLIENT_SECRET=<Client Secret>
OIDC_BUTTON_LABEL="Mit Authentik anmelden"
```

- Bestehende Konten werden über die E-Mail-Adresse verknüpft, sofern der Provider sie als bestätigt meldet (`email_verified`). Sonst `OIDC_TRUST_EMAIL=true` setzen – nur, wenn Benutzer ihre E-Mail im Provider nicht selbst ändern können.
- Neue Konten entstehen nur, wenn die Registrierung aktiviert ist.
- Die Zwei-Faktor-Abfrage übernimmt bei SSO der Provider; der Passwort-Login bleibt zusätzlich verfügbar.

---

## Updates und Datensicherung

**Update:** Neues Image ziehen und den Container neu erstellen – in Unraid „Update“ bzw. „Force Update“, in Compose:

```bash
docker compose pull && docker compose up -d
```

Migrationen laufen beim Start automatisch.

**Datensicherung:**
- In der App: *Einstellungen → Daten & Export* – JSON-Backup herunterladen und bei Bedarf wiederherstellen (vorhandene Daten bleiben erhalten, Dubletten werden erkannt).
- Empfohlen: `bin/backup.sh` täglich ausführen (auf Unraid mit dem Plugin „User Scripts“). Es sichert die Datenbank (`pg_dump`, eingebaut oder eigene Instanz) und `storage/app` (App-Schlüssel, Datenbank-Passwort) und behält die letzten 14 Sicherungen:

  ```bash
  FINANZVIEW_CONTAINER=FinanzView BACKUP_DIR=/mnt/user/appdata/finanzview/backups KEEP=14 ./bin/backup.sh
  ```

  Wiederherstellen (ersetzt die aktuellen Daten, der Container muss laufen):

  ```bash
  FINANZVIEW_CONTAINER=FinanzView ./bin/restore.sh /mnt/user/appdata/finanzview/backups/finanzview-db-<Zeit>.sql.gz /mnt/user/appdata/finanzview/backups/finanzview-files-<Zeit>.tar.gz
  ```

  `FINANZVIEW_CONTAINER` ist der Name deines Containers. Ist `APP_KEY` als Variable gesetzt, steckt er nicht in der Dateisicherung und muss separat aufbewahrt werden.

---

## Entwicklung

**Technik:** Laravel 13 (PHP 8.4), Fortify (2FA, Passkeys), Blade, Tailwind CSS 4, Vite, PostgreSQL. FinTS über [php-fints](https://github.com/nemiah/phpFinTS).

Das Docker-Image hat eine eigene Test-Stufe, die auch in der CI läuft:

```bash
docker build --target test -t finanzview-tests .
docker run --rm --entrypoint sh finanzview-tests -c 'touch .env && php artisan test'
```

**Branches:** `main` ist der stabile Stand (Image `:latest`, das auch die Unraid-Vorlage nutzt), `dev` ist der Entwicklungsstand (Image `:dev`). Neue Arbeit entsteht in kurzlebigen Branches (`feature/…`, `fix/…`), Pull Requests gehen nach `dev`. Ist `dev` getestet, wird er per Pull Request nach `main` übernommen (Release). Eilige Korrekturen gehen direkt nach `main`; danach `main` wieder in `dev` mergen.

**Labels, Issues und Pull Requests** sind komplett auf Deutsch: Vorlagen für Fehler, Funktionswünsche und Fragen, eine Pull-Request-Vorlage mit Checkliste und Labels wie *Fehler*, *Funktion*, *Design*, *Datenbank*, *Docker & Betrieb*, *Priorität: hoch* oder *Status: in Arbeit*. Pull Requests bekommen ihre Labels automatisch anhand von Branch-Name und geänderten Dateien.

Jeder Pull Request wird automatisch getestet und als Vorschau-Image (`:pr-<Nummer>`) gebaut. Merges auf `dev` erzeugen `:dev`, Merges auf `main` erzeugen `:latest`. Die wöchentliche Neu-Erstellung (Sicherheitsupdates) läuft auf `main`.

```text
app/
├── Http/Controllers/     Seiten und Aktionen
├── Models/               Eloquent-Modelle
├── Services/             Berechnungen (Budgets, Berichte, Import, FinTS, Umbuchungen …)
└── Services/Fints/       Bankabruf
resources/views/          Blade-Oberfläche und Komponenten
database/migrations/      Datenbankschema
tests/                    Unit- und Feature-Tests
finanzview.xml            Unraid-Template
```

---

## Lizenz

[MIT](LICENSE) – FinanzView ist ein privates Open-Source-Projekt und keine Finanz- oder Anlageberatung.
