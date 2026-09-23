# FinanzView

FinanzView ist eine moderne, selbst gehostete Finanzverwaltung zur Verwaltung von Konten, Buchungen, Kategorien, Budgets und wiederkehrenden Ausgaben.

Das Projekt wird als Webanwendung betrieben und ist für den privaten bzw. persönlichen Finanzüberblick ausgelegt.

## 🚀 Funktionen

### 💳 Konten

- Verwaltung mehrerer Konten
- Kontostände und Kontobewegungen
- Zuordnung von Buchungen zu Konten
- Übersicht über Einnahmen und Ausgaben

### 💸 Transaktionen

- Einnahmen und Ausgaben erfassen
- Buchungsdatum
- Beschreibung
- Händler
- Kategorie
- Konto
- Notizen
- Referenz
- Wiederkehrende Buchungen
- Ausstehende Buchungen
- Bearbeiten und Löschen von Buchungen

### 🗂️ Kategorien

- Eigene Kategorien erstellen
- Kategorien bearbeiten und löschen
- Icons für Kategorien
- Zuordnung von Buchungen zu Kategorien

### 🎯 Budgets

FinanzView unterstützt verschiedene Budgetarten:

- Monatliche Budgets
- Jährliche Budgets
- Benutzerdefinierte Budgets
- Start- und Enddatum
- Budgetbetrag
- Frei wählbare Kategorien
- Individuelle Farben und Icons
- Anzeige des aktuellen Verbrauchs
- Verbleibender Betrag
- Prozentualer Verbrauch
- Erkennung von Budgetüberschreitungen
- Zugehörige Buchungen innerhalb des Budgetzeitraums

Die Berechnung der Budgets erfolgt zentral über den `BudgetService`, damit Dashboard, Budgetübersicht und Budgetdetails dieselbe Berechnungslogik verwenden.

### 📈 Analysen

- Zeiträume: dieser/letzter Monat, letzte 3/6/12 Monate, dieses/letztes Jahr oder frei wählbar
- Filter nach Konto
- Einnahmen, Ausgaben, Saldo und Sparquote mit Vergleich zur Vorperiode
- Monatsverlauf von Einnahmen und Ausgaben
- Ausgaben und Einnahmen nach Kategorie inkl. Anteil und Veränderung
- Heatmap: Ausgaben je Kategorie und Monat
- Top-Händler und größte Einzelausgaben
- Export als CSV (Excel-kompatibel) sowie Druck/PDF über den Browser

Umbuchungen zwischen eigenen Konten zählen nicht als Einnahme oder Ausgabe. Die Berechnung erfolgt im `ReportService`.

### 🔐 Anmeldung & Sicherheit

- Login mit E-Mail und Passwort
- Zwei-Faktor-Authentifizierung (TOTP) mit Authenticator-App und Wiederherstellungscodes – einrichten unter *Einstellungen → Sicherheit*
- Single Sign-On über OpenID Connect (z. B. Authentik)

#### OIDC mit Authentik einrichten

1. In Authentik einen **OAuth2/OpenID Provider** anlegen:
   - Client type: *Confidential*
   - Redirect URI: `https://<deine-finanzview-url>/auth/oidc/callback`
   - Scopes: `openid`, `profile`, `email`
2. Eine **Application** (z. B. Slug `finanzview`) mit diesem Provider anlegen.
3. In FinanzView (Unraid-Template bzw. `.env`) setzen:

```env
OIDC_ENABLED=true
OIDC_ISSUER=https://auth.example.com/application/o/finanzview/
OIDC_CLIENT_ID=<Client ID>
OIDC_CLIENT_SECRET=<Client Secret>
OIDC_BUTTON_LABEL="Mit Authentik anmelden"
```

Verhalten:

- Bestehende Konten werden beim ersten SSO-Login über die E-Mail-Adresse verknüpft, sofern der Provider sie als bestätigt meldet (`email_verified`). Liefert Authentik `email_verified: false`, kann `OIDC_TRUST_EMAIL=true` gesetzt werden.
- Neue Konten werden nur angelegt, wenn im Admin-Bereich die Registrierung aktiviert ist.
- Der Passwort-Login bleibt zusätzlich verfügbar.
- Bei SSO-Logins übernimmt der Provider die Zwei-Faktor-Abfrage.

### 📊 Dashboard

Das Dashboard soll einen schnellen Überblick über die persönliche finanzielle Situation ermöglichen.

Geplante bzw. vorhandene Informationen:

- Kontostände
- Einnahmen
- Ausgaben
- Budgets
- Budgetverbrauch
- aktuelle Buchungen
- finanzielle Entwicklungen

---

## 🛠️ Technologie

FinanzView basiert auf modernen Open-Source-Technologien.

### Backend

- PHP
- Laravel
- Eloquent ORM
- Laravel Blade

### Frontend

- Blade Templates
- Tailwind CSS
- JavaScript

### Datenbank

Die Anwendung ist für relationale Datenbanken ausgelegt.

Aktuell wird eine SQL-basierte Datenbank verwendet.

### Betrieb

FinanzView kann containerisiert betrieben werden und eignet sich dadurch besonders für einen eigenen Server, NAS oder Homelab.

---

## 📁 Projektstruktur

Eine vereinfachte Struktur des Projekts:

```text
finanzview/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   └── Services/
│
├── database/
│   ├── migrations/
│   └── seeders/
│
├── resources/
│   └── views/
│       ├── budgets/
│       ├── transactions/
│       ├── accounts/
│       └── ...
│
├── routes/
│   └── web.php
│
├── storage/
│
├── tests/
│
├── artisan
├── composer.json
└── README.md